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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frequency {

    /**
     * The due date databse field differs between module types.
     * This map provides the translation.
     *
     * @var array $modduefield
     */
    private $moduleendfield = array (
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
     * The start date databse field differs between module types.
     * This map provides the translation.
     *
     * @var array $modduefield
     */
    private $modulestartfield = array (
        'assign' => 'allowsubmissionsfromdate',
        'choice' => 'timeopen',
        'data' => 'timeavailablefrom',
        'feedback' => 'timeopen',
        'forum' => null,
        'lesson' => 'available',
        'quiz' => 'timeopen',
        'scorm' => 'timeopen',
        'workshop' => 'submissionstart'
    );

    /**
     * The time limit databse field differs between module types and only some support it.
     * This map provides the translation
     *
     * @var array $moduletimelimit
     */
    private $moduletimelimit = array (
        'leesson' => 'timelimit',
        'quiz' => 'timelimit',

    );


    /**
     * Map of capabilities that users must have
     * before that activity event applies to them.
     *
     * @var array $capabilitymap
     */
    private $capabilitymap = array (
        'assign' => array('mod/assign:submit', 'mod/assign:view'),
        'choice' => array('mod/choice:choose', 'mod/choice:view'),
        'data' => array('mod/data:writeentry', 'mod/data:viewentry', 'mod/data:view'),
        'feedback' => array('mod/feedback:complete', 'mod/feedback:viewanalysepage', 'mod/feedback:view'),
        'forum' => array(
            'mod/forum:startdiscussion', 'mod/forum:createattachment', 'mod/forum:replypost', 'mod/forum:viewdiscussion'),
        'lesson' => array('mod/lesson:view'),
        'quiz' => array('mod/quiz:attempt', 'mod/quiz:view'),
        'scorm' => array('mod/scorm:savetrack', 'mod/scorm:viewscores'),
        'workshop' => array('mod/workshop:submit', 'mod/workshop:view')
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
     * Get the modules to use in data collection.
     * This is based on plugin configuration.
     *
     * @return array $modules The enabled modules.
     */
    public function get_modules() : array {
        $version = get_config('moodle', 'version');

        // Start with a hardcoded list of modules. As there is not a good way to get a list of suppoerted modules.
        // Different versions of Moodle have different supported modules. This is an anti pattern, but yeah...
        if ($version < 2019052000) { // Versions less than 3.7 don't support forum due dates.
            $availablemodules = array ('assign', 'choice', 'data', 'feedback', 'lesson', 'quiz', 'scorm', 'workshop');
        } else {
            $availablemodules = array ('assign', 'choice', 'data', 'feedback', 'forum', 'lesson', 'quiz', 'scorm', 'workshop');
        }

        return $availablemodules;
    }

    /**
     * Given a modle shortname get capabilities that users must have
     * before that activity event applies to them.
     *
     * @param string $module The module to get the capability for.
     * @return array Capabilities relating to the module.
     */
    public function get_module_capabilities(string $module): array {
        return $this->capabilitymap[$module];
    }

    /**
     * Get currently enabled modules from the Moodle DB.
     *
     * @return array $modules The enabled modules.
     */
    public function get_enabled_modules(): array {
        global $DB;

        $modules = $DB->get_records_menu('modules', array(), '', 'name, visible');

        return $modules;
    }

    /**
     * Return a list of modules to process.
     *
     * Get list of enabled courses from config.
     * If we are including disabled activities just got with the config list.
     * If we are not including disabled activites, remove the disabled ones from the config list.
     *
     * @return array $modules Lis of modules to process.
     */
    public function get_process_modules(): array {
        $config = get_config('local_assessfreq');
        $modules = explode(',', $config->modules);
        $disabledmodules = $config->disabledmodules;

        if (!$disabledmodules) {
            $enabledmodules = $this->get_enabled_modules();

            foreach ($modules as $index => $module) {
                if (empty($enabledmodules[$module])) {
                    unset($modules[$index]);
                }
            }
        }

        return $modules;
    }

    /**
     * Generate SQL to use to get activity info.
     *
     * @param string $module Activity module to get data for.
     * @return string $sql The generated SQL.
     */
    private function get_sql_query(string $module) : string {
        $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');

        $duedate = $this->moduleendfield[$module];
        $sql = 'SELECT cm.id, cm.course, m.name, cm.instance, c.id as contextid, a.' . $duedate . ' AS duedate ';

        if (!empty($this->modulestartfield[$module])) {
            $startdate = $this->modulestartfield[$module];
            $sql .= ', a.' . $startdate . ' AS startdate ';
        }

        if (!empty($this->moduletimelimit[$module])) {
            $timelimit = $this->moduletimelimit[$module];
            $sql .= ', a.' . $timelimit . ' AS timelimit ';
        }

        $sql .= 'FROM {course_modules} cm
           INNER JOIN {modules} m ON cm.module = m.id
           INNER JOIN {context} c ON cm.id = c.instanceid
           INNER JOIN {' . $module . '} a ON cm.instance = a.id
           INNER JOIN {course} course ON cm.course = course.id
                WHERE m.name = ?
                      AND c.contextlevel = ?
                      AND a.' . $duedate . ' >= ?
                      AND cm.visible = ?';

        if (!$includehiddencourses) {
            $sql .= ' AND course.visible = ?';
        }

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
     * Remove existing data related to event from database.
     * The event date may have been changed from in the past to in the future. In this case it may
     * not have been picked up by the delete records process. This method removes it a processing time.
     *
     * @param \stdClass $record The record to process.
     */
    private function cleanup_record(\stdClass $record): void {
        global $DB;

        $params = array('module' => $record->module, 'instanceid' => $record->instanceid);
        $id = $DB->get_field('local_assessfreq_site', 'id', $params);

        if ($id) {
            $DB->delete_records('local_assessfreq_site', array('id' => $id));
            $DB->delete_records('local_assessfreq_user', array('eventid' => $id));
        }

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

            if (empty($record->startdate)) {
                $record->startdate = 0;
            }

            if (empty($record->timelimit)) {
                $record->timelimit = 0;
            }

            // Iterate through the records and insert to database in batches.
            $timeelements = $this->format_time($record->duedate);
            $insertrecord = new \stdClass();
            $insertrecord->module = $record->name;
            $insertrecord->instanceid = $record->instance;
            $insertrecord->courseid = $record->course;
            $insertrecord->contextid = $record->contextid;
            $insertrecord->timestart = $record->startdate;
            $insertrecord->timeend = $record->duedate;
            $insertrecord->timelimit = $record->timelimit;
            $insertrecord->endyear = $timeelements['endyear'];
            $insertrecord->endmonth = $timeelements['endmonth'];
            $insertrecord->endday = $timeelements['endday'];

            $toinsert[] = $insertrecord;

            $this->cleanup_record($insertrecord);

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
        $enabledmods = $this->get_process_modules();
        $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');

        if (!empty($enabledmods[0])) {
            // Itterate through modules.
            foreach ($enabledmods as $module) {
                $sql = $this->get_sql_query($module);
                if ($includehiddencourses) {
                    $params = array($module, CONTEXT_MODULE, $duedate, 1);
                } else {
                    $params = array($module, CONTEXT_MODULE, $duedate, 1, 1);
                }

                $moduleevents = $this->get_module_events($sql, $params); // Get all events for module.
                $recordsprocessed += $this->process_module_events($moduleevents); // Store events.
            }
        }

        return $recordsprocessed;
    }

    /**
     * Generate the where sql fragments, join clauses and params arrray to
     * get the enrolled users with given capabilities for a given context.
     * Used to generte SQL for getting users in assessments.
     *
     * @param \context $context The context to get the enrolled users for.
     * @param array $capabilities The capabilities that users need to have.
     * @return array
     */
    public function generate_enrolled_wheres_joins_params(\context $context, array $capabilities): array {
        $uid = 'u.id';
        $joins = array();
        $wheres = array();

        $enrolledjoin = get_enrolled_join($context, $uid, true);
        $joins[] = $enrolledjoin->joins;
        $wheres[] = $enrolledjoin->wheres;
        $params = $enrolledjoin->params;

        $capjoin = get_with_capability_join($context, $capabilities, $uid);
        $joins[] = $capjoin->joins;
        $wheres[] = $capjoin->wheres;
        $params = array_merge($params, $capjoin->params);

        $joins = implode("\n", $joins);
        $wheres[] = "u.deleted = 0";
        $wheres = implode(" AND ", $wheres);

        $wherejoin = array($joins, $wheres, $params);

        return $wherejoin;
    }

    /**
     * Our own implementation of get_enrolled_users. Allows us to check multiple capabilities
     * in less database queries.
     *
     * @param \context $context The context to get the enrolled users for.
     * @param array $capabilities The capabilities that users need to have.
     * @return array Enrolled user records
     */
    private function get_enrolled_users(\context $context, array $capabilities): array {
        global $DB;

        list($joins, $wheres, $params) = $this->generate_enrolled_wheres_joins_params($context, $capabilities);

        $finaljoin = new \core\dml\sql_join($joins, $wheres, $params);

        $sql = "SELECT DISTINCT u.id
              FROM {user} u
            $finaljoin->joins
             WHERE $finaljoin->wheres";
        $params = $finaljoin->params;

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get all user IDs that a particular event applies to.
     * This method gets "fresh" records from the Moodle Databsae. Processing for
     * this can take a long time. Consider using the get_event_users method
     * if you don't need the most up to date data.
     *
     * @param int $contextid The context ID in a course for the event to check.
     * @param string $module The type of module the event is for.
     * @return array $users An array of user IDs.
     */
    public function get_event_users_raw(int $contextid, string $module): array {
        $context = \context::instance_by_id($contextid);
        $capabilities = $this->get_module_capabilities($module);

        $users = $this->get_enrolled_users($context, $capabilities);

        return $users;
    }

    /**
     * Get all user IDs that a particular event applies to.
     * This mehod uses the preprocessed event and user data from the plugin tables.
     *
     * @param int $contextid The context ID in a course for the event to check.
     * @param string $module The type of module the event is for.
     * @param bool $cache If false cache won't be used fresh data will be retrieved from DB.
     * @return array $users Array of user ids.
     */
    public function get_event_users(int $contextid, string $module, bool $cache=true): array {
        global $DB;
        $users = array();
        $cachekey = (string)$contextid . '_' . $module;

        // Try to get value from cache.
        $usercache = cache::make('local_assessfreq', 'eventusers');
        $data = $usercache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            // Only return data for chosen range.
            $users = $data->users;
        } else {  // Not valid cache data.
            $sql = 'SELECT u.userid as id
                      FROM {local_assessfreq_user} u
                INNER JOIN {local_assessfreq_site} s ON u.eventid = s.id
                     WHERE s.contextid = ?
                           AND s.module = ?';
            $params = array($contextid, $module);

            $users = $DB->get_records_sql($sql, $params);

            // Update cache.
            if (!empty($users)) {
                $expiry = time() + $this->expiryperiod;
                $data = new \stdClass();
                $data->expiry = $expiry;
                $data->users = $users;
                $usercache->set($cachekey, $data);
            }
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
            $users = $this->get_event_users_raw($event->contextid, $event->module);
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
                $chunks = array_chunk($userevents, 30000);
                foreach ($chunks as $usereventschunk) {
                    list($insql, $inparams) = $DB->get_in_or_equal($usereventschunk);
                    $inselect = "eventid $insql";
                    $DB->delete_records_select('local_assessfreq_user', $inselect, $inparams);
                }
            }

            $transaction->allow_commit();

        } catch (\Exception $e) {
            $transaction->rollback($e);
        }
    }

    /**
     * Delete processed event.
     *
     * @param \stdClass $event The event to delete.
     */
    public function delete_event(\stdClass $event): void {
        global $DB;

        // We do the following in a transaction to maintain data consistency.
        try {
            $transaction = $DB->start_delegated_transaction();

            // Delete site events.
            $DB->delete_records('local_assessfreq_site', array('id' => $event->id));
            $DB->delete_records('local_assessfreq_user', array('eventid' => $event->id));

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
    private function filter_event_data($events, int $from, int $to=0) : array {
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

            $sql = "SELECT s.*
                          FROM {local_assessfreq_site} s
                     LEFT JOIN {course} c ON s.courseid = c.id";

            // Get data from database.
            if ($module == 'all') {
                $modules = $this->get_process_modules();
                list($insql, $params) = $DB->get_in_or_equal($modules);
                $sql .= " WHERE s.module $insql";

            } else {
                $params = array($module);
                $sql .= " WHERE s.module = ?";
            }

            $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');
            if (!$includehiddencourses) {
                $params[] = 1;
                $sql .= " AND c.visible = ?";
            }

            $rawevents = $DB->get_records_sql($sql, $params);
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
     * Get all events that are ending on a given date.
     *
     * @param string $date The end date for the event.
     * @param string $module The module to get events for or all events.
     *
     * @return array $events An array of site events
     */
    public function get_day_ending_events(string $date, string $module='all'): array {
        global $DB;
        $events = array();

        // TODO: Think about some caching here.
        // TODO: Improve unit test coverage for this.

        list($year, $month, $day) = explode('-', $date);
        $from = 0;
        $tostart = mktime(0, 0, 0, $month, $day, $year);
        $toend = mktime(23, 59, 59, $month, $day, $year);

        $sql = "SELECT s.*
                  FROM {local_assessfreq_site} s
             LEFT JOIN {course} c ON s.courseid = c.id";

        // Get data from database.
        if ($module == 'all') {
            $modules = $this->get_process_modules();
            list ($insql, $params) = $DB->get_in_or_equal($modules);
            $sql .= " WHERE s.module $insql";
        } else {
            $params = array(
                $module
            );
            $sql .= " WHERE s.module = ?";
        }

        $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');
        if (! $includehiddencourses) {
            $params[] = 1;
            $sql .= " AND c.visible = ?";
        }

        // Date sorting.
        $sql .= " AND s.timestart >= ?
                  AND s.timeend >= ?
                  AND s.timeend <= ?";
        $params[] = $from;
        $params[] = $tostart;
        $params[] = $toend;

        $events = $DB->get_records_sql($sql, $params);

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
                INNER JOIN {course} c ON s.courseid = c.id
                     WHERE u.userid = ?';
            // Get data from database.
            if ($module == 'all') {
                $params = array($userid);
            } else {
                $params = array($userid, $module);
                $sql .= ' AND s.module = ?';
            }

            $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');
            if (!$includehiddencourses) {
                $params[] = 1;
                $sql .= " AND c.visible = ?";
            }

            $rawevents = $DB->get_records_sql($sql, $params);
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
     * Return events for all users.
     *
     * @param string $module The module to get events for or all events.
     * @param int $from The timestamp to get events from.
     * @param int $to The timestamp to get events to.
     * @return array $events An array of site events
     */
    public function get_user_events_all(string $module='all', int $from=0, int $to=0) : Iterable {
        global $DB;

        $rowkey = $DB->sql_concat('s.id', "'_'", 'u.userid');
        $sql = "SELECT $rowkey as myrow, u.userid, s.*
                 FROM {local_assessfreq_site} s
           INNER JOIN {local_assessfreq_user} u ON u.eventid = s.id
           INNER JOIN {course} c ON s.courseid = c.id";

        // Get data from database.
        if ($module == 'all') {
            $modules = $this->get_process_modules();
            list($insql, $params) = $DB->get_in_or_equal($modules);
            $sql .= " WHERE s.module $insql";

        } else {
            $params = array($module);
            $sql .= ' WHERE s.module = ?';
        }

        $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');
        if (!$includehiddencourses) {
            $params[] = 1;
            $sql .= " AND c.visible = ?";
        }

        // If an explicit to date was not defined default to a year from now.
        if ($to === 0) {
            $to = time() + YEARSECS;
        }

        $params[] = $from;
        $params[] = $to;
        $sql .= " AND s.timeend >= ? AND s.timeend < ?";

        $events = $DB->get_recordset_sql($sql, $params);

        return $events;
    }

    /**
     * Get events for a given year, grouped by month.
     *
     * @param int $year The year to get the events for.
     * @param bool $cache Fetch events from cache.
     * @return array $events The events.
     */
    public function get_events_due_by_month(int $year, bool $cache=true): array {
        global $DB;
        $events = array();
        $cachekey = (string)$year;

        // Try to get value from cache.
        $usercache = cache::make('local_assessfreq', 'eventsduemonth');
        $data = $usercache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            $events = $data->events;
        } else {  // Not valid cache data.
            $modules = $this->get_process_modules();
            list($insql, $params) = $DB->get_in_or_equal($modules);
            $params[] = $year;
            $sql = "SELECT s.endmonth, COUNT(s.id) as count
                      FROM {local_assessfreq_site} s
                 LEFT JOIN {course} c ON s.courseid = c.id
                     WHERE s.module $insql
                           AND s.endyear = ?";

            $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');
            if (!$includehiddencourses) {
                $params[] = 1;
                $sql .= " AND c.visible = ? ";
            }

            $sql .= 'GROUP BY s.endmonth
                     ORDER BY s.endmonth ASC';

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

    /**
     * Get count of users who have an event for a given year grouped by month.
     *
     * @param int $year The year to get the events for.
     * @param bool $cache Fetch events from cache.
     * @return array $events The events.
     */
    public function get_events_due_monthly_by_user(int $year, bool $cache=true): array {
        global $DB;
        $events = array();
        $cachekey = (string)$year;

        // Try to get value from cache.
        $usercache = cache::make('local_assessfreq', 'monthlyuser');
        $data = $usercache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            $events = $data->events;
        } else {  // Not valid cache data.
            $modules = $this->get_process_modules();
            list($insql, $params) = $DB->get_in_or_equal($modules);
            $params[] = $year;
            $sql = "SELECT s.endmonth, COUNT(u.id) as count
                      FROM {local_assessfreq_site} s
                INNER JOIN {local_assessfreq_user} u ON s.id = u.eventid
                INNER JOIN {course} c ON s.courseid = c.id
                     WHERE s.module $insql
                           AND s.endyear = ?";

            $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');
            if (!$includehiddencourses) {
                $params[] = 1;
                $sql .= " AND c.visible = ? ";
            }

            $sql .= 'GROUP BY s.endmonth
                     ORDER BY s.endmonth ASC';

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

    /**
     * Get count of assessments who have an event for a given year grouped by month.
     *
     * @param int $year The year to get the events for.
     * @param bool $cache Fetch events from cache.
     * @return array $events The events.
     */
    public function get_events_due_by_activity(int $year, bool $cache=true): array {
        global $DB;
        $events = array();
        $cachekey = (string)$year . '_activity';

        // Try to get value from cache.
        $usercache = cache::make('local_assessfreq', 'eventsdueactivity');
        $data = $usercache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            $events = $data->events;
        } else { // Not valid cache data.
            $params = array($year);
            $sql = 'SELECT s.module, COUNT(s.id) as count
                      FROM {local_assessfreq_site} s
                 LEFT JOIN {course} c ON s.courseid = c.id
                     WHERE s.endyear = ?';

            $includehiddencourses = get_config('local_assessfreq', 'hiddencourses');
            if (!$includehiddencourses) {
                 $params[] = 1;
                 $sql .= " AND c.visible = ? ";
            }

            $sql .= 'GROUP BY s.module
                     ORDER BY s.module ASC';

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

    /**
     * Get list of years that have events.
     *
     * @param bool $cache Fetch events from cache.
     * @return array $years The years with events.
     */
    public function get_years_has_events(bool $cache=true): array {
        global $DB;
        $years = array();
        $cachekey = 'yearevents';

        // Try to get value from cache.
        $usercache = cache::make('local_assessfreq', 'yearevents');

        $data = $usercache->get($cachekey);

        if ($data && (time() < $data->expiry) && $cache) { // Valid cache data.
            $years = $data->events;
        } else {  // Not valid cache data.
            $sql = 'SELECT DISTINCT endyear FROM {local_assessfreq_site} ORDER BY endyear ASC';
            $yearrecords = $DB->get_records_sql($sql);
            $years = array_keys($yearrecords);
        }

        // Update cache.
        if (!empty($years)) {
            $expiry = time() + $this->expiryperiod;
            $data = new \stdClass();
            $data->expiry = $expiry;
            $data->events = $years;
            $usercache->set($cachekey, $data);
        }

        return $years;
    }

    /**
     * Get all events on a particular day.
     *
     * @param string $date A string representations of the date to get events for.
     * @param array $modules The modules to get events for.
     * @return array $dayevents The list of events that day.
     */
    public function get_day_events(string $date, array $modules): array {
        $dayevents = array();

        if (empty($modules)) {
            $modules = array('all');
        }

        // Get the raw events.
        if (in_array('all', $modules)) {
            $events = $this->get_day_ending_events($date, 'all');
        } else {
            // Work through the event array.
            foreach ($modules as $module) {
                if ($module == 'all') {
                    continue;
                } else {
                    $events = array_merge($events, $this->get_day_ending_events($date, $module));
                }
            }
        }

        // Get additional information and format the event data.
        foreach ($events as $event) {
            $context = \context::instance_by_id($event->contextid, IGNORE_MISSING);
            $course = get_course($event->courseid);

            if ($context) {
                $event->name = $context->get_context_name();
                $event->url = $context->get_url()->out();
                $event->usercount = count($this->get_event_users($event->contextid, $event->module));
                $event->timelimit =
                    ($event->timelimit == 0) ? get_string('na', 'local_assessfreq') : round(($event->timelimit / 60));

                if ($event->module == 'quiz') {
                    $dashurl = new \moodle_url('/local/assessfreq/dashboard_quiz.php', array('id' => $event->instanceid));
                    $event->dashurl = $dashurl->out();
                }

                $event->courseshortname = $course->shortname;

                $dayevents[] = $event;
            } else {
                // Context has been removed which means event has been deleted.
                // Remove corresponding event.
                $this->delete_event($event);
            }
        }

        // Sort results if we have more than one.
        if (count($dayevents) > 1) {
            $sorton = array(
                    'timestart' => 'ASC',
                    'courseshortname' => 'ASC',
                    'name' => 'ASC'
            );
            $dayevents = \local_assessfreq\utils::multi_sort($dayevents, $sorton);

            $dayevents = array_values($dayevents);
        }

        return $dayevents;
    }

    /**
     * Generate a frequency array of the events.
     * The form of the array is:
     * [yyyy][mm][dd]['number'] = number of events that day.
     *
     * @param int $year The year to get events for.
     * @param string $metric The metric to get 'students' or 'assess'.
     * @param array $modules List of modules to get events for.
     * @return array $freqarray The array of even frequencies.
     */
    public function get_frequency_array(int $year, string $metric, array $modules) : array {
        $freqarray = array();
        $events = array();
        $from = mktime(0, 0, 0, 1, 1, $year);
        $to = mktime(23, 59, 59, 12, 31, $year);
        $userfreqarraycache = cache::make('local_assessfreq', 'usereventsallfrequencyarray');
        sort($modules);
        $cachekey = implode("_", $modules) . '_' . (string)$from . '_' . (string)$to;

        if ($metric == 'assess') {
            $functionname = 'get_site_events';
        } else if ($metric == 'students') {
            $data = $userfreqarraycache->get($cachekey);
            if ($data && $metric == 'students' && (time() < $data->expiry)) {
                return $data->freqarray;
            }

            $functionname = 'get_user_events_all';
        }

        if (empty($modules)) {
            $modules = array('all');
        }

        // Get the raw events.
        if (in_array('all', $modules)) {
            $events = $this->$functionname('all', $from, $to);
        } else {
            // Work through the event array.
            foreach ($modules as $module) {
                $records = $this->$functionname($module, $from, $to);
                foreach ($records as $record) {
                    $events[] = $record;
                }
            }
        }

        // Iterate through the events, building the frequency array.
        foreach ($events as $event) {
            $month = $event->endmonth;
            $day = $event->endday;
            $module = $event->module;

            // Construct the multidimensional array.
            if (empty($freqarray[$year][$month][$day])) {
                $freqarray[$year][$month][$day] = array('number' => 1);
            } else {
                $freqarray[$year][$month][$day]['number']++;
            }

            // Add the event counts.
            if (empty($freqarray[$year][$month][$day][$module])) {
                $freqarray[$year][$month][$day][$module] = 1;
            } else {
                $freqarray[$year][$month][$day][$module]++;
            }
        }

        /*
         * On large sites, the number of rows returned by get_user_events_all
         * can go in to the millions. So caching the events isn't reasonable
         * (and the time to reconstruct the frequency array will still be slow).
         * Instead, cache the result of computing the frequency array.
         */
        if ($functionname == 'get_user_events_all') {
            $expiry = time() + $this->expiryperiod;
            $data = new \stdClass();
            $data->expiry = $expiry;
            $data->freqarray = $freqarray;
            $userfreqarraycache->set($cachekey, $data);
        }

        return $freqarray;
    }

    /**
     * Get data for file download export.
     *
     * @param int $year The year to get the data for.
     * @param string $metric The type of metric to get 'assess' or 'student'.
     * @param array $modules The modules to get.
     * @return array $data The data for the download file.
     */
    public function get_download_data(int $year, string $metric, array $modules) : array {
        global $DB;

        $data = array();
        $events = array();
        $from = mktime(0, 0, 0, 1, 1, $year);
        $to = mktime(23, 59, 59, 12, 31, $year);

        if ($metric == 'assess') {
            $functionname = 'get_site_events';
        } else if ($metric == 'students') {
            $functionname = 'get_user_events_all';
        }

        if (empty($modules)) {
            $modules = array('all');
        }

        // Get the raw events.
        if (in_array('all', $modules)) {
            $events = $this->$functionname('all', $from, $to);
        } else {
            // Work through the event array.
            foreach ($modules as $module) {
                if ($module == 'all') {
                    continue;
                } else {
                    $events = array_merge($events, $this->$functionname($module, $from, $to));
                }
            }
        }

        // Format the data ready for download.
        foreach ($events as $event) {
            $row = array();

            // Catch exception when context does not exist because assessfreq tables are out of sync.
            try {
                $context = \context::instance_by_id($event->contextid);
            } catch (\dml_missing_record_exception $ex) {
                continue;
            }

            $activity = get_string('modulename', $event->module);
            $startdate = userdate($event->timestart, get_string('strftimedatetimeshort', 'langconfig'));
            $duedate = userdate($event->timeend, get_string('strftimedatetimeshort', 'langconfig'));
            $name = $context->get_context_name();
            $url = $context->get_url()->out(false);

            if ($metric == 'assess') {
                $usercount = count($this->get_event_users($event->contextid, $event->module));
                $row = array($startdate, $duedate, $activity, $name, $url, $usercount);
            } else if ($metric == 'students') {
                $extrafields = \core_user\fields::for_identity($context, false);
                $selects = $extrafields->get_sql()->selects;

                $sql = "SELECT CONCAT(firstname, ' ', lastname) $selects
                          FROM {user}
                         WHERE id = ?";
                $params = [$event->userid];
                $userfields = $DB->get_record_sql($sql, $params);

                $row = array($startdate, $duedate, $activity, $name, $url);

                foreach ($userfields as $userfield) {
                    $row[] = $userfield;
                }
            }
            $data[] = $row;
        }

        return $data;
    }

    /**
     * Get heat colors to use id nheatmap display from plugin configuration.
     *
     * @return array
     */
    public function get_heat_colors(): array {
        $config = get_config('local_assessfreq');

        $heatcolors = array(
            1 => $config->heat1,
            2 => $config->heat2,
            3 => $config->heat3,
            4 => $config->heat4,
            5 => $config->heat5,
            6 => $config->heat6
        );

        return $heatcolors;
    }

    /**
     * Purge all plugin caches.
     * This is invoked when a plugin setting is changed.
     *
     * @param string $name Name of the setting change that invoked the purge.
     */
    public static function purge_caches($name): void {
        global $CFG;

        // Get plugin cache definitions.
        $definitions = array();
        include($CFG->dirroot . '/local/assessfreq/db/caches.php');
        $definitionnames = array_keys($definitions);

        // Clear each cache.
        foreach ($definitionnames as $definitionname) {
            $cache = cache::make('local_assessfreq', $definitionname);
            $cache->purge();
        }
    }

    /**
     * Get assessment conflicts.
     *
     * @param int $now The timestamp to get the conflicts for.
     * @return array $conflicts The conflict data.
     */
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

    /**
     * Process the conflicts.
     *
     * @return array $conflicts Conflict data.
     */
    public function process_conflicts() : array {

        // Final result should look like this.
        $conflicts['eventid'] = array(
            array(
                'conflicteventid' => 123,
                'effecteduserids' => array(1, 2, 3)
            ),
            array(
                'conflicteventid' => 456,
                'effecteduserids' => array(4, 5, 6)
            ),
        );

        return $conflicts;
    }

}
