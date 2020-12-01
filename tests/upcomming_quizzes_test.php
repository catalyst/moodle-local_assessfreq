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
 * This file contains the class that handles testing of the upcomming quizzes class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

use local_assessfreq\output\upcomming_quizzes;

/**
 * This file contains the class that handles testing of the upcomming quizzes class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class upcomming_quizzes_test extends advanced_testcase {

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
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => 1),
            array('createsections' => true));

        // Start is more than one hour in the past, but end is in the future. (Should return).
        $this->quiz3 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 2)),
            'timeclose' => ($now + (3600 * 0.5)),
            'timelimit' => 3600
        ));

        // Start is less than one hour in the past, but end is in the future. (Should return).
        $this->quiz4 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 0.5)),
            'timeclose' => ($now + (3600 * 0.5)),
            'timelimit' => 3600
        ));

        // Start is less than one hour in the future, end is more than one hour in the future. (Should return).
        $this->quiz5 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => ($now + (3600 * 0.5)),
            'timeclose' => ($now + (3600 * 2)),
            'timelimit' => 3600
        ));

        // Start is less than one hour in the future, end is less that one hour in the future. (Should return).
        $this->quiz6 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => ($now + (3600 * 0.25)),
            'timeclose' => ($now + (3600 * 0.75)),
            'timelimit' => 1800
        ));

        // Start is more than one hour in the future, end is more than one hour in the future. (Should not return).
        $this->quiz7 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => ($now + (3600 * 2)),
            'timeclose' => ($now + (3600 * 3)),
            'timelimit' => 3600
        ));

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
        $override1 = new \stdClass();
        $override1->quiz = $this->quiz3->id;
        $override1->userid = $user3->id;
        $override1->timeopen = 1593996000; // Open early.
        $override1->timeclose = 1594004400;
        $override1->timelimit = 7200;

        $override2 = new \stdClass();
        $override2->quiz = $this->quiz4->id;
        $override2->userid = $user4->id;
        $override2->timeopen = 1593997200;
        $override2->timeclose = 1594005000;  // End late.
        $override2->timelimit = 7200;

        $overriderecords = array($override1, $override2);

        $DB->insert_records('quiz_overrides', $overriderecords);

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->user3 = $user3;
        $this->user4 = $user4;
        $this->user5 = $user5;
        $this->user6 = $user6;

    }

    /**
     * Test get upcomming quiz chart.
     */
    public function test_get_upcomming_quizzes_chart() {
        $now = 1594780800;
        $upcomming = new upcomming_quizzes();
        $result = $upcomming->get_upcomming_quizzes_chart($now);

        $quizvalues = $result['chart']->get_series()[0]->get_values();
        $participantvalues  = $result['chart']->get_series()[1]->get_values();

        $this->assertEquals(1, $quizvalues[0]);
        $this->assertEquals(1, $quizvalues[1]);
        $this->assertEquals(2, $quizvalues[2]);
        $this->assertEquals(0, $quizvalues[3]);
        $this->assertEquals(1, $quizvalues[4]);
        $this->assertEquals(0, $quizvalues[5]);

        $this->assertEquals(6, $participantvalues[0]);
        $this->assertEquals(6, $participantvalues[1]);
        $this->assertEquals(12, $participantvalues[2]);
        $this->assertEquals(0, $participantvalues[3]);
        $this->assertEquals(6, $participantvalues[4]);
        $this->assertEquals(0, $participantvalues[5]);

    }
}
