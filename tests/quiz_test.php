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

defined('MOODLE_INTERNAL') || die();

use local_assessfreq\quiz;

/**
 * This file contains the class that handles testing of the block assess frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_testcase extends advanced_testcase {

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
     * @var stdClass Second test user.
     */
    protected $user3;

    /**
     *
     * @var stdClass Second test user.
     */
    protected $user4;

    /**
     * Set up conditions for tests.
     */
    public function setUp() {
        $this->resetAfterTest();

        global $CFG, $DB;

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
        $this->quiz2 =$generator->create_module('quiz', array(
            'course' => $course->id,
            'timeopen' => 1593997200,
            'timeclose' => 1594004400,
            'timelimit' => 7200
        ));

        // Add questions to quiz;
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

            } else{
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

        // Enrol users into the course.
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');
        $generator->enrol_user($user3->id, $course->id, 'student');
        $generator->enrol_user($user4->id, $course->id, 'student');

        // Set up a couple of overrides.
        $override1 = new \stdClass();
        $override1->quiz = $this->quiz1->id;
        $override1->userid = $user3->id;
        $override1->timeopen = 1593996000; // Open early.
        $override1->timeclose = 1594004400;
        $override1->timelimit = 7200;

        $override2 = new \stdClass();
        $override2->quiz = $this->quiz1->id;
        $override2->userid = $user4->id;
        $override2->timeopen = 1593997200;
        $override2->timeclose = 1594005000;  // End late.
        $override2->timelimit = 7200;

        $overriderecords = array($override1, $override2);
        $DB->insert_records('quiz_overrides', $overriderecords);

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->user3 = $user3;
        $this->user3 = $user4;

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
        $this->assertContains('essay', $result->types);
        $this->assertContains('shortanswer', $result->types);

    }

    /**
     * Test getting quiz data.
     */
    public function test_get_quiz_data() {

        $quizdata = new quiz();
        $result = $quizdata->get_quiz_data($this->quiz1->id);

        $this->assertEquals(1593996000, $result->earlyopen);
        $this->assertEquals(1594005000, $result->lateclose);
        $this->assertEquals(4, $result->participants);
        $this->assertEquals($this->quiz1->name, $result->name);
        $this->assertEquals(2, $result->overrideparticipants);
        $this->assertEquals(2, $result->typecount);
        $this->assertEquals(6, $result->questioncount);
        $this->assertContains('essay', $result->types);
        $this->assertContains('shortanswer', $result->types);

    }

    /**
     * Test quiz tracking processing.
     */
    public function test_process_quiz_tracking() {

        $quizdata = new quiz();
        $result = $quizdata->process_quiz_tracking();

        error_log($result);
    }

}
