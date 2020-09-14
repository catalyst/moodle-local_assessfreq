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
 * This file contains the class that handles testing of the local assess webservice class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

use local_assessfreq\frequency;

/**
 * This file contains the class that handles testing of the local assess webservice class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class local_assessfreq_external_testcase extends advanced_testcase {

    /**
     *
     * @var stdClass $course Test course.
     */
    protected $course;

    /**
     *
     * @var stdClass First test assign.
     */
    protected $assign1;

    /**
     *
     * @var stdClass Second test assign.
     */
    protected $assign2;

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
            array('fullname' => 'blue course', 'format' => 'topics', 'numsections' => 3,
                'enablecompletion' => 1),
            array('createsections' => true));
        $assignrow1 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585359375
        ));
        $assignrow2 = $generator->create_module('assign', array(
            'course' => $course->id,
            'duedate' => 1585445775
        ));
        $this->assign1 = new assign(context_module::instance($assignrow1->cmid), false, false);
        $this->assign2 = new assign(context_module::instance($assignrow2->cmid), false, false);
        $this->course = $course;

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

        set_config('modules', 'quiz,assign,scorm,choice', 'local_assessfreq');
    }


    /**
     * Test ajax getting of event data.
     */
    public function test_get_frequency() {
        $this->setAdminUser();

        $duedate = 0;
        $data = new \stdClass;
        $data->year  = 2020;
        $data->metric = 'assess'; // Can be assess or students.
        $data->modules = array('all');

        $jsondata = json_encode($data);

        $returnvalue = local_assessfreq_external::get_frequency($jsondata);
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_frequency_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertEmpty($eventarr);

        $frequency = new frequency();
        $frequency->process_site_events($duedate);
        $frequency->process_user_events($duedate);

        $returnvalue = local_assessfreq_external::get_frequency($jsondata);
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_frequency_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertEquals(1, $eventarr[2020][3][29]['number']);
        $this->assertEquals(1, $eventarr[2020][3][28]['number']);

        $data->metric = 'students';
        $jsondata = json_encode($data);
        $returnvalue = local_assessfreq_external::get_frequency($jsondata);
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_frequency_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertEquals(4, $eventarr[2020][3][29]['number']);
        $this->assertEquals(4, $eventarr[2020][3][28]['number']);
    }

    /**
     * Test ajax getting of event data.
     */
    public function test_get_process_modules() {
        global $DB;

        $DB->set_field('modules', 'visible', '0', array('name' => 'scorm'));
        $DB->set_field('modules', 'visible', '0', array('name' => 'choice'));

        set_config('modules', 'quiz,assign,scorm,choice', 'local_assessfreq');
        set_config('disabledmodules', '0', 'local_assessfreq');

        $returnvalue = local_assessfreq_external::get_process_modules();
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_process_modules_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertArrayHasKey('quiz', $eventarr);
        $this->assertArrayHasKey('assign', $eventarr);
        $this->assertArrayNotHasKey('scorm', $eventarr);
        $this->assertArrayNotHasKey('choice', $eventarr);

        set_config('disabledmodules', '1', 'local_assessfreq');
        $returnvalue = local_assessfreq_external::get_process_modules();
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_process_modules_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertArrayHasKey('quiz', $eventarr);
        $this->assertArrayHasKey('assign', $eventarr);
        $this->assertArrayHasKey('scorm', $eventarr);
        $this->assertArrayHasKey('choice', $eventarr);

    }

    /**
     * Test ajax getting of day event data.
     */
    public function test_get_day_events() {
        $this->setAdminUser();

        $frequency = new frequency();
        $frequency->process_site_events(0);
        $frequency->process_user_events(0);

        $data = new \stdClass;
        $data->date  = '2020-03-28';
        $data->modules = array('all');

        $jsondata = json_encode($data);

        $returnvalue = local_assessfreq_external::get_day_events($jsondata);
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_day_events_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertEquals('assign', $eventarr[0]['module']);
        $this->assertEquals(4, $eventarr[0]['usercount']);
        $this->assertEquals(2020, $eventarr[0]['endyear']);
        $this->assertEquals(3, $eventarr[0]['endmonth']);
        $this->assertEquals(28, $eventarr[0]['endday']);
    }

    /**
     * Test ajax getting of course names.
     */
    public function test_get_courses() {
        $this->setAdminUser();

        $query = 'blu';

        $returnvalue = local_assessfreq_external::get_courses($query);
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_courses_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertEquals('blue course', $eventarr[0]['fullname']);
    }

    /**
     * Test ajax getting of quiz names.
     */
    public function test_get_quizzes() {
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $generator->create_module('quiz', array(
            'course' => $this->course->id
        ));
        $generator->create_module('quiz', array(
            'course' => $this->course->id
        ));

        $query = $this->course->id;

        $returnvalue = local_assessfreq_external::get_quizzes($query);
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_quizzes_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertCount(4, $eventarr);
    }

    /**
     * Test ajax getting of quiz names.
     */
    public function test_get_quiz_data() {
        $this->setAdminUser();

        $quizid = $this->quiz1->id;

        $returnvalue = local_assessfreq_external::get_quiz_data($quizid);
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_quiz_data_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        $this->assertEquals('6 July 2020, 8:40 AM', $eventarr['earlyopen']);
        $this->assertEquals('6 July 2020, 11:10 AM', $eventarr['lateclose']);
        $this->assertEquals(4, $eventarr['participants']);
        $this->assertEquals($this->quiz1->name, $eventarr['name']);
        $this->assertEquals(2, $eventarr['overrideparticipants']);
        $this->assertEquals(2, $eventarr['typecount']);
        $this->assertEquals(6, $eventarr['questioncount']);
        $this->assertContains('essay', $eventarr['types']);
        $this->assertContains('shortanswer', $eventarr['types']);
    }

    /**
     * Test setting table preferences.
     */
    public function test_set_table_preference() {
        global $SESSION;
        $this->setAdminUser();

        $tableid = 'test_table';
        $preference = 'collapse';
        $values = '{"fullname":0,"username":1,"idnumber":0,"email":0}';

        $returnvalue = local_assessfreq_external::set_table_preference($tableid, $preference, $values);
        $return = external_api::clean_returnvalue(local_assessfreq_external::set_table_preference_returns(), $returnvalue);

        $this->assertEquals('collapse', $return);
        $this->assertEquals(0, $SESSION->flextable[$tableid]['collapse']['fullname']);
        $this->assertEquals(1, $SESSION->flextable[$tableid]['collapse']['username']);
        $this->assertEquals(0, $SESSION->flextable[$tableid]['collapse']['idnumber']);
        $this->assertEquals(0, $SESSION->flextable[$tableid]['collapse']['email']);

    }
}
