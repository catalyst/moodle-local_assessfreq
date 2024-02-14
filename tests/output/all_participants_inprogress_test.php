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
 * This file contains the class that handles testing of the all participants in progress class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

namespace local_assessfreq\output;

use stdClass;

/**
 * This file contains the class that handles testing of the all participants in progress class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_assessfreq\output\all_participants_inprogress
 */
class all_participants_inprogress_test extends \advanced_testcase {
    /**
     *
     * @var stdClass $course Test course.
     */
    protected $course;

    /**
     *
     * @var stdClass Second test quiz.
     */
    protected $quiz3;

    /**
     *
     * @var stdClass Second test quiz.
     */
    protected $quiz4;

    /**
     *
     * @var stdClass Second test quiz.
     */
    protected $quiz5;

    /**
     *
     * @var stdClass Second test quiz.
     */
    protected $quiz6;

    /**
     *
     * @var stdClass Second test quiz.
     */
    protected $quiz7;

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
     *
     * @var stdClass Third test user.
     */
    protected $user3;

    /**
     *
     * @var stdClass Fourth test user.
     */
    protected $user4;

    /**
     *
     * @var stdClass Fifth test user.
     */
    protected $user5;

    /**
     *
     * @var stdClass Sixth test user.
     */
    protected $user6;

    /**
     * Set up conditions for tests.
     */
    public function setUp(): void {
        $this->resetAfterTest();

        global $DB, $CFG;
        $now = 1594788000;

        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $layout = '1,2,0,3,4,0,5,6,0';
        $course = $generator->create_course(
            ['format' => 'topics', 'numsections' => 3,
                'enablecompletion' => 1, ],
            ['createsections' => true]
        );

        // Start is more than one hour in the past, but end is in the future. (Should return).
        $this->quiz3 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 2)),
            'timeclose' => ($now + (3600 * 0.5)),
            'timelimit' => 3600,
        ]);

        // Start is less than one hour in the past, but end is in the future. (Should return).
        $this->quiz4 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 0.5)),
            'timeclose' => ($now + (3600 * 0.5)),
            'timelimit' => 3600,
        ]);

        // Start is less than one hour in the future, end is more than one hour in the future. (Should return).
        $this->quiz5 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now + (3600 * 0.5)),
            'timeclose' => ($now + (3600 * 2)),
            'timelimit' => 3600,
        ]);

        // Start is less than one hour in the future, end is less that one hour in the future. (Should return).
        $this->quiz6 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now + (3600 * 0.25)),
            'timeclose' => ($now + (3600 * 0.75)),
            'timelimit' => 1800,
        ]);

        // Start is more than one hour in the future, end is more than one hour in the future. (Should not return).
        $this->quiz7 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now + (3600 * 2)),
            'timeclose' => ($now + (3600 * 3)),
            'timelimit' => 3600,
        ]);

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();
        $user3 = $generator->create_user();
        $user4 = $generator->create_user();
        $user5 = $generator->create_user();
        $user6 = $generator->create_user();

        // Enrol users into the course.
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');
        $generator->enrol_user($user3->id, $course->id, 'student');
        $generator->enrol_user($user4->id, $course->id, 'student');
        $generator->enrol_user($user5->id, $course->id, 'student');
        $generator->enrol_user($user6->id, $course->id, 'student');

        // Set up a couple of overrides.
        $override1 = new stdClass();
        $override1->quiz = $this->quiz3->id;
        $override1->userid = $user3->id;
        $override1->timeopen = 1593996000; // Open early.
        $override1->timeclose = 1594004400;
        $override1->timelimit = 7200;

        $override2 = new stdClass();
        $override2->quiz = $this->quiz4->id;
        $override2->userid = $user4->id;
        $override2->timeopen = 1593997200;
        $override2->timeclose = 1594005000;  // End late.
        $override2->timelimit = 7200;

        $overriderecords = [$override1, $override2];

        $DB->insert_records('quiz_overrides', $overriderecords);

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->user3 = $user3;
        $this->user4 = $user4;
        $this->user5 = $user5;
        $this->user6 = $user6;
    }

    /**
     * Helper method to create quiz tracking records used in quiz.
     *
     * @param int $now Timestamp for tracking.
     * @param int $quizid Quiz ID to to create tracking records for.
     */
    public function setup_quiz_tracking(int $now, int $quizid): void {
        global $DB;

        $track1 = new stdClass();
        $track1->assessid = $quizid;
        $track1->notloggedin = 5;
        $track1->loggedin = 0;
        $track1->inprogress = 0;
        $track1->finished = 0;
        $track1->timecreated = $now + (60 * 1);

        $track2 = new stdClass();
        $track2->assessid = $quizid;
        $track2->notloggedin = 4;
        $track2->loggedin = 1;
        $track2->inprogress = 1;
        $track2->finished = 0;
        $track2->timecreated = $now + (60 * 2);

        $track3 = new stdClass();
        $track3->assessid = $quizid;
        $track3->notloggedin = 3;
        $track3->loggedin = 2;
        $track3->inprogress = 2;
        $track3->finished = 0;
        $track3->timecreated = $now + (60 * 3);

        $track4 = new stdClass();
        $track4->assessid = $quizid;
        $track4->notloggedin = 2;
        $track4->loggedin = 3;
        $track4->inprogress = 3;
        $track4->finished = 0;
        $track4->timecreated = $now + (60 * 4);

        $track5 = new stdClass();
        $track5->assessid = $quizid;
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
     * Test get upcomming quiz chart.
     */
    public function test_get_all_participants_inprogress_chart(): void {
        $now = 1594788000;

        $upcomming = new all_participants_inprogress();

        $result = $upcomming->get_all_participants_inprogress_chart($now);
        $this->assertFalse($result['hasdata']);

        $this->setup_quiz_tracking($now, $this->quiz3->id);
        $result = $upcomming->get_all_participants_inprogress_chart($now);
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

        $this->setup_quiz_tracking($now, $this->quiz4->id);
        $result = $upcomming->get_all_participants_inprogress_chart($now);
        $values = $result['chart']->get_series()[0]->get_values();
        $labels = $result['chart']->get_labels();
        $this->assertEquals(2, $values[0]);
        $this->assertEquals(8, $values[1]);
        $this->assertEquals(6, $values[2]);
        $this->assertEquals(2, $values[3]);
        $this->assertEquals('Not logged in', $labels[0]);
        $this->assertEquals('Logged in', $labels[1]);
        $this->assertEquals('In progress', $labels[2]);
        $this->assertEquals('Finished', $labels[3]);
    }
}
