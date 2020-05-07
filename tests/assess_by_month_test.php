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
 * This file contains the class that handles testing of the assess by month class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

use local_assessfreq\output\assess_by_month;

/**
 * This file contains the class that handles testing of the assess by month class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class assess_by_month_testcase extends advanced_testcase {

    /**
     * Set up conditions for tests.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test gett assess due by month chart method.
     */
    public function test_get_assess_due_chart() {
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
            $record->courseid = 2;
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

        $assessbymonth = new assess_by_month();
        $result = $assessbymonth->get_assess_by_month_chart($year);
        $values = $result->get_series()[0]->get_values();

        foreach ($values as $value) {
            if ($value % 2 != 0) {
                $count = 1;
            } else {
                $count = 2;
            }
            $this->assertEquals($count, $value);
        }
    }
}
