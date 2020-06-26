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
 * This file contains the class that handles testing of the block assess frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/calendar/tests/helpers.php');

use local_assessfreq\frequency;

/**
 * This file contains the class that handles testing of the block assess frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frequency_testcase extends advanced_testcase {

    /**
     *
     * @var stdClass $course Test course.
     */
    protected $course;

    /**
     *
     * @var stdClass First test assign.
     */
    protected $assign1;

    /**
     *
     * @var stdClass Second test assign.
     */
    protected $assign2;

    /**
     *
     * @var stdClass First test user.
     */
    protected $user1;

    /**
     *
     * @var stdClass Second test user.
     */
    protected $user2;

    /**
     * Set up conditions for tests.
     */
    public function setUp() {
        $this->resetAfterTest();

        global $CFG;

        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => 1),
            array('createsections' => true));
        $assignrow1 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585359375
        ));
        $assignrow2 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585445775
        ));
        $this->assign1 = new assign(context_module::instance($assignrow1->cmid), false, false);
        $this->assign2 = new assign(context_module::instance($assignrow2->cmid), false, false);
        $this->course = $course;

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Enrol users into the course.
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');

        $this->user1 = $user1;
        $this->user2 = $user2;

        set_config('modules', 'quiz,assign,scorm,choice', 'local_assessfreq');
    }

    /**
     * Test getting a modules events.
     */
    public function test_get_module_events() {
        $sql = 'SELECT cm.id, cm.course, m.name, cm.instance, c.id as contextid, a.duedate
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON cm.module = m.id
            INNER JOIN {context} c ON cm.id = c.instanceid
            INNER JOIN {assign} a ON cm.instance = a.id
            INNER JOIN {course} course ON cm.course = course.id
                 WHERE m.name = ?
                       AND c.contextlevel = ?
                       AND a.duedate > ?
                       AND cm.visible = ?
                       AND course.visible = ?';
        $params = array('assign', CONTEXT_MODULE, 0, 1, 1);

        $frequency = new frequency();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'get_module_events');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($frequency, $sql, $params);
        $contextids = array($this->assign1->get_context()->id, $this->assign2->get_context()->id);

        foreach ($result as $record) {
            $this->assertEquals($this->course->id, $record->course);
            $this->assertContains($record->contextid, $contextids);
            $this->assertEquals('assign', $record->name);
        }
        $result->close();

    }

    /**
     * Test format time method.
     */
    public function test_format_time() {
        $frequency = new frequency();
        $timestamp = 1585445775;

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'format_time');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($frequency, $timestamp);

        $this->assertEquals(2020, $result['endyear']);
        $this->assertEquals(03, $result['endmonth']);
        $this->assertEquals(29, $result['endday']);
    }

    /**
     * Test process module events method.
     */
    public function test_process_module_events() {

        global $DB;
        $frequency = new frequency();

        $sql = 'SELECT cm.id, cm.course, m.name, cm.instance, c.id as contextid, a.duedate, a.allowsubmissionsfromdate AS startdate
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON cm.module = m.id
            INNER JOIN {context} c ON cm.id = c.instanceid
            INNER JOIN {assign} a ON cm.instance = a.id
            INNER JOIN {course} course ON cm.course = course.id
                 WHERE m.name = ?
                       AND c.contextlevel = ?
                       AND a.duedate > ?
                       AND cm.visible = ?
                       AND course.visible = ?';
        $params = array('assign', CONTEXT_MODULE, 0, 1, 1);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'get_module_events');
        $method->setAccessible(true); // Allow accessing of private method.

        $recordset = $method->invoke($frequency, $sql, $params);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'process_module_events');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($frequency, $recordset);
        $this->assertEquals(2, $result); // Check the expected number of records inserted.

        // Check actual records in the DB.
        $record1 = $DB->get_record('local_assessfreq_site', array('instanceid' => $this->assign1->get_course_module()->instance));
        $record2 = $DB->get_record('local_assessfreq_site', array('instanceid' => $this->assign2->get_course_module()->instance));

        $this->assertEquals(28, $record1->endday);
        $this->assertEquals(29, $record2->endday);
    }

    /**
     * Test process site events method.
     */
    public function test_process_site_events() {
        global $DB;

        $now = 1585359400;
        $frequency = new frequency();
        $result = $frequency->process_site_events($now);

        $this->assertEquals(1, $result);

        // Check actual records in the DB.
        $record = $DB->get_record('local_assessfreq_site', array('instanceid' => $this->assign2->get_course_module()->instance));
        $this->assertEquals(29, $record->endday);
    }

    /**
     * Test process site events method.
     */
    public function test_delete_events() {
        global $DB;

        $duedate = 0;
        $frequency = new frequency();
        $result = $frequency->process_site_events($duedate);
        $frequency->process_user_events($duedate);

        $this->assertEquals(2, $result);

        // Delete events from date.
        $now = 1585359375;
        $frequency->delete_events($now);

        $count1 = $DB->count_records('local_assessfreq_site');
        $this->assertEquals(0, $count1); // Should be no records.

        $count2 = $DB->count_records('local_assessfreq_user');
        $this->assertEquals(0, $count2); // Should be no records.

        $result = $frequency->process_site_events($duedate);
        $frequency->process_user_events($duedate);
        $now = 1585359400;
        $frequency->delete_events($now);

        $count1 = $DB->count_records('local_assessfreq_site');
        $this->assertEquals(1, $count1); // Should be one record.

        $count2 = $DB->count_records('local_assessfreq_user');
        $this->assertEquals(2, $count2); // Should be two record.
    }

    /**
     * Test process getting events for users.
     */
    public function test_get_event_users() {
        $frequency = new frequency();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'get_event_users');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($frequency, $this->assign1->get_context()->id, 'assign');

        $this->assertEquals($this->user1->id, $result[$this->user1->id]->id);
        $this->assertEquals($this->user2->id, $result[$this->user2->id]->id);

    }

    /**
     * Test process processing user events.
     */
    public function test_process_user_events() {
        global $DB;

        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);
        $result = $frequency->process_user_events($duedate);

        // Check expected record count was returned.
        $this->assertEquals(4, $result);

        // Check the reocrds in the database.
        $count1 = $DB->count_records('local_assessfreq_user', array('userid' => $this->user1->id));
        $count2 = $DB->count_records('local_assessfreq_user', array('userid' => $this->user2->id));

        $this->assertEquals(2, $count1);
        $this->assertEquals(2, $count2);
    }

    /**
     * Test filtering event by dater events.
     */
    public function test_filter_event_data() {
        global $DB;

        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);

        $records = $DB->get_records('local_assessfreq_site');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'filter_event_data');
        $method->setAccessible(true); // Allow accessing of private method.

        // Expect two results.
        $result = $method->invoke($frequency, $records, 0, 0);
        $this->assertCount(2, $result);

        // Expect earliest event.
        $result = $method->invoke($frequency, $records, 0, 1585445775);
        $this->assertEquals(1585359375, $result[0]->timeend);

        // Expect latest event.
        $result = $method->invoke($frequency, $records, 1585359376, 0);
        $this->assertEquals(1585445775, $result[0]->timeend);
    }

    /**
     * Test getting site events and cache.
     */
    public function test_get_site_events() {
        global $DB;

        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);

        $sitecache = cache::make('local_assessfreq', 'siteevents');
        $data = $sitecache->get('all');
        $this->assertEmpty($data);

        $result = $frequency->get_site_events('all', 0, 0, false);

        $this->assertCount(2, $result);

        $data = $sitecache->get('all');
        $this->assertCount(2, $data->events);

        $result = $frequency->get_site_events('forum', 0, 0, true);
        $this->assertEmpty($result);

        $data = $sitecache->get('forum');
        $this->assertEmpty($data);

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, array('id' => $this->course->id));

        $result = $frequency->get_site_events('all', 0, 0, false);
        $this->assertEmpty($result);

        set_config('hiddencourses', '1', 'local_assessfreq');
        $result = $frequency->get_site_events('all', 0, 0, false);
        $this->assertCount(2, $result);

    }

    /**
     * Test getting course events and cache.
     */
    public function test_get_course_events() {
        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);

        $coursecache = cache::make('local_assessfreq', 'courseevents');
        $cachekey = (string)$this->course->id . '_all';
        $data = $coursecache->get($cachekey);
        $this->assertEmpty($data);

        $result = $frequency->get_course_events($this->course->id, 'all', 0, 0, false);
        $this->assertCount(2, $result);

        $data = $coursecache->get($cachekey);
        $this->assertCount(2, $data->events);

        $result = $frequency->get_course_events($this->course->id, 'forum', 0, 0, true);
        $this->assertEmpty($result);

        $result = $frequency->get_course_events(3, 'all', 0, 0, true);
        $this->assertEmpty($result);

        $data = $coursecache->get('forum');
        $this->assertEmpty($data);
    }

    /**
     * Test getting user events and cache.
     */
    public function test_get_user_events() {
        global $DB;

        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);
        $frequency->process_user_events($duedate);

        $usercache = cache::make('local_assessfreq', 'userevents');
        $cachekey = (string)$this->user1->id . '_all';
        $data = $usercache->get($cachekey);
        $this->assertEmpty($data);

        $result = $frequency->get_user_events($this->user1->id, 'all', 0, 0, false);
        $this->assertCount(2, $result);

        $data = $usercache->get($cachekey);
        $this->assertCount(2, $data->events);

        $result = $frequency->get_user_events($this->user1->id, 'forum', 0, 0, true);
        $this->assertEmpty($result);

        $result = $frequency->get_user_events(3, 'all', 0, 0, true);
        $this->assertEmpty($result);

        $data = $usercache->get('forum');
        $this->assertEmpty($data);

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, array('id' => $this->course->id));

        $result = $frequency->get_user_events($this->user1->id, 'all', 0, 0, false);
        $this->assertEmpty($result);

        set_config('hiddencourses', '1', 'local_assessfreq');
        $result = $frequency->get_user_events($this->user1->id, 'all', 0, 0, false);
        $this->assertCount(2, $result);
    }

    /**
     * Test getting all user events and cache.
     */
    public function test_get_user_events_all() {
        global $DB;

        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);
        $frequency->process_user_events($duedate);

        $usercache = cache::make('local_assessfreq', 'usereventsall');
        $cachekey = 'all';
        $data = $usercache->get($cachekey);
        $this->assertEmpty($data);

        $result = $frequency->get_user_events_all('all', 0, 0, false);
        $this->assertCount(4, $result);

        $data = $usercache->get($cachekey);
        $this->assertCount(4, $data->events);

        $result = $frequency->get_user_events_all('forum', 0, 0, true);
        $this->assertEmpty($result);

        $data = $usercache->get('forum');
        $this->assertEmpty($data);

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, array('id' => $this->course->id));

        $result = $frequency->get_user_events_all('all', 0, 0, false);
        $this->assertEmpty($result);

        set_config('hiddencourses', '1', 'local_assessfreq');
        $result = $frequency->get_user_events_all('all', 0, 0, false);
        $this->assertCount(4, $result);
    }

    /**
     * Test getting conflict data.
     */
    public function test_get_conflicts() {
        global $DB;

        // Setup records in DB.
        $lasrecord1 = new \stdClass();
        $lasrecord1->module = 'quiz';
        $lasrecord1->instanceid = 1;
        $lasrecord1->courseid = 2;
        $lasrecord1->contextid = 4;
        $lasrecord1->timestart = 1585728000; // Time in readable format 2020-04-01 @ 8:00:00am GMT.
        $lasrecord1->timeend = 1585814400; // Time in readable format 2020-04-02 @ 8:00:00am GMT.
        $lasrecord1->endyear = 2020;
        $lasrecord1->endmonth = 4;
        $lasrecord1->endday = 2;

        $lasrecord2 = new \stdClass();
        $lasrecord2->module = 'quiz';
        $lasrecord2->instanceid = 2;
        $lasrecord2->courseid = 2;
        $lasrecord2->contextid = 5;
        $lasrecord2->timestart = 1585814401; // Time in readable format 2020-04-02 @ 8:00:01am GMT.
        $lasrecord2->timeend = 1585900800; // Time in readable format 2020-04-03 @ 8:00:00am GMT.
        $lasrecord2->endyear = 2020;
        $lasrecord2->endmonth = 4;
        $lasrecord2->endday = 3;

        $lasrecord3 = new \stdClass();
        $lasrecord3->module = 'quiz';
        $lasrecord3->instanceid = 3;
        $lasrecord3->courseid = 2;
        $lasrecord3->contextid = 6;
        $lasrecord3->timestart = 1585900801; // Time in readable format 2020-04-03 @ 8:00:01am GMT.
        $lasrecord3->timeend = 1586073600; // Time in readable format 2020-04-05 @ 8:00:00am GMT.
        $lasrecord3->endyear = 2020;
        $lasrecord3->endmonth = 4;
        $lasrecord3->endday = 5;

        $lasrecord4 = new \stdClass();
        $lasrecord4->module = 'quiz';
        $lasrecord4->instanceid = 4;
        $lasrecord4->courseid = 2;
        $lasrecord4->contextid = 7;
        $lasrecord4->timestart = 1585987200; // Time in readable format 2020-04-04 @ 8:00:00am GMT.
        $lasrecord4->timeend = 1586160000; // Time in readable format 2020-04-06 @ 8:00:00am GMT.
        $lasrecord4->endyear = 2020;
        $lasrecord4->endmonth = 4;
        $lasrecord4->endday = 6;

        $lasrecord5 = new \stdClass();
        $lasrecord5->module = 'quiz';
        $lasrecord5->instanceid = 5;
        $lasrecord5->courseid = 2;
        $lasrecord5->contextid = 8;
        $lasrecord5->timestart = 1586073601; // Time in readable format 2020-04-05 @ 8:00:01am GMT.
        $lasrecord5->timeend = 1586246400; // Time in readable format 2020-04-07 @ 8:00:00am GMT.
        $lasrecord5->endyear = 2020;
        $lasrecord5->endmonth = 4;
        $lasrecord5->endday = 7;

        $lasrecord6 = new \stdClass();
        $lasrecord6->module = 'assign';
        $lasrecord6->instanceid = 6;
        $lasrecord6->courseid = 2;
        $lasrecord6->contextid = 7;
        $lasrecord6->timestart = 1586084400; // Time in readable format 2020-04-05 @ 11:00:00am GMT.
        $lasrecord6->timeend = 1586160000; // Time in readable format 2020-04-06 @ 8:00:00am GMT.
        $lasrecord6->endyear = 2020;
        $lasrecord6->endmonth = 4;
        $lasrecord6->endday = 6;

        $lasrecord7 = new \stdClass();
        $lasrecord7->module = 'quiz';
        $lasrecord7->instanceid = 7;
        $lasrecord7->courseid = 2;
        $lasrecord7->contextid = 7;
        $lasrecord7->timestart = 1586073601; // Time in readable format 2020-04-05 @ 8:00:01am GMT.
        $lasrecord7->timeend = 1586246400; // Time in readable format 2020-04-07 @ 8:00:00am GMT.
        $lasrecord7->endyear = 2020;
        $lasrecord7->endmonth = 4;
        $lasrecord7->endday = 6;

        // Record 1 and 2 should not overlap.
        // Record 3 overlaps Record 4.
        // Record 4 overlaps Record 5.
        // So Record 4 should have two conflicts (record 3 and 5).
        // So Record 3 and 5 should have one conflict (record 4).
        // Record 6 should not have any conflicts because it is not a quiz.
        // Record 7 should not have any conflicts because it has no users.

        // Insert records in to database.
        $records = array($lasrecord1, $lasrecord2, $lasrecord3, $lasrecord4, $lasrecord5, $lasrecord6, $lasrecord7);
        $userids = array(234, 456, 789);
        $eventarray = array();
        foreach ($records as $record) {
            $eventid = $DB->insert_record('local_assessfreq_site', $record);
            $eventarray[$record->instanceid] = $eventid;
            if ($record->instanceid != 7) { // Don't add users for record 7.
                foreach ($userids as $userid) {
                    if ($userid == 789 && $record->instanceid == 3) {
                        continue;
                    }
                    $userrecord = new \stdClass();
                    $userrecord->userid = $userid;
                    $userrecord->eventid = $eventid;
                    $DB->insert_record('local_assessfreq_user', $userrecord);
                }
            }

        }

        $frequency = new frequency();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'get_conflicts');
        $method->setAccessible(true); // Allow accessing of private method.

        $results = $method->invoke($frequency, 0);

        // Expect total of 4 conflicts.
        $this->assertCount(4, $results);

        // Make sure we don't have any references to records that don't have conflicts.
        foreach ($results as $result) {
            $this->assertNotEquals($eventarray[1], $result->eventid);
            $this->assertNotEquals($eventarray[1], $result->conflictid);
            $this->assertNotEquals($eventarray[2], $result->eventid);
            $this->assertNotEquals($eventarray[2], $result->conflictid);
            $this->assertNotEquals($eventarray[7], $result->eventid);
            $this->assertNotEquals($eventarray[7], $result->conflictid);
        }
    }

    /**
     * Test getting course events and cache.
     */
    public function test_get_events_due_by_month() {
        global $DB;
        $year = 2020;

        // Make some records to put in the database;
        // Every even month should have two entries and every odd month one entry.
        $records = array();
        $month = 1;
        for ($i = 1; $i <= 24; $i++) {

            if ($i > 12 && ($month % 2 != 0)) {
                $month ++;
                continue;
            }

            $record = new \stdClass();
            $record->module = 'quiz';
            $record->instanceid = $i;
            $record->courseid = $this->course->id;
            $record->contextid = $i;
            $record->timestart = 0; // Start can be fake for this test.
            $record->timeend = 0; // End can be fake for this test.
            $record->endyear = $year;
            $record->endmonth = $month;
            $record->endday = 1;

            $records[] = $record;

            if ($month == 12) {
                $month = 0;
            }
            $month ++;
        }

        $DB->insert_records('local_assessfreq_site', $records);

        // Cache should be initially empty.
        $eventduecache = cache::make('local_assessfreq', 'eventsduemonth');
        $cachekey = (string)$year;
        $data = $eventduecache->get($cachekey);
        $this->assertEmpty($data);

        $frequency = new frequency();
        $results = $frequency->get_events_due_by_month($year);

        $this->assertCount(12, $results);
        $this->assertEquals(1, $results[1]->count);
        $this->assertEquals(2, $results[2]->count);

        $data = $eventduecache->get($cachekey);
        $this->assertCount(12, $data->events);

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, array('id' => $this->course->id));

        $result = $frequency->get_events_due_by_month($year, false);
        $this->assertEmpty($result);

        set_config('hiddencourses', '1', 'local_assessfreq');
        $result = $frequency->get_events_due_by_month($year, false);
        $this->assertCount(12, $result);
    }

    /**
     * Test getting course events and cache.
     * Check behavior if there is no data.
     */
    public function test_get_events_due_by_month_no_data() {
        $year = 2020;

        // Cache should be initially empty.
        $eventduecache = cache::make('local_assessfreq', 'eventsduemonth');
        $cachekey = (string)$year;
        $data = $eventduecache->get($cachekey);
        $this->assertEmpty($data);

        $frequency = new frequency();
        $results = $frequency->get_events_due_by_month($year);

        $this->assertEmpty($results);

        $data = $eventduecache->get($cachekey);
        $this->assertEmpty($data);
    }

    /**
     * Test years that have events.
     */
    public function test_get_years_has_events() {
        global $DB;

        // Make some records to put in the database;
        // Every even month should have two entries and every odd month one entry.
        $records = array();

        $lasrecord1 = new \stdClass();
        $lasrecord1->module = 'quiz';
        $lasrecord1->instanceid = 1;
        $lasrecord1->courseid = 2;
        $lasrecord1->contextid = 4;
        $lasrecord1->timestart = 1585728000; // Time in readable format 2020-04-01 @ 8:00:00am GMT.
        $lasrecord1->timeend = 1585814400; // Time in readable format 2020-04-02 @ 8:00:00am GMT.
        $lasrecord1->endyear = 2019;
        $lasrecord1->endmonth = 4;
        $lasrecord1->endday = 2;

        $lasrecord2 = new \stdClass();
        $lasrecord2->module = 'quiz';
        $lasrecord2->instanceid = 2;
        $lasrecord2->courseid = 2;
        $lasrecord2->contextid = 5;
        $lasrecord2->timestart = 1585814401; // Time in readable format 2020-04-02 @ 8:00:01am GMT.
        $lasrecord2->timeend = 1585900800; // Time in readable format 2020-04-03 @ 8:00:00am GMT.
        $lasrecord2->endyear = 2020;
        $lasrecord2->endmonth = 4;
        $lasrecord2->endday = 3;

        $lasrecord3 = new \stdClass();
        $lasrecord3->module = 'quiz';
        $lasrecord3->instanceid = 3;
        $lasrecord3->courseid = 2;
        $lasrecord3->contextid = 6;
        $lasrecord3->timestart = 1585900801; // Time in readable format 2020-04-03 @ 8:00:01am GMT.
        $lasrecord3->timeend = 1586073600; // Time in readable format 2020-04-05 @ 8:00:00am GMT.
        $lasrecord3->endyear = 2020;
        $lasrecord3->endmonth = 4;
        $lasrecord3->endday = 5;

        $lasrecord4 = new \stdClass();
        $lasrecord4->module = 'quiz';
        $lasrecord4->instanceid = 4;
        $lasrecord4->courseid = 2;
        $lasrecord4->contextid = 7;
        $lasrecord4->timestart = 1585987200; // Time in readable format 2020-04-04 @ 8:00:00am GMT.
        $lasrecord4->timeend = 1586160000; // Time in readable format 2020-04-06 @ 8:00:00am GMT.
        $lasrecord4->endyear = 2021;
        $lasrecord4->endmonth = 4;
        $lasrecord4->endday = 6;

        $records = array($lasrecord1, $lasrecord2, $lasrecord3, $lasrecord4);

        $DB->insert_records('local_assessfreq_site', $records);

        // Cache should be initially empty.
        $yeareventscache = cache::make('local_assessfreq', 'yearevents');
        $cachekey = 'yearevents';
        $data = $yeareventscache->get($cachekey);
        $this->assertEmpty($data);

        $frequency = new frequency();
        $results = $frequency->get_years_has_events();

        $this->assertCount(3, $results);
        $this->assertContains(2019, $results);
        $this->assertContains(2020, $results);
        $this->assertContains(2021, $results);

        $data = $yeareventscache->get($cachekey);
        $this->assertCount(3, $data->events);
    }

    /**
     * Test years that have events.
     * Check behavior if there is no data.
     */
    public function test_get_years_has_events_no_data() {
        // Cache should be initially empty.
        $yeareventscache = cache::make('local_assessfreq', 'yearevents');
        $cachekey = 'yearevents';
        $data = $yeareventscache->get($cachekey);
        $this->assertEmpty($data);

        $frequency = new frequency();
        $results = $frequency->get_years_has_events();

        $this->assertEmpty($results);

        $data = $yeareventscache->get($cachekey);
        $this->assertEmpty($data);
    }

    /**
     * Test getting activities that have events.
     */
    public function test_get_events_due_by_activity() {
        global $DB;
        $year = 2020;

        // Make some records to put in the database;
        // Every even month should have two entries and every odd month one entry.
        $records = array();

        $lasrecord1 = new \stdClass();
        $lasrecord1->module = 'quiz';
        $lasrecord1->instanceid = 1;
        $lasrecord1->courseid = $this->course->id;
        $lasrecord1->contextid = 4;
        $lasrecord1->timestart = 1585728000; // Time in readable format 2020-04-01 @ 8:00:00am GMT.
        $lasrecord1->timeend = 1585814400; // Time in readable format 2020-04-02 @ 8:00:00am GMT.
        $lasrecord1->endyear = 2020;
        $lasrecord1->endmonth = 4;
        $lasrecord1->endday = 2;

        $lasrecord2 = new \stdClass();
        $lasrecord2->module = 'assign';
        $lasrecord2->instanceid = 2;
        $lasrecord2->courseid = $this->course->id;
        $lasrecord2->contextid = 5;
        $lasrecord2->timestart = 1585814401; // Time in readable format 2020-04-02 @ 8:00:01am GMT.
        $lasrecord2->timeend = 1585900800; // Time in readable format 2020-04-03 @ 8:00:00am GMT.
        $lasrecord2->endyear = 2020;
        $lasrecord2->endmonth = 4;
        $lasrecord2->endday = 3;

        $lasrecord3 = new \stdClass();
        $lasrecord3->module = 'assign';
        $lasrecord3->instanceid = 3;
        $lasrecord3->courseid = $this->course->id;
        $lasrecord3->contextid = 6;
        $lasrecord3->timestart = 1585900801; // Time in readable format 2020-04-03 @ 8:00:01am GMT.
        $lasrecord3->timeend = 1586073600; // Time in readable format 2020-04-05 @ 8:00:00am GMT.
        $lasrecord3->endyear = 2020;
        $lasrecord3->endmonth = 4;
        $lasrecord3->endday = 5;

        $lasrecord4 = new \stdClass();
        $lasrecord4->module = 'forum';
        $lasrecord4->instanceid = 4;
        $lasrecord4->courseid = $this->course->id;
        $lasrecord4->contextid = 7;
        $lasrecord4->timestart = 1585987200; // Time in readable format 2020-04-04 @ 8:00:00am GMT.
        $lasrecord4->timeend = 1586160000; // Time in readable format 2020-04-06 @ 8:00:00am GMT.
        $lasrecord4->endyear = 2020;
        $lasrecord4->endmonth = 4;
        $lasrecord4->endday = 6;

        $lasrecord5 = new \stdClass();
        $lasrecord5->module = 'forum';
        $lasrecord5->instanceid = 5;
        $lasrecord5->courseid = $this->course->id;
        $lasrecord5->contextid = 8;
        $lasrecord5->timestart = 1585987200; // Time in readable format 2020-04-04 @ 8:00:00am GMT.
        $lasrecord5->timeend = 1586160000; // Time in readable format 2020-04-06 @ 8:00:00am GMT.
        $lasrecord5->endyear = 2021;
        $lasrecord5->endmonth = 4;
        $lasrecord5->endday = 6;

        $records = array($lasrecord1, $lasrecord2, $lasrecord3, $lasrecord4, $lasrecord5);

        $DB->insert_records('local_assessfreq_site', $records);

        // Cache should be initially empty.
        $yeareventscache = cache::make('local_assessfreq', 'eventsdueactivity');
        $cachekey = (string)$year . '_activity';
        $data = $yeareventscache->get($cachekey);
        $this->assertEmpty($data);

        $frequency = new frequency();
        $results = $frequency->get_events_due_by_activity($year);

        $this->assertCount(3, $results);
        $this->assertEquals(2, $results['assign']->count);
        $this->assertEquals(1, $results['forum']->count);
        $this->assertEquals(1, $results['quiz']->count);

        $data = $yeareventscache->get($cachekey);
        $this->assertCount(3, $data->events);

        $this->course->visible = 0;
        $DB->set_field('course', 'visible', 0, array('id' => $this->course->id));

        $result = $frequency->get_events_due_by_activity($year, false);
        $this->assertEmpty($result);

        set_config('hiddencourses', '1', 'local_assessfreq');
        $result = $frequency->get_events_due_by_activity($year, false);
        $this->assertCount(3, $result);
    }

    /**
     * Test getting user events and cache.
     */
    public function test_get_events_due_monthly_by_user() {
        global $DB;
        $year = 2020;

        // Make some records to put in the database;
        // Every month should have an increasing ammount of users.
        for ($i = 1; $i <= 12; $i++) {
            $record = new \stdClass();
            $record->module = 'quiz';
            $record->instanceid = $i;
            $record->courseid = $this->course->id;
            $record->contextid = $i;
            $record->timestart = 0; // Start can be fake for this test.
            $record->timeend = 0; // End can be fake for this test.
            $record->endyear = $year;
            $record->endmonth = $i;
            $record->endday = 1;

            $eventid = $DB->insert_record('local_assessfreq_site', $record, true);

            for ($j = 1; $j <= $i; $j++) {
                $userrecord = new \stdClass();
                $userrecord->userid = $j;
                $userrecord->eventid = $eventid;

                $DB->insert_record('local_assessfreq_user', $userrecord, true);
            }

        }

        // Cache should be initially empty.
        $monthlyusercache = cache::make('local_assessfreq', 'monthlyuser');
        $cachekey = (string)$year;
        $data = $monthlyusercache->get($cachekey);
        $this->assertEmpty($data);

        $frequency = new frequency();
        $results = $frequency->get_events_due_monthly_by_user($year);

         $this->assertCount(12, $results);
         $this->assertEquals(1, $results[1]->count);
         $this->assertEquals(2, $results[2]->count);
         $this->assertEquals(3, $results[3]->count);
         $this->assertEquals(4, $results[4]->count);
         $this->assertEquals(5, $results[5]->count);
         $this->assertEquals(6, $results[6]->count);
         $this->assertEquals(7, $results[7]->count);

         $data = $monthlyusercache->get($cachekey);
         $this->assertCount(12, $data->events);

         $this->course->visible = 0;
         $DB->set_field('course', 'visible', 0, array('id' => $this->course->id));

         $result = $frequency->get_events_due_monthly_by_user($year, false);
         $this->assertEmpty($result);

         set_config('hiddencourses', '1', 'local_assessfreq');
         $result = $frequency->get_events_due_monthly_by_user($year, false);
         $this->assertCount(12, $result);
    }

    /**
     * Test getting the frequency array.
     */
    public function test_get_frequency_array() {
        $year = 2020;
        $metric = 'assess'; // Can be assess or students.
        $modules = array('all');

        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);
        $frequency->process_user_events($duedate);

        $result = $frequency->get_frequency_array($year, $metric, $modules);
        $this->assertEquals(1, $result[2020][3][29]['number']);
        $this->assertEquals(1, $result[2020][3][28]['number']);
        $this->assertEquals(1, $result[2020][3][29]['assign']);
        $this->assertEquals(1, $result[2020][3][28]['assign']);

        $metric = 'students';
        $result = $frequency->get_frequency_array($year, $metric, $modules);
        $this->assertEquals(2, $result[2020][3][29]['number']);
        $this->assertEquals(2, $result[2020][3][28]['number']);
        $this->assertEquals(2, $result[2020][3][29]['assign']);
        $this->assertEquals(2, $result[2020][3][28]['assign']);

    }

    /**
     * Test getting the download data.
     */
    public function test_get_download_data() {
        $year = 2020;
        $metric = 'assess'; // Can be assess or students.
        $modules = array('all');

        $duedate = 0;
        $frequency = new frequency();
        $frequency->process_site_events($duedate);
        $frequency->process_user_events($duedate);

        $result = $frequency->get_download_data($year, $metric, $modules);

        $this->assertRegexp('/mod\/assign\/view/', $result[0][2]);
        $this->assertRegexp('/mod\/assign\/view/', $result[1][2]);
    }

    /**
     * Test getting heat colors.
     */
    public function test_get_heat_colors() {
        $frequency = new frequency();
        $result = $frequency->get_heat_colors();

        $this->assertEquals('#FDF9CD', $result[1]);
        $this->assertEquals('#A2DAB5', $result[2]);
        $this->assertEquals('#41B7C5', $result[3]);
        $this->assertEquals('#4D7FB9', $result[4]);
        $this->assertEquals('#283B94', $result[5]);
        $this->assertEquals('#8C0010', $result[6]);

        set_config('heat3', '#FFFFFF', 'local_assessfreq');
        $result = $frequency->get_heat_colors();
        $this->assertEquals('#FFFFFF', $result[3]);
    }

    /**
     * Test getting modules to process.
     */
    public function test_get_process_modules() {
        global $DB;

        $DB->set_field('modules', 'visible', '0', array('name' => 'scorm'));
        $DB->set_field('modules', 'visible', '0', array('name' => 'choice'));

        set_config('modules', 'quiz,assign,scorm,choice', 'local_assessfreq');
        set_config('disabledmodules', '0', 'local_assessfreq');

        $frequency = new frequency();
        $result = $frequency->get_process_modules();

        $this->assertContains('quiz', $result);
        $this->assertContains('assign', $result);
        $this->assertNotContains('scorm', $result);
        $this->assertNotContains('choice', $result);

        set_config('disabledmodules', '1', 'local_assessfreq');
        $result = $frequency->get_process_modules();;

        $this->assertContains('quiz', $result);
        $this->assertContains('assign', $result);
        $this->assertContains('scorm', $result);
        $this->assertContains('choice', $result);
    }

    /**
     * Test getting day event information.
     */
    public function test_get_day_events() {
        $date = '2020-3-28';
        $modules = array('all');

        $frequency = new frequency();
        $frequency->process_site_events(0);
        $frequency->process_user_events(0);
        $result = $frequency->get_day_events($date, $modules);

        $this->assertEquals('assign', $result[0]->module);
        $this->assertEquals(2, $result[0]->usercount);
        $this->assertEquals(2020, $result[0]->endyear);
        $this->assertEquals(3, $result[0]->endmonth);
        $this->assertEquals(28, $result[0]->endday);

    }
}
