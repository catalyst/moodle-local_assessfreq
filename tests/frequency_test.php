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
     * Test getting the raw events.
     */
    public function test_get_events() {
        $this->resetAfterTest();

        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $this->resetAfterTest(true);
        $this->setAdminuser();
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        for ($i = 1; $i < 2; $i++) {
            create_event([
                'name' => sprintf('Event %d', $i),
                'eventtype' => 'user',
                'userid' => $user->id,
                'timesort' => $i,
                'type' => CALENDAR_EVENT_TYPE_ACTION,
                'courseid' => $course1->id,
            ]);
        }

        for ($i = 2; $i < 4; $i++) {
            create_event([
                'name' => sprintf('Event %d', $i),
                'eventtype' => 'user',
                'userid' => $user->id,
                'timesort' => $i,
                'type' => CALENDAR_EVENT_TYPE_ACTION,
                'courseid' => $course2->id,
            ]);
        }

        $frequency = new frequency();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new ReflectionMethod('\local_assessfreq\frequency', 'get_events');
        $method->setAccessible(true); // Allow accessing of private method.
        $result = $method->invoke($frequency);

        $this->assertCount(3, $result); // Should be 3 events across 2 courses.
        $this->assertEquals('Event 1', $result[0]->get_name());
        $this->assertEquals('Event 2', $result[1]->get_name());
        $this->assertEquals('Event 3', $result[2]->get_name());
    }

    /**
     * Test getting the frequency array.
     */
    public function test_get_frequency_array() {
        $this->resetAfterTest();
        //$this->setUser();

        $user = $this->getDataGenerator()->create_user();
        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();

        $this->resetAfterTest(true);
        $this->setAdminuser();
        $this->getDataGenerator()->enrol_user($user->id, $course1->id);
        $this->getDataGenerator()->enrol_user($user->id, $course2->id);

        for ($i = 1; $i < 3; $i++) {
            create_event([
                'name' => sprintf('Event %d', $i),
                'eventtype' => 'user',
                'userid' => $user->id,
                'timesort' => $i,
                'timestart' => 1581227776,
                'type' => CALENDAR_EVENT_TYPE_ACTION,
                'courseid' => $course1->id,
            ]);
        }

        for ($i = 3; $i < 6; $i ++) {
            create_event([
                'name' => sprintf('Event %d', $i),
                'eventtype' => 'user',
                'userid' => $user->id,
                'timesort' => $i,
                'timestart' => 1581170400,
                'type' => CALENDAR_EVENT_TYPE_ACTION,
                'courseid' => $course2->id
            ]);
        }

        $frequency = new frequency();
        $result = $frequency->get_frequency_array();

        $this->assertEquals(2, $result[2020][2][9]['number']);
        $this->assertEquals(3, $result[2020][2][8]['number']);
        $this->assertEquals(0, $result[2020][2][9]['heat']);
        $this->assertEquals(1, $result[2020][2][8]['heat']);
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
}
