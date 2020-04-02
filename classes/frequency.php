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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

namespace local_assessfreq;

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
        'choice' =>  array(),
        'data' =>  array(),
        'feedback' =>  array(),
        'forum' =>  array(),
        'lesson' => array(),
        'quiz' =>  array(),
        'scorm' =>  array(),
        'workshop' =>  array()
    );


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

        foreach($this->map as $key => $value) {
            if($count > $key) {
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
            // Iterate through the records and insert to database in batches
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

            if(count($toinsert) == $this->batchsize){
                // Insert in database.
                $DB->insert_records('local_assessfreq_site', $toinsert);
                $toinsert = array(); // Reset array.
                $recordsprocessed += count($toinsert);
            }
        }

        // Insert any remaining records that don't make a full batch
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
     * Process user events.
     * Get all events for users and store results in the database.
     *
     * @param int $duedate The timestamp to start processing from.
     * @return int $recordsprocessed The number of records processed.
     */
    public function process_user_events(int $duedate) : int {
        $recordsprocessed = 0;

        // Get recordset of site events where date is greater than now.
        // We don't care about updating events in the past.

        // For each site event get list of users that the event aplies to.

        // Store result in database in a many-to-many table.

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
        $DB->delete_records_select('local_assessfreq_site', $select, array($duedate));
    }
}
