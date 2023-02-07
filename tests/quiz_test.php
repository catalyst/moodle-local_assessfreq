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

namespace local_assessfreq;

use question_engine;
use quiz_attempt;
use stdClass;

/**
 * This file contains the class that handles testing of the block assess frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_assessfreq\quiz
 */
class quiz_test extends \advanced_testcase {

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
            array('format' => 'topics', 'numsections' => 3,
                'enablecompletion' => 1),
            array('createsections' => true));
        $this->quiz1 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => 1593910800,
            'timeclose' => 1593914400,
            'timelimit' => 3600,
            'layout' => $layout
        ));
        $this->quiz2 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => 1593997200,
            'timeclose' => 1594004400,
            'timelimit' => 7200
        ));

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

        // Start and end date of override is more than one hour in the past. (Should not be returned).
        $this->quiz8 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 3)),
            'timeclose' => ($now - (3600 * 2)),
            'timelimit' => 3600
        ));

        // Start is more than one hour in the past, but end is less than one hour in the past. (Should return).
        $this->quiz9 = $generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => ($now - (3600 * 2)),
            'timeclose' => ($now - (3600 * 0.5)),
            'timelimit' => 3600
        ));

        // Add questions to quiz.
        $quizobj = \quiz::create($this->quiz1->id);
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

        $overriderecords = array(
            $override1, $override2, $override3, $override4, $override5, $override6, $override7, $override8, $override9
        );

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

        $sessionrecords = array($record1, $record2, $record3, $record4, $record5, $record6);
        $DB->insert_records('sessions', $sessionrecords);

        $fakeattempt = new stdClass();
        $fakeattempt->quiz = $this->quiz3->id;
        $fakeattempt->layout = '1,2,0,3,4,0,5';

        $fakeattempt->userid = $this->user1->id;
        $fakeattempt->attempt = 3;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 13;
        $fakeattempt->state = quiz_attempt::FINISHED;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->userid = $this->user1->id;
        $fakeattempt->attempt = 2;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 26;
        $fakeattempt->state = quiz_attempt::IN_PROGRESS;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->userid = $this->user2->id;
        $fakeattempt->attempt = 1;
        $fakeattempt->sumgrades = 30;
        $fakeattempt->uniqueid = 52;
        $fakeattempt->state = quiz_attempt::ABANDONED;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->userid = $this->user3->id;
        $fakeattempt->attempt = 3;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 53;
        $fakeattempt->state = quiz_attempt::FINISHED;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->attempt = 1;
        $fakeattempt->userid = $this->user5->id;
        $fakeattempt->sumgrades = 100;
        $fakeattempt->uniqueid = 65;
        $fakeattempt->state = quiz_attempt::OVERDUE;
        $DB->insert_record('quiz_attempts', $fakeattempt);

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
        $trackrecords = array($track1, $track5, $track3, $track2, $track4);

        $DB->insert_records('local_assessfreq_trend', $trackrecords);
    }

    public function setup_mock_quiz_data(): array {
        $quizzes = array (
            208002 =>
            (object)array(
                'name' => 'very SPECIAL Quiz 3',
                'timeopen' => '15 July 2020, 10:40 AM',
                'timeclose' => '15 July 2020, 1:10 PM',
                'timelimit' => '1 hour',
                'earlyopen' => '15 July 2020, 10:40 AM',
                'lateclose' => '15 July 2020, 1:10 PM',
                'participants' => 6,
                'overrideparticipants' => 0,
                'url' => 'https://www.example.com/moodle/mod/quiz/view.php?id=354002',
                'types' =>
                array (
                ),
                'typecount' => 0,
                'questioncount' => 0,
                'resultlink' => 'https://www.example.com/moodle/mod/quiz/report.php?id=354002&mode=overview',
                'overridelink' => 'https://www.example.com/moodle/mod/quiz/overrides.php?cmid=354002&mode=user',
                'coursefullname' => 'Test course 1',
                'courseshortname' => 'tc_1',
                'courselink' => 'https://www.example.com/moodle/course/view.php?id=187000',
                'participantlink' => 'https://www.example.com/moodle/user/index.php?id=187000',
                'dashboardlink' => 'https://www.example.com/moodle/local/assessfreq/dashboard_quiz.php?id=208002',
                'timestampopen' => '1594780800',
                'timestampclose' => '1594789800',
                'tracking' =>
                (object)array(
                    'id' => '314001',
                    'assessid' => '208002',
                    'notloggedin' => '1',
                    'loggedin' => '4',
                    'inprogress' => '3',
                    'finished' => '1',
                    'timecreated' => '1594788300',
                ),
            ),
            208003 =>
            (object)array(
                'name' => 'Independent Quiz 4',
                'timeopen' => '15 July 2020, 12:10 PM',
                'timeclose' => '15 July 2020, 1:10 PM',
                'timelimit' => '1 hour',
                'earlyopen' => '15 July 2020, 12:10 PM',
                'lateclose' => '15 July 2020, 1:10 PM',
                'participants' => 6,
                'overrideparticipants' => 0,
                'url' => 'https://www.example.com/moodle/mod/quiz/view.php?id=354003',
                'types' =>
                array (
                ),
                'typecount' => 0,
                'questioncount' => 0,
                'resultlink' => 'https://www.example.com/moodle/mod/quiz/report.php?id=354003&mode=overview',
                'overridelink' => 'https://www.example.com/moodle/mod/quiz/overrides.php?cmid=354003&mode=user',
                'coursefullname' => 'Independent course 1',
                'courseshortname' => 'tc_1',
                'courselink' => 'https://www.example.com/moodle/course/view.php?id=187000',
                'participantlink' => 'https://www.example.com/moodle/user/index.php?id=187000',
                'dashboardlink' => 'https://www.example.com/moodle/local/assessfreq/dashboard_quiz.php?id=208003',
                'timestampopen' => '1594786200',
                'timestampclose' => '1594789800',
                'tracking' =>
                (object)array(
                    'id' => '314006',
                    'assessid' => '208003',
                    'notloggedin' => '1',
                    'loggedin' => '4',
                    'inprogress' => '3',
                    'finished' => '1',
                    'timecreated' => '1594788300',
                ),
            ),
        );

        return $quizzes;
    }

    /**
     * Test getting quiz override info.
     */
    public function test_get_quiz_override_info() {
        $quizdata = new quiz();
        $context = \context_module::instance($this->quiz1->cmid);

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'get_quiz_override_info');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($quizdata, $this->quiz1->id, $context);

        $this->assertEquals(1593996000, $result->start);
        $this->assertEquals(1594005000, $result->end);
        $this->assertEquals(2, $result->users);

    }

    /**
     * Test getting quiz question information.
     */
    public function test_get_quiz_questions() {
        $quizdata = new quiz();

        // We're testing a private method, so we need to setup reflector magic.
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'get_quiz_questions');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($quizdata, $this->quiz1->id);

        $this->assertEquals(2, $result->typecount);
        $this->assertEquals(6, $result->questioncount);

    }

    /**
     * Test getting quiz data.
     */
    public function test_get_quiz_data() {

        $quizdata = new quiz();
        $result = $quizdata->get_quiz_data($this->quiz1->id);

        $this->assertEquals('5 July 2020, 9:00 AM', $result->earlyopen);
        $this->assertEquals('6 July 2020, 11:10 AM', $result->lateclose);
        $this->assertEquals(6, $result->participants);
        $this->assertEquals($this->quiz1->name, $result->name);
        $this->assertEquals(2, $result->overrideparticipants);
        $this->assertEquals(2, $result->typecount);
        $this->assertEquals(6, $result->questioncount);

    }

    /**
     * Test quiz override tracking.
     */
    public function test_get_tracked_overrides() {
        $quizdata = new quiz();
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'get_tracked_overrides');
        $method->setAccessible(true); // Allow accessing of private method.

        $now = 1594788000;
        $result = $method->invoke($quizdata, $now, HOURSECS, HOURSECS);

        $this->assertCount(5, $result);

        foreach ($result as $override) {
            $this->assertNotEquals(1, $override->quiz);
            $this->assertNotEquals(7, $override->quiz);
        }
    }

    /**
     * Test quiz tracking.
     */
    public function test_get_tracked_quizzes() {
        $quizdata = new quiz();
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'get_tracked_quizzes');
        $method->setAccessible(true); // Allow accessing of private method.

        $now = 1594788000;
        $result = $method->invoke($quizdata, $now, HOURSECS, HOURSECS);

        $this->assertCount(5, $result);

        foreach ($result as $override) {
            $this->assertNotEquals($this->quiz7->id, $override->id);
            $this->assertNotEquals($this->quiz8->id, $override->id);
        }
    }

    /**
     * Test quiz tracking with overrides.
     */
    public function test_get_tracked_quizzes_with_overrides() {
        global $DB;
        $now = 1594788000;

        // Add an extra override for this test.
        // Start is less than one hour in the past, but end is in the future. (Should return).
        $override = new stdClass();
        $override->quiz = $this->quiz4->id; // OK to use fake id for this.
        $override->userid = 7; // OK to use fake id for this.
        $override->timeopen = ($now - (3600 * 0.75));
        $override->timeclose = ($now + (3600 * 0.25));
        $override->timelimit = 3600;

        $DB->insert_record('quiz_overrides', $override);

        $quizdata = new quiz();
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'get_tracked_quizzes_with_overrides');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($quizdata, $now);

        // Get override students array for test quiz.
        foreach ($result[$this->quiz4->id]->overrides as $quizoverride) {
            if ($override->userid == 7) {
                $this->assertEquals(($now - (3600 * 0.75)), $quizoverride->timeopen);
                $this->assertEquals(($now + (3600 * 0.25)), $quizoverride->timeclose);
            }
        }

        // Time open and time close should be the same as original quiz. Override should added as an array.
        $this->assertEquals(($now - (3600 * 0.5)), $result[$this->quiz4->id]->timeopen);
        $this->assertEquals(($now + (3600 * 0.5)), $result[$this->quiz4->id]->timeclose);

        $this->assertCount(5, $result);

    }

    /**
     * Test getting logged in users.
     */
    public function test_get_loggedin_users() {
        $userids = array(
            $this->user1->id,
            $this->user2->id,
            $this->user3->id,
            $this->user4->id,
            ($this->user4->id + 123),
        );

        $quizdata = new quiz();
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'get_loggedin_users');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($quizdata, $userids);

        $this->assertEquals(3, $result->loggedin);
        $this->assertEquals(2, $result->loggedout);
    }

    /**
     * Test quiz tracking with overrides.
     */
    public function test_get_quiz_attempts() {

        $quizdata = new quiz();
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'get_quiz_attempts');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($quizdata, $this->quiz3->id);

        $this->assertEquals(2, $result->inprogress);
        $this->assertEquals(2, $result->finished);
    }

    /**
     * Test processing quiz tracking.
     */
    public function test_process_quiz_tracking() {
        global $DB;
        $now = 1594788000;

        $quizdata = new quiz();
        $method = new \ReflectionMethod('\local_assessfreq\quiz', 'process_quiz_tracking');
        $method->setAccessible(true); // Allow accessing of private method.

        $result = $method->invoke($quizdata, $now);

        $this->assertEquals(5, $result);

        $trendrecords = $DB->get_records('local_assessfreq_trend');
        foreach ($trendrecords as $trendrecord) {
            if ($trendrecord->assessid == $this->quiz3->id) {
                $this->assertEquals(2, $trendrecord->inprogress);
                $this->assertEquals(1, $trendrecord->loggedin);
                $this->assertEquals(2, $trendrecord->finished);
                $this->assertEquals(1, $trendrecord->notloggedin);
            }
        }
    }

    /**
     * Test processing quiz tracking .
     */
    public function test_get_quiz_tracking() {
        $now = 1594788000;
        $this->setup_quiz_tracking($now, $this->quiz1->id);

        $quizdata = new quiz();
        $trackingdata = $quizdata->get_quiz_tracking($this->quiz1->id);

        $this->assertEquals($now + (60 * 5), array_pop($trackingdata)->timecreated);
        $this->assertEquals($now + (60 * 4), array_pop($trackingdata)->timecreated);
        $this->assertEquals($now + (60 * 3), array_pop($trackingdata)->timecreated);
        $this->assertEquals($now + (60 * 2), array_pop($trackingdata)->timecreated);
        $this->assertEquals($now + (60 * 1), array_pop($trackingdata)->timecreated);

    }

    /**
     * Test getting in progress quiz counts.
     */
    public function test_get_inprogress_counts() {
        $now = 1594788000;
        $this->setup_quiz_tracking($now, $this->quiz3->id);
        $this->setup_quiz_tracking($now, $this->quiz4->id);

        $quizdata = new quiz();
        $result = $quizdata->get_inprogress_counts($now);

        $this->assertEquals(2, $result['assessments']);
        $this->assertEquals(6, $result['participants']);

    }

    /**
     * Test getting finished quizzes.
     */
    public function test_get_quizzes_finished() {
        $quizdata = new quiz();

        $now = 1594788000;
        $result = $quizdata->get_quiz_summaries($now);

        $this->assertCount(1, $result['finished'][$now - HOURSECS]);

    }

    /**
     * Test getting in progress quizzes.
     */
    public function test_get_quizzes_inprogress() {
        $now = 1594788000;
        $this->setup_quiz_tracking($now, $this->quiz3->id);
        $this->setup_quiz_tracking($now, $this->quiz4->id);

        $quizdata = new quiz();
        $result = $quizdata->get_quiz_summaries($now);

        $this->assertCount(2, $result['inprogress']);
        $this->assertLessThan($now, $result['inprogress'][$this->quiz3->id]->timestampopen);
        $this->assertGreaterThan($now, $result['inprogress'][$this->quiz3->id]->timestampclose);
        $this->assertLessThan($now, $result['inprogress'][$this->quiz4->id]->timestampopen);
        $this->assertGreaterThan($now, $result['inprogress'][$this->quiz4->id]->timestampclose);

    }

    /**
     * Test getting upcomming quizzes.
     */
    public function test_get_quizzes_upcomming() {
        $quizdata = new quiz();

        $now = 1594780800;
        $result = $quizdata->get_quiz_summaries($now);

        $this->assertCount(2, $result['upcomming'][$now]);
        $this->assertCount(1, $result['upcomming'][$now + HOURSECS]);
        $this->assertCount(2, $result['upcomming'][$now + (HOURSECS * 2)]);
        $this->assertCount(0, $result['upcomming'][$now + (HOURSECS * 3)]);

        $now = 1594788000;
        $result = $quizdata->get_quiz_summaries($now);

        $this->assertCount(2, $result['upcomming'][$now]);
        $this->assertCount(0, $result['upcomming'][$now + HOURSECS]);
        $this->assertCount(1, $result['upcomming'][$now + (HOURSECS * 2)]);
        $this->assertCount(0, $result['upcomming'][$now + (HOURSECS * 3)]);

    }

    /**
     * Test filtering quizzes.
     */
    public function test_filter_quizzes() {
        // Mock data.
        $quizzes = $this->setup_mock_quiz_data();

        $quizdata = new quiz();

        $search = 'special';
        $filtered = $quizdata->filter_quizzes($quizzes, $search, 0, 10);
        $this->assertCount(1, $filtered[0]);
        $this->assertEquals('very SPECIAL Quiz 3', $filtered[0][208002]->name);

        $search = 'Independent';
        $filtered = $quizdata->filter_quizzes($quizzes, $search, 0, 10);
        $this->assertCount(1, $filtered[0]);
        $this->assertEquals('Independent course 1', $filtered[0][208003]->coursefullname);
    }

    /**
     * Test filtering quizzes with pages.
     */
    public function test_filter_quizzes_paging() {
        // Mock data.
        $quizzes = $this->setup_mock_quiz_data();

        $quizdata = new quiz();
        $search = 'quiz';

        $filtered = $quizdata->filter_quizzes($quizzes, $search, 0, 10);
        $this->assertCount(2, $filtered[0]);

        $filtered = $quizdata->filter_quizzes($quizzes, $search, 0, 1);
        $this->assertCount(1, $filtered[0]);
        $this->assertEquals('very SPECIAL Quiz 3', $filtered[0][208002]->name);

        $filtered = $quizdata->filter_quizzes($quizzes, $search, 1, 1);
        $this->assertCount(1, $filtered[0]);
        $this->assertEquals('Independent course 1', $filtered[0][208003]->coursefullname);
    }

    /**
     * Test filtering quizzes with pages.
     */
    public function test_sort_quizzes() {
        // Mock data.
        $quizzes = $this->setup_mock_quiz_data();

        $quizdata = new quiz();
        $sorton = 'name';

        $sorted = \local_assessfreq\utils::sort($quizzes, $sorton, 'asc');
        $this->assertEquals('Independent Quiz 4', $sorted[208003]->name);

        $sorted = \local_assessfreq\utils::sort($quizzes, $sorton, 'desc');
        $this->assertEquals('very SPECIAL Quiz 3', $sorted[208002]->name);

        $sorton = 'coursefullname';

        $sorted = \local_assessfreq\utils::sort($quizzes, $sorton, 'asc');
        $this->assertEquals('Independent course 1', $sorted[208003]->coursefullname);

        $sorted = \local_assessfreq\utils::sort($quizzes, $sorton, 'desc');
        $this->assertEquals('Test course 1', $sorted[208002]->coursefullname);

    }
}
