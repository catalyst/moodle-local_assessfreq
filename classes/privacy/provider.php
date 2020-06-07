<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Privacy Subsystem implementation for local_assessfreq.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;

/**
 * Privacy Subsystem for local_assessfreq implementing null_provider.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
\core_privacy\local\metadata\provider,
\core_privacy\local\request\data_provider {

    /**
     * Returns metadata about this plugin's privacy policy.
     *
     * @param   collection $collection The initialised collection to add items to.
     * @return  collection     A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'local_assessfreq_user',
            [
                'id' => 'privacy:metadata:local_assessfreq_user:id',
                'userid' => 'privacy:metadata:local_assessfreq_user:userid',
                'eventid' => 'privacy:metadata:local_assessfreq_user:eventid',
            ],
            'privacy:metadata:local_assessfreq_user'
            );
        $collection->add_database_table(
            'local_assessfreq_conf_user',
            [
                'id' => 'privacy:metadata:local_assessfreq_conf_user:id',
                'userid' => 'privacy:metadata:local_assessfreq_conf_user:userid',
                'conflictid' => 'privacy:metadata:local_assessfreq_conf_user:conflictid',
            ],
            'privacy:metadata:local_assessfreq_conf_user'
            );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the given user.
     *
     * @param int $userid the userid to search.
     * @return contextlist the contexts in which data is contained.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new \core_privacy\local\request\contextlist();
        $contextlist->add_user_context($userid);
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Gets the list of users who have data with a context.
     *
     * @param userlist $userlist the userlist containing users who have data in this context.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        // If current context is system, all users are contained within, get all users.
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $sql = "
            SELECT *
            FROM {local_assessfreq_user}";
            $userlist->add_from_sql('userid', $sql, array());

            $sql = "
            SELECT *
            FROM {local_assessfreq_conf_user}";
            $userlist->add_from_sql('userid', $sql, array());
        }
    }

    /**
     * Exports all data stored in provided contexts for user.
     *
     * @param approved_contextlist $contextlist the list of contexts to export for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {

            // If not in system context, exit loop.
            if ($context->contextlevel == CONTEXT_SYSTEM) {

                $parentclass = array();

                // Get records for user ID.
                $rows = $DB->get_records('local_assessfreq_user', array('userid' => $userid));

                if (count($rows) > 0) {
                    $i = 0;
                    foreach ($rows as $row) {
                        $parentclass[$i]['userid'] = $row->userid;
                        $parentclass[$i]['eventid'] = $row->eventid;
                        $i++;
                    }
                }

                $rows = $DB->get_records('local_assessfreq_conf_user', array('userid' => $userid));

                if (count($rows) > 0) {
                    foreach ($rows as $row) {
                        $parentclass[$i]['userid'] = $row->userid;
                        $parentclass[$i]['conflictid'] = $row->conflictid;
                        $i++;
                    }
                }

                writer::with_context($context)->export_data(
                    [get_string('privacy:metadata:local_assessfreq', 'local_assessfreq')],
                    (object) $parentclass);
            }
        }
    }

    /**
     * Deletes data for all users in context.
     *
     * @param context $context The context to delete for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        // All data contained in system context.
        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $sql = "
            DELETE
            FROM {local_assessfreq_user}";
            $DB->execute($sql);

            $sql = "
            DELETE
            FROM {local_assessfreq_conf_user}";
            $DB->execute($sql);
        }
    }

    /**
     * Deletes all data in all provided contexts for user.
     *
     * @param approved_contextlist $contextlist the list of contexts to delete for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        $userid = $contextlist->get_user()->id;
        foreach ($contextlist as $context) {
            // If not in system context, skip context.
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                $sql = "DELETE
                        FROM {local_assessfreq_user} mfa
                        WHERE mfa.userid = :userid";

                $DB->execute($sql, array('userid' => $userid));

                $sql = "DELETE
                        FROM {local_assessfreq_conf_user} mfa
                        WHERE mfa.userid = :userid";

                $DB->execute($sql, array('userid' => $userid));
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        $users = $userlist->get_users();
        foreach ($users as $user) {
            // Create contextlist.
            $contextlist = new approved_contextlist($user, 'local_assessfreq', array(CONTEXT_SYSTEM));
            // Call delete data.
            self::delete_data_for_user($contextlist);
        }
    }
}