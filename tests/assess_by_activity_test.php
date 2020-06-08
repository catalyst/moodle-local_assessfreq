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
 * This file contains the class that handles testing of the assess by activity class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

use local_assessfreq\output\assess_by_activity;

/**
 * This file contains the class that handles testing of the assess by activity class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class assess_by_activity_testcase extends advanced_testcase {

    /**
     * Set up conditions for tests.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test gett assess due by month chart method.
     */
    public function test_get_assess_activity_chart() {
        global $DB;
        $year = 2020;

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
        $lasrecord1->endyear = 2020;
        $lasrecord1->endmonth = 4;
        $lasrecord1->endday = 2;

        $lasrecord2 = new \stdClass();
        $lasrecord2->module = 'assign';
        $lasrecord2->instanceid = 2;
        $lasrecord2->courseid = 2;
        $lasrecord2->contextid = 5;
        $lasrecord2->timestart = 1585814401; // Time in readable format 2020-04-02 @ 8:00:01am GMT.
        $lasrecord2->timeend = 1585900800; // Time in readable format 2020-04-03 @ 8:00:00am GMT.
        $lasrecord2->endyear = 2020;
        $lasrecord2->endmonth = 4;
        $lasrecord2->endday = 3;

        $lasrecord3 = new \stdClass();
        $lasrecord3->module = 'assign';
        $lasrecord3->instanceid = 3;
        $lasrecord3->courseid = 2;
        $lasrecord3->contextid = 6;
        $lasrecord3->timestart = 1585900801; // Time in readable format 2020-04-03 @ 8:00:01am GMT.
        $lasrecord3->timeend = 1586073600; // Time in readable format 2020-04-05 @ 8:00:00am GMT.
        $lasrecord3->endyear = 2020;
        $lasrecord3->endmonth = 4;
        $lasrecord3->endday = 5;

        $lasrecord4 = new \stdClass();
        $lasrecord4->module = 'scorm';
        $lasrecord4->instanceid = 4;
        $lasrecord4->courseid = 2;
        $lasrecord4->contextid = 7;
        $lasrecord4->timestart = 1585987200; // Time in readable format 2020-04-04 @ 8:00:00am GMT.
        $lasrecord4->timeend = 1586160000; // Time in readable format 2020-04-06 @ 8:00:00am GMT.
        $lasrecord4->endyear = 2020;
        $lasrecord4->endmonth = 4;
        $lasrecord4->endday = 6;

        $lasrecord5 = new \stdClass();
        $lasrecord5->module = 'scorm';
        $lasrecord5->instanceid = 5;
        $lasrecord5->courseid = 2;
        $lasrecord5->contextid = 8;
        $lasrecord5->timestart = 1585987200; // Time in readable format 2020-04-04 @ 8:00:00am GMT.
        $lasrecord5->timeend = 1586160000; // Time in readable format 2020-04-06 @ 8:00:00am GMT.
        $lasrecord5->endyear = 2021;
        $lasrecord5->endmonth = 4;
        $lasrecord5->endday = 6;

        $records = array($lasrecord1, $lasrecord2, $lasrecord3, $lasrecord4, $lasrecord5);

        $DB->insert_records('local_assessfreq_site', $records);

        $assessbymonth = new assess_by_activity();
        $result = $assessbymonth->get_assess_by_activity_chart($year);
        $values = $result->get_series()[0]->get_values();

        $version = get_config('moodle', 'version');

        if ($version < 2019052000) { // Versions less than 3.7 don't support forum due dates.
            $this->assertEquals(2, $values[0]);
            $this->assertEquals(0, $values[1]);
            $this->assertEquals(0, $values[2]);
            $this->assertEquals(0, $values[3]);
            $this->assertEquals(0, $values[4]);
            $this->assertEquals(1, $values[5]);
            $this->assertEquals(1, $values[6]);
            $this->assertEquals(0, $values[7]);
        } else {
            $this->assertEquals(2, $values[0]);
            $this->assertEquals(0, $values[1]);
            $this->assertEquals(0, $values[2]);
            $this->assertEquals(0, $values[3]);
            $this->assertEquals(0, $values[4]);
            $this->assertEquals(0, $values[5]);
            $this->assertEquals(1, $values[6]);
            $this->assertEquals(1, $values[7]);
            $this->assertEquals(0, $values[8]);
        }

    }
}
