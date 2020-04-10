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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
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
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
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
    }

    /**
     * Test getting the map.
     */
    public function test_get_map() {
        $frequency = new frequency();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'get_map');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($frequency, 0);
        $this->assertEquals(0, $result);

        $result = $method->invoke($frequency, 1);
        $this->assertEquals(0, $result);

        $result = $method->invoke($frequency, 3);
        $this->assertEquals(1, $result);

        $result = $method->invoke($frequency, 5);
        $this->assertEquals(2, $result);

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

        // Expect two results
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
    }
}
