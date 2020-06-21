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
     * @var stdClass First test user.
     */
    protected $user1;

    /**
     *
     * @var stdClass Second test user.
     */
    protected $user2;

    /**
     * Set up conditions for tests.
     */
    public function setUp() {
        $this->resetAfterTest();

        global $CFG;

        // Create a course with activity.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course(
            array('format' => 'topics', 'numsections' => 3,
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

        // Create some users.
        $user1 = $generator->create_user();
        $user2 = $generator->create_user();

        // Enrol users into the course.
        $generator->enrol_user($user1->id, $course->id, 'student');
        $generator->enrol_user($user2->id, $course->id, 'student');

        $this->user1 = $user1;
        $this->user2 = $user2;

        set_config('modules', 'quiz,assign,scorm,choice', 'local_assessfreq');
    }

    /**
     * Test ajax getting of event data.
     */
    public function test_get_frequency() {
        global $DB;
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

        $this->assertEquals(2, $eventarr[2020][3][29]['number']);
        $this->assertEquals(2, $eventarr[2020][3][28]['number']);
    }

    /**
     * Test ajax getting of event data..
     */
    public function test_get_process_modules() {

        $returnvalue = local_assessfreq_external::get_process_modules();
        $returnjson = external_api::clean_returnvalue(local_assessfreq_external::get_process_modules_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        error_log(print_r($eventarr, true));
    }
}
