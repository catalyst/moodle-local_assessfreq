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
        $this->resetAfterTest();

        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));
        $assignrow = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 123456
        ));
        $assign = new assign(context_module::instance($assignrow->cmid), false, false);

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

        foreach ($result as $record) {
            $this->assertEquals($course->id, $record->course);
            $this->assertEquals($assign->get_context()->id, $record->contextid);
            $this->assertEquals(123456, $record->duedate);
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
        $this->resetAfterTest();

        global $DB;
        $frequency = new frequency();
        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));
        $assignrow1 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585359375
        ));
        $assignrow2 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585445775
        ));
        $assign1 = new assign(context_module::instance($assignrow1->cmid), false, false);
        $assign2 = new assign(context_module::instance($assignrow2->cmid), false, false);

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
        $record1 = $DB->get_record('local_assessfreq_site', array('instanceid' => $assign1->get_course_module()->instance));
        $record2 = $DB->get_record('local_assessfreq_site', array('instanceid' => $assign2->get_course_module()->instance));

        $this->assertEquals(28, $record1->endday);
        $this->assertEquals(29, $record2->endday);
    }

    /**
     * Test process site events method.
     */
    public function test_process_site_events() {
        $this->resetAfterTest();

        global $DB;
        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));
        $assignrow1 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585359375
        ));
        $assignrow2 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585445775
        ));
        new assign(context_module::instance($assignrow1->cmid), false, false);
        $assign2 = new assign(context_module::instance($assignrow2->cmid), false, false);

        $now = 1585359400;
        $frequency = new frequency();
        $result = $frequency->process_site_events($now);

        $this->assertEquals(1, $result);

        // Check actual records in the DB.
        $record = $DB->get_record('local_assessfreq_site', array('instanceid' => $assign2->get_course_module()->instance));
        $this->assertEquals(29, $record->endday);
    }

    /**
     * Test process site events method.
     */
    public function test_delete_events() {
        $this->resetAfterTest();

        global $DB;
        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));
        $assignrow1 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585359375
        ));
        $assignrow2 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585445775
        ));
        new assign(context_module::instance($assignrow1->cmid), false, false);
        new assign(context_module::instance($assignrow2->cmid), false, false);

        $frequency = new frequency();
        $result = $frequency->process_site_events(0);

        $this->assertEquals(2, $result);

        // Delete events from date.
        $now = 1585359375;
        $frequency->delete_events($now);

        $count = $DB->count_records('local_assessfreq_site');
        $this->assertEquals(0, $count); // Should be no records.

        $result = $frequency->process_site_events(0);
        $now = 1585359400;
        $frequency->delete_events($now);

        $count = $DB->count_records('local_assessfreq_site');
        $this->assertEquals(1, $count); // Should be one record.
    }

    /**
     * Test process getting events for users.
     */
    public function test_get_event_users() {
        $this->resetAfterTest();

        global $DB;
        $frequency = new frequency();
        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => COMPLETION_ENABLED),
            array('createsections' => true));
        $assignrow1 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585359375
        ));
        $assign1 = new assign(context_module::instance($assignrow1->cmid), false, false);

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Enrol users into the course.
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'get_event_users');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($frequency, $assign1->get_context()->id, 'assign');

        $this->assertEquals($user1->id, $result[$user1->id]->id);
        $this->assertEquals($user2->id, $result[$user2->id]->id);

    }
}
