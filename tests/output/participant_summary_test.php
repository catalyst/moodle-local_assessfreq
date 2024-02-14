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
 * This file contains the class that handles testing of the participant summary class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

namespace local_assessfreq\output;

use stdClass;

/**
 * This file contains the class that handles testing of the participant summary class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_assessfreq\output\participant_summary
 */
class participant_summary_test extends \advanced_testcase {
    /**
     *
     * @var stdClass $course Test course.
     */
    protected $course;

    /**
     * Set up conditions for tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();

        global $DB;
        $now = 1594788000;

        $track1 = new stdClass();
        $track1->assessid = 123;
        $track1->notloggedin = 5;
        $track1->loggedin = 0;
        $track1->inprogress = 0;
        $track1->finished = 0;
        $track1->timecreated = $now + (60 * 1);

        $track2 = new stdClass();
        $track2->assessid = 123;
        $track2->notloggedin = 4;
        $track2->loggedin = 1;
        $track2->inprogress = 1;
        $track2->finished = 0;
        $track2->timecreated = $now + (60 * 2);

        $track3 = new stdClass();
        $track3->assessid = 123;
        $track3->notloggedin = 3;
        $track3->loggedin = 2;
        $track3->inprogress = 2;
        $track3->finished = 0;
        $track3->timecreated = $now + (60 * 3);

        $track4 = new stdClass();
        $track4->assessid = 123;
        $track4->notloggedin = 2;
        $track4->loggedin = 3;
        $track4->inprogress = 3;
        $track4->finished = 0;
        $track4->timecreated = $now + (60 * 4);

        $track5 = new stdClass();
        $track5->assessid = 123;
        $track5->notloggedin = 1;
        $track5->loggedin = 4;
        $track5->inprogress = 3;
        $track5->finished = 1;
        $track5->timecreated = $now + (60 * 5);

        // Insert out of order.
        $trackrecords = [$track1, $track5, $track3, $track2, $track4];

        $DB->insert_records('local_assessfreq_trend', $trackrecords);
    }

    /**
     * Test gett assess due by month chart method.
     */
    public function test_get_assess_activity_chart(): void {

        $participantsumamry = new participant_summary();
        $result = $participantsumamry->get_participant_summary_chart(123);

        $values = $result['chart']->get_series()[0]->get_values();
        $labels = $result['chart']->get_labels();

        $this->assertTrue($result['hasdata']);
        $this->assertEquals(1, $values[0]);
        $this->assertEquals(4, $values[1]);
        $this->assertEquals(3, $values[2]);
        $this->assertEquals(1, $values[3]);
        $this->assertEquals('Not logged in', $labels[0]);
        $this->assertEquals('Logged in', $labels[1]);
        $this->assertEquals('In progress', $labels[2]);
        $this->assertEquals('Finished', $labels[3]);
    }
}
