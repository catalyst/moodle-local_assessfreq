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
 * Frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq;

use cache;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Frequency class.
 *
 * This class handles data processing to get assessment frequency data
 * used in generating the heat map display for the plugin.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class frequency {

    /**
     * Mapp of event count to heat setting.
     * TODO: Create array from plugin settings.
     *
     * @var array
     */
    private $map = array (
        0 => 0,
        2 => 1,
        4 => 2,
        6 => 3,
        8 => 4,
        10 => 5
    );

    /**
     * The due date databse field differs between module types.
     * This map provides the translation.
     *
     * @var array $modduefield
     */
    private $modulefield = array (
        'assign' => 'duedate',
        'choice' => 'timeclose',
        'data' => 'timeavailableto',
        'feedback' => 'timeclose',
        'forum' => 'duedate',
        'lesson' => 'deadline',
        'quiz' => 'timeclose',
        'scorm' => 'timeclose',
        'workshop' => 'submissionend'
    );

    /**
     * Map of capabilities that users must have
     * before that activity event applies to them.
     *
     * @var array $capabilitymap
     */
    private $capabilitymap = array (
        'assign' => array('mod/assign:submit', 'mod/assign:view'),
        'choice' => array(),
        'data' => array(),
        'feedback' => array(),
        'forum' => array(),
        'lesson' => array(),
        'quiz' => array(),
        'scorm' => array(),
        'workshop' => array()
    );

    /**
     * Expiry period for caches.
     *
     * @var int $expiryperiod.
     */
    private $expiryperiod = 60 * 60; // One hour.

    /**
     * Size of batch to insert records into database.
     *
     * @var integer $batchsize
     */
    private $batchsize = 100;

    /**
     * Given a count value get the corresponding heat setting.
     *
     * @param int $count The event count.
     * @return int $result The heat setting that relates to the given count.
     */
    private function get_map(int $count) : int {
        $result = 0;

        foreach ($this->map as $key => $value) {
            if ($count > $key) {
                $result = $value;
            }
        }

        return $result;
    }

    /**
     * Get the modules to use in data collection.
     * This is based on plugin configuration.
     *
     * @return array $modules The enabled modules.
     */
    private function get_modules() : array {
        // TODO: Get these from plugin config rather than hard code.
        return array ('assign', 'choice', 'data', 'feedback', 'forum', 'lesson', 'quiz', 'scorm', 'workshop');
    }

    /**
     * Generate SQL to use to get activity info.
     *
     * @param string $module Activity module to get data for.
     * @return string $sql The generated SQL.
     */
    private function get_sql_query(string $module) : string {

        $duedate = $this->modulefield[$module];
        $sql = 'SELECT cm.id, cm.course, m.name, cm.instance, c.id as contextid, a.' . $duedate . ' AS duedate
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON cm.module = m.id
            INNER JOIN {context} c ON cm.id = c.instanceid
            INNER JOIN {' . $module . '} a ON cm.instance = a.id
            INNER JOIN {course} course ON cm.course = course.id
                 WHERE m.name = ?
                       AND c.contextlevel = ?
                       AND a.' . $duedate . ' >= ?
                       AND cm.visible = ?
                       AND course.visible = ?';
        return $sql;
    }

    /**
     * Get a recordset of all events for a particular module.
     * SQL sent as a variable determines what events to get.
     *
     * @param string $sql
     * @param array $params
     * @return \moodle_recordset
     */
    private function get_module_events(string $sql, array $params) : \moodle_recordset {
        global $DB;

        $recordset = $DB->get_recordset_sql($sql, $params);

        return $recordset;
    }

    /**
     * Given a unix timestamp, splits into year, month and day
     * ready for further processing.
     *
     * @param int $timestamp The unix timestamp to split.
     * @return array $timeelements Array of split time.
     */
    private function format_time(int $timestamp) : array {
        $timeelements = array(
            'endyear' => date('Y', $timestamp),
            'endmonth' => date('m', $timestamp),
            'endday' => date('d', $timestamp),
        );

        return $timeelements;
    }

    /**
     * Take a recordest of events process
     * and store in correct database table.
     *
     * @param \moodle_recordset $recordset
     * @return array
     */
    private function process_module_events(\moodle_recordset $recordset) : int {
        global $DB;
        $recordsprocessed = 0;
        $toinsert = array();

        foreach ($recordset as $record) {
            // Iterate through the records and insert to database in batches.
            $timeelements = $this->format_time($record->duedate);
            $insertrecord = new \stdClass();
            $insertrecord->module = $record->name;
            $insertrecord->instanceid = $record->instance;
            $insertrecord->courseid = $record->course;
            $insertrecord->contextid = $record->contextid;
            $insertrecord->timeend = $record->duedate;
            $insertrecord->endyear = $timeelements['endyear'];
            $insertrecord->endmonth = $timeelements['endmonth'];
            $insertrecord->endday = $timeelements['endday'];

            $toinsert[] = $insertrecord;

            if (count($toinsert) == $this->batchsize) {
                // Insert in database.
                $DB->insert_records('local_assessfreq_site', $toinsert);
                $toinsert = array(); // Reset array.
                $recordsprocessed += count($toinsert);
            }
        }

        // Insert any remaining records that don't make a full batch.
        if (count($toinsert) > 0) {
            $DB->insert_records('local_assessfreq_site', $toinsert);
            $recordsprocessed += count($toinsert);
        }

        $recordset->close();

        return $recordsprocessed;
    }

    /**
     * Process site events.
     * Get all events for modules and store results in the database.
     *
     * @param int $duedate The timestamp to start processing from.
     * @return int $recordsprocessed The number of records processed.
     */
    public function process_site_events(int $duedate) : int {
        $recordsprocessed = 0;
        $enabledmods = $this->get_modules(); // Get all enabled modules.

        // Itterate through modules.
        foreach ($enabledmods as $module) {
            $sql = $this->get_sql_query($module);
            $params = array($module, CONTEXT_MODULE, $duedate, 1, 1);
            $moduleevents = $this->get_module_events($sql, $params); // Get all events for module.
            $recordsprocessed += $this->process_module_events($moduleevents); // Store events.
        }
        return $recordsprocessed;
    }

    /**
     * Get all user IDs that a particular event applies to.
     *
     * @param int $contextid The context ID in a course for the event to check.
     * @param string $module The type of module the event is for.
     * @return array $users An array of user IDs.
     */
    private function get_event_users(int $contextid, string $module) : array {
        $context = \context::instance_by_id($contextid);
        $capabilities = $this->capabilitymap[$module];
        $users = array();

        foreach ($capabilities as $capability) {
            $enrolledusers = get_enrolled_users($context, $capability, 0, 'u.id');
            $users = array_replace($users, $enrolledusers);
        }

        return $users;
    }

    /**
     * Get stored events from a specified date.
     *
     * @param int $duedate The duedate to get events from.
     * @return \moodle_recordset Recordset of event info.
     */
    private function get_stored_events(int $duedate) : \moodle_recordset {
        global $DB;

        $select = 'timeend >= ?';
        $params = array($duedate);
        $recordset = $DB->get_recordset_select(
            'local_assessfreq_site',
            $select,
            $params,
            'timeend DESC',
            'id, contextid, module');

        return $recordset;
    }

    /**
     * Take and array of users and prepare them
     * for insertion into the database.
     *
     * @param array $users The array of users to link to the event.
     * @param int $eventid The related event id.
     * @return array $userrecords Array of objects ready to store in database.
     */
    private function prepare_user_event_records(array $users, int $eventid) : array {
        $userrecords = array();
        foreach ($users as $user) {
            $record = new \stdClass();
            $record->userid = $user->id;
            $record->eventid = $eventid;

            $userrecords[] = $record;
        }

        return $userrecords;
    }

    /**
     * Process user events.
     * Get all events for users and store results in the database.
     *
     * @param int $duedate The timestamp to start processing from.
     * @return int $recordsprocessed The number of records processed.
     */
    public function process_user_events(int $duedate) : int {
        global $DB;
        $recordsprocessed = 0;

        // Get recordset of site events where date is greater than now.
        // We don't care about updating events in the past.
        $eventset = $this->get_stored_events($duedate);
        foreach ($eventset as $event) {
            // For each site event get list of users that the event aplies to.
            $users = $this->get_event_users($event->contextid, $event->module);
            $userrecords = $this->prepare_user_event_records($users, $event->id);

            // Store result in database in a many-to-many table.
            $DB->insert_records('local_assessfreq_user', $userrecords);
            $recordsprocessed += count($userrecords);
        }
        $eventset->close();

        return $recordsprocessed;
    }

    /**
     * Delete processed events based on due date timestamp.
     *
     * @param int $duedate The unix timestamp to delete events from.
     */
    public function delete_events(int $duedate) : void {
        global $DB;
        $select = 'timeend >= ?';

        // We do the following in a transaction to maintain data consistency.
        try {
            $transaction = $DB->start_delegated_transaction();
            $userevents = $DB->get_fieldset_select('local_assessfreq_site', 'id', $select, array($duedate));

            // Delete site events.
            $DB->delete_records_select('local_assessfreq_site', $select, array($duedate));

            // Delete user events.
            if (!empty($userevents)) {
                list($insql, $inparams) = $DB->get_in_or_equal($userevents);
                $inselect = "eventid $insql";
                $DB->delete_records_select('local_assessfreq_user', $inselect, $inparams);
            }

            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Filter event dates by time period.
     * We do this PHP side not DB as we cache all the events.
     *
     * @param array $events List of event records to filter.
     * @param int $from Timestamp to filter from.
     * @param int $to Timestamp to fiter to.
     * @return array $filteredevents The list of filtered events.
     */
    private function filter_event_data(array $events, int $from, int $to=0) : array {
        $filteredevents = array();

        // If an explicit to date was not defined default to a year from now.
        if ($to == 0) {
            $to = time() + YEARSECS;
        }

        // Filter events.
        foreach ($events as $event) {
            if (($event->timeend >= $from) && ($event->timeend < $to)) {
                $filteredevents[] = $event;
            }
        }

        return $filteredevents;
    }

    /**
     * Get site events.
     * This is events across all courses.
     *
     * @param string $module The module to get events for or all events.
     * @param int $from The timestamp to get events from.
     * @param int $to The timestamp to get events to.
     * @param bool $cache If false cache won't be used fresh data will be retrieved from DB.
     * @return array $events An array of site events
     */
    public function get_site_events(string $module='all', int $from=0, int $to=0, bool $cache=true) : array {
        global $DB;
        $events = array();

        // Try to get value from cache.
        $sitecache = cache::make('local_assessfreq', 'siteevents');
        $data = $sitecache->get($module);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            // Only return data for chosen range.
            $events = $this->filter_event_data($data->events, $from, $to);
        } else {  // Not valid cache data.

            // Get data from database.
            if ($module == 'all') {
                $rawevents = $DB->get_records('local_assessfreq_site');
            } else {
                $rawevents = $DB->get_records('local_assessfreq_site', array('module' => $module));
            }

            $events = $this->filter_event_data($rawevents, $from, $to);

            // Update cache.
            if (!empty($rawevents)) {
                $expiry = time() + $this->expiryperiod;
                $data = new \stdClass();
                $data->expiry = $expiry;
                $data->events = $rawevents;
                $sitecache->set($module, $data);
            }
        }
        return $events;
    }

    /**
     * Return events for a given course.
     *
     * @param int $courseid Course ID to get events for.
     * @param string $module The module to get events for or all events.
     * @param int $from The timestamp to get events from.
     * @param int $to The timestamp to get events to.
     * @param bool $cache If false cache won't be used fresh data will be retrieved from DB.
     * @return array $events An array of site events
     */
    public function get_course_events(int $courseid, string $module='all', int $from=0, int $to=0, bool $cache=true) : array {
        global $DB;
        $events = array();
        $cachekey = (string)$courseid . '_' . $module;

        // Try to get value from cache.
        $coursecache = cache::make('local_assessfreq', 'courseevents');
        $data = $coursecache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            // Only return data for chosen range.
            $events = $this->filter_event_data($data->events, $from, $to);
        } else {  // Not valid cache data.

            // Get data from database.
            if ($module == 'all') {
                $rawevents = $DB->get_records('local_assessfreq_site', array('courseid' => $courseid));
            } else {
                $rawevents = $DB->get_records('local_assessfreq_site', array('module' => $module, 'courseid' => $courseid));
            }

            $events = $this->filter_event_data($rawevents, $from, $to);

            // Update cache.
            if (!empty($rawevents)) {
                $expiry = time() + $this->expiryperiod;
                $data = new \stdClass();
                $data->expiry = $expiry;
                $data->events = $rawevents;
                $coursecache->set($cachekey, $data);
            }
        }

        return $events;
    }

    /**
     * Return events for a given user.
     *
     * @param int $userid user ID to get events for.
     * @param string $module The module to get events for or all events.
     * @param int $from The timestamp to get events from.
     * @param int $to The timestamp to get events to.
     * @param bool $cache If false cache won't be used fresh data will be retrieved from DB.
     * @return array $events An array of site events
     */
    public function get_user_events(int $userid, string $module='all', int $from=0, int $to=0, bool $cache=true) : array {
        global $DB;
        $events = array();
        $cachekey = (string)$userid . '_' . $module;

        // Try to get value from cache.
        $usercache = cache::make('local_assessfreq', 'userevents');
        $data = $usercache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            // Only return data for chosen range.
            $events = $this->filter_event_data($data->events, $from, $to);
        } else {  // Not valid cache data.
            $sql = 'SELECT s.*
                      FROM {local_assessfreq_site} s
                INNER JOIN {local_assessfreq_user} u ON u.eventid = s.id
                     WHERE u.userid = ?';
            // Get data from database.
            if ($module == 'all') {
                $rawevents = $DB->get_records_sql($sql, array($userid));
            } else {
                $sql .= ' AND s.module = ?';
                $rawevents = $DB->get_records_sql($sql, array($userid, $module));
            }

            $events = $this->filter_event_data($rawevents, $from, $to);

            // Update cache.
            if (!empty($rawevents)) {
                $expiry = time() + $this->expiryperiod;
                $data = new \stdClass();
                $data->expiry = $expiry;
                $data->events = $rawevents;
                $usercache->set($cachekey, $data);
            }
        }

        return $events;
    }

    /**
     *
     * @param int $year
     * @param bool $cache
     * @return array
     */
    public function get_events_due_by_month(int $year, bool $cache=true): array {
        global $DB;
        $events = array();
        $cachekey = (string)$year ;

        // Try to get value from cache.
        $usercache = cache::make('local_assessfreq', 'eventsduemonth');
        $data = $usercache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            $events = $data;
        } else {  // Not valid cache data.
            $params = array($year);
            $sql = 'SELECT endmonth, COUNT(id)
                      FROM {local_assessfreq_site}
                     WHERE endyear = ?
                  GROUP BY endmonth
                  ORDER BY endmonth ASC';
            $events = $DB->get_records_sql($sql, $params);
        }

        // Update cache.
        if (!empty($events)) {
            $expiry = time() + $this->expiryperiod;
            $data = new \stdClass();
            $data->expiry = $expiry;
            $data->events = $events;
            $usercache->set($cachekey, $data);
        }

        return $events;
    }

    private function get_conflicts(int $now) : array {
        global $DB;
        $conflicts = array();

        // A conflict is an overlapping date range for two or more quizzes where the quiz has at least one common student.
        $eventsql = 'SELECT lasa.id as eventid, lasb.id as conflictid
                       FROM {local_assessfreq_site} lasa
                 INNER JOIN {local_assessfreq_site} lasb ON (lasa.timestart > lasb.timestart AND lasa.timestart < lasb.timeend)
                                                         OR (lasa.timeend > lasb.timestart AND lasa.timeend < lasb.timeend)
                                                         OR (lasa.timeend > lasb.timeend AND lasa.timestart < lasb.timestart)
                      WHERE lasa.module = ?
                            AND lasb.module = ?
                            AND lasa.timestart > ?';
        $eventparams = array('quiz', 'quiz', $now, $now);
        $recordset = $DB->get_recordset_sql($eventsql, $eventparams);

        foreach ($recordset as $record) {
            $usersql = 'SELECT DISTINCT laua.userid
                          FROM {local_assessfreq_user} laua
                    INNER JOIN {local_assessfreq_user} laub on laua.userid = laub.userid
                         WHERE laua.eventid = ?
                               AND laub.eventid = ?';

            $userparams = array($record->eventid, $record->conflictid);
            $users = $DB->get_fieldset_sql($usersql, $userparams);

            if (!empty($users)) {
                $conflict = new \stdClass();
                $conflict->eventid = $record->eventid;
                $conflict->conflictid = $record->conflictid;
                $conflict->users = $users;

                $conflicts[] = $conflict;
            }
        }
        $recordset->close();

        return $conflicts;
    }

    public function process_conflicts() : array {

        // Final result should look like this.
        $conflicts['eventid'] = array(
            array(
                'conflicteventid' => 123,
                'effecteduserids' => array(1,2,3)
            ),
            array(
                'conflicteventid' => 456,
                'effecteduserids' => array(4,5,6)
            ),
        );

        return $conflicts;
    }

}
