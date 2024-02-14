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

namespace local_assessfreq\output;

use question_engine;
use context_system;
use stdClass;

/**
 * This file contains the class that handles testing of the block assess frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_assessfreq\output\student_search_table
 */
class student_search_table_test extends \advanced_testcase {
    /**
     *
     * @var stdClass $course Test course.
     */
    protected $course;

    /**
     *
     * @var stdClass First test quiz.
     */
    protected $quiz1;

    /**
     *
     * @var stdClass Second test quiz.
     */
    protected $quiz2;

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
     * @var stdClass Second test quiz.
     */
    protected $quiz8;

    /**
     *
     * @var stdClass Second test quiz.
     */
    protected $quiz9;

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
        $this->quiz1 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => 1593910800,
            'timeclose' => 1593914400,
            'timelimit' => 3600,
            'layout' => $layout,
        ]);
        $this->quiz2 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => 1593997200,
            'timeclose' => 1594004400,
            'timelimit' => 7200,
        ]);

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

        // Start and end date of override is more than one hour in the past. (Should not be returned).
        $this->quiz8 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 3)),
            'timeclose' => ($now - (3600 * 2)),
            'timelimit' => 3600,
        ]);

        // Start is more than one hour in the past, but end is less than one hour in the past. (Should return).
        $this->quiz9 = $generator->create_module('quiz', [
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 2)),
            'timeclose' => ($now - (3600 * 0.5)),
            'timelimit' => 3600,
        ]);

        // Add questions to quiz.
        $quizobj = \mod_quiz\quiz_settings::create($this->quiz1->id);
        $quba = question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);

        $questiongenerator = $generator->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $page = 1;
        foreach (explode(',', $layout) as $slot) {
            if ($slot == 0) {
                $page += 1;
                continue;
            }

            if ($slot % 2 == 0) {
                $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
            } else {
                $question = $questiongenerator->create_question('essay', null, ['category' => $cat->id]);
            }

            quiz_add_quiz_question($question->id, $this->quiz1, $page);
        }

        $this->course = $course;

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
        $override1->quiz = $this->quiz1->id;
        $override1->userid = $user3->id;
        $override1->timeopen = 1593996000; // Open early.
        $override1->timeclose = 1594004400;
        $override1->timelimit = 7200;

        $override2 = new stdClass();
        $override2->quiz = $this->quiz1->id;
        $override2->userid = $user4->id;
        $override2->timeopen = 1593997200;
        $override2->timeclose = 1594005000;  // End late.
        $override2->timelimit = 7200;

        // Start is more than one hour in the past, but end is in the future. (Should return).
        $override3 = new stdClass();
        $override3->quiz = 3; // OK to use fake id for this.
        $override3->userid = 5; // OK to use fake id for this.
        $override3->timeopen = ($now - (3600 * 2));
        $override3->timeclose = ($now + (3600 * 0.5));
        $override3->timelimit = 3600;

        // Start is less than one hour in the past, but end is in the future. (Should return).
        $override4 = new stdClass();
        $override4->quiz = 4; // OK to use fake id for this.
        $override4->userid = 6; // OK to use fake id for this.
        $override4->timeopen = ($now - (3600 * 0.5));
        $override4->timeclose = ($now + (3600 * 0.5));
        $override4->timelimit = 3600;

        // Start is less than one hour in the future, end is more than one hour in the future. (Should return).
        $override5 = new stdClass();
        $override5->quiz = 5; // OK to use fake id for this.
        $override5->userid = 7; // OK to use fake id for this.
        $override5->timeopen = ($now + (3600 * 0.5));
        $override5->timeclose = ($now + (3600 * 2));
        $override5->timelimit = 3600;

        // Start is less than one hour in the future, end is less that one hour in the future. (Should return).
        $override6 = new stdClass();
        $override6->quiz = 6; // OK to use fake id for this.
        $override6->userid = 8; // OK to use fake id for this.
        $override6->timeopen = ($now + (3600 * 0.25));
        $override6->timeclose = ($now + (3600 * 0.75));
        $override6->timelimit = 1800;

        // Start is more than one hour in the future, end is more than one hour in the future. (Should not return).
        $override7 = new stdClass();
        $override7->quiz = 7; // OK to use fake id for this.
        $override7->userid = 9; // OK to use fake id for this.
        $override7->timeopen = ($now + (3600 * 2));
        $override7->timeclose = ($now + (3600 * 3));
        $override7->timelimit = 3600;

        // Start and end date of override is more than one hour in the past. (Should not be returned).
        $override8 = new stdClass();
        $override8->quiz = 1; // OK to use fake id for this.
        $override8->userid = 3; // OK to use fake id for this.
        $override8->timeopen = ($now - (3600 * 3));
        $override8->timeclose = ($now - (3600 * 2));
        $override8->timelimit = 3600;

        // Start is more than one hour in the past, but end is less than one hour in the past. (Should return).
        $override9 = new stdClass();
        $override9->quiz = 2; // OK to use fake id for this.
        $override9->userid = 4; // OK to use fake id for this.
        $override9->timeopen = ($now - (3600 * 2));
        $override9->timeclose = ($now - (3600 * 0.5));
        $override9->timelimit = 3600;

        $overriderecords = [
            $override1, $override2, $override3, $override4, $override5, $override6, $override7, $override8, $override9,
        ];

        $DB->insert_records('quiz_overrides', $overriderecords);

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->user3 = $user3;
        $this->user4 = $user4;
        $this->user5 = $user5;
        $this->user6 = $user6;

        $CFG->sessiontimeout = 60 * 10;  // Short time out for test.

        $record1 = new stdClass();
        $record1->state = 0;
        $record1->sid = md5('sid1');
        $record1->sessdata = null;
        $record1->userid = $this->user1->id;
        $record1->timecreated = time() - 60 * 60;
        $record1->timemodified = time() - 30;
        $record1->firstip = '10.0.0.1';
        $record1->lastip = '10.0.0.1';

        $record2 = new stdClass();
        $record2->state = 0;
        $record2->sid = md5('sid2');
        $record2->sessdata = null;
        $record2->userid = $this->user2->id;
        $record2->timecreated = time() - 60 * 60;
        $record2->timemodified = time() - 30;
        $record2->firstip = '10.0.0.1';
        $record2->lastip = '10.0.0.1';

        $record3 = new stdClass();
        $record3->state = 0;
        $record3->sid = md5('sid3');
        $record3->sessdata = null;
        $record3->userid = $this->user3->id;
        $record3->timecreated = time() - 60 * 60;
        $record3->timemodified = time() - 30;
        $record3->firstip = '10.0.0.1';
        $record3->lastip = '10.0.0.1';

        $record4 = new stdClass();
        $record4->state = 0;
        $record4->sid = md5('sid4');
        $record4->sessdata = null;
        $record4->userid = $this->user4->id;
        $record4->timecreated = time() - 60 * 60;
        $record4->timemodified = time() - 60 * 60;
        $record4->firstip = '10.0.0.1';
        $record4->lastip = '10.0.0.1';

        $record5 = new stdClass();
        $record5->state = 0;
        $record5->sid = md5('sid5');
        $record5->sessdata = null;
        $record5->userid = $this->user5->id;
        $record5->timecreated = time() - 60 * 60;
        $record5->timemodified = time() - 30;
        $record5->firstip = '10.0.0.1';
        $record5->lastip = '10.0.0.1';

        $record6 = new stdClass();
        $record6->state = 0;
        $record6->sid = md5('sid6');
        $record6->sessdata = null;
        $record6->userid = $this->user6->id;
        $record6->timecreated = time() - 60 * 60;
        $record6->timemodified = time() - 30;
        $record6->firstip = '10.0.0.1';
        $record6->lastip = '10.0.0.1';

        $sessionrecords = [$record1, $record2, $record3, $record4, $record5, $record6];
        $DB->insert_records('sessions', $sessionrecords);

        $fakeattempt = new stdClass();
        $fakeattempt->quiz = $this->quiz3->id;
        $fakeattempt->layout = '1,2,0,3,4,0,5';

        $fakeattempt->userid = $this->user1->id;
        $fakeattempt->attempt = 3;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 13;
        $fakeattempt->state = \mod_quiz\quiz_attempt::FINISHED;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->userid = $this->user1->id;
        $fakeattempt->attempt = 2;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 26;
        $fakeattempt->timestart = 1;
        $fakeattempt->state = \mod_quiz\quiz_attempt::IN_PROGRESS;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->userid = $this->user2->id;
        $fakeattempt->attempt = 1;
        $fakeattempt->sumgrades = 30;
        $fakeattempt->uniqueid = 52;
        $fakeattempt->state = \mod_quiz\quiz_attempt::ABANDONED;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->userid = $this->user3->id;
        $fakeattempt->attempt = 3;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 53;
        $fakeattempt->state = \mod_quiz\quiz_attempt::FINISHED;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->attempt = 1;
        $fakeattempt->userid = $this->user5->id;
        $fakeattempt->sumgrades = 100;
        $fakeattempt->uniqueid = 65;
        $fakeattempt->state = \mod_quiz\quiz_attempt::OVERDUE;
        $DB->insert_record('quiz_attempts', $fakeattempt);
    }
    /**
     * Test getting table data.
     */
    public function test_get_table_data(): void {
        global $CFG;

        $baseurl = $CFG->wwwroot . '/local/assessfreq/dashboard_quiz.php';
        $context = context_system::instance();
        $now = 1594788000;
        $quizusertable = new student_search_table($baseurl, $context->id, '', 1, 1, $now);

        // Fake getting table.
        $this->expectOutputRegex("/table/");
        $quizusertable->out(1, false);

        // Query data.
        set_user_preference('local_assessfreq_student_search_table_rows_preference', 30);
        $quizusertable->query_db(30, false);
        $rawdata = $quizusertable->rawdata;

        $this->assertCount(30, $rawdata);
    }
}
