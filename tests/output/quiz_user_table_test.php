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

use context_system;
use stdClass;

/**
 * This file contains the class that handles testing of the block assess frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_assessfreq\output\quiz_user_table
 */
class quiz_user_table_test extends \advanced_testcase {
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

        $overriderecords = [$override1, $override2];

        $DB->insert_records('quiz_overrides', $overriderecords);

        $this->user1 = $user1;
        $this->user2 = $user2;
        $this->user3 = $user3;
        $this->user4 = $user4;

        $CFG->sessiontimeout = 60 * 10;  // Short time out for test.

        $record4 = new stdClass();
        $record4->state = 0;
        $record4->sid = md5('sid4');
        $record4->sessdata = null;
        $record4->userid = $this->user4->id;
        $record4->timecreated = time() - 60 * 60;
        $record4->timemodified = time() - 30;
        $record4->firstip = '10.0.0.1';
        $record4->lastip = '10.0.0.1';

        $sessionrecords = [$record4];
        $DB->insert_records('sessions', $sessionrecords);

        $fakeattempt = new stdClass();
        $fakeattempt->userid = $user1->id;
        $fakeattempt->timestart = time();
        $fakeattempt->quiz = $this->quiz1->id;
        $fakeattempt->layout = '1,2,0,3,4,0,5';
        $fakeattempt->attempt = 1;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 13;
        $fakeattempt->state = \mod_quiz\quiz_attempt::FINISHED;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->timestart = time() + 30;
        $fakeattempt->attempt = 2;
        $fakeattempt->sumgrades = 50;
        $fakeattempt->uniqueid = 26;
        $fakeattempt->state = \mod_quiz\quiz_attempt::IN_PROGRESS;
        $DB->insert_record('quiz_attempts', $fakeattempt);

        $fakeattempt->userid = $user2->id;
        $fakeattempt->attempt = 1;
        $fakeattempt->sumgrades = null;
        $fakeattempt->uniqueid = 39;
        $fakeattempt->state = \mod_quiz\quiz_attempt::FINISHED;
        $DB->insert_record('quiz_attempts', $fakeattempt);
    }

    /**
     * Test getting table data.
     */
    public function test_get_table_data(): void {
        global $CFG;

        $baseurl = $CFG->wwwroot . '/local/assessfreq/dashboard_quiz.php';
        $context = context_system::instance();
        $quizusertable = new quiz_user_table($baseurl, $this->quiz1->id, $context->id, '');

        // Fake getting table.
        $this->expectOutputRegex("/table/");
        $quizusertable->out(1, false);

        // Query data.
        $quizusertable->query_db(20, false);
        $rawdata = $quizusertable->rawdata;

        $this->assertCount(4, $rawdata);
        $this->assertEquals(4, $quizusertable->totalrows);

        $this->assertEquals($this->quiz1->timeopen, $rawdata[$this->user1->id]->timeopen);
        $this->assertEquals($this->quiz1->timeclose, $rawdata[$this->user1->id]->timeclose);
        $this->assertEquals($this->quiz1->timelimit, $rawdata[$this->user1->id]->timelimit);
        $this->assertEquals('inprogress', $rawdata[$this->user1->id]->state);

        $this->assertEquals($this->quiz1->timeopen, $rawdata[$this->user2->id]->timeopen);
        $this->assertEquals($this->quiz1->timeclose, $rawdata[$this->user2->id]->timeclose);
        $this->assertEquals($this->quiz1->timelimit, $rawdata[$this->user2->id]->timelimit);
        $this->assertEquals('finished', $rawdata[$this->user2->id]->state);

        $this->assertEquals(1593996000, $rawdata[$this->user3->id]->timeopen);
        $this->assertEquals(1594004400, $rawdata[$this->user3->id]->timeclose);
        $this->assertEquals(7200, $rawdata[$this->user3->id]->timelimit);
        $this->assertEquals('notloggedin', $rawdata[$this->user3->id]->state);

        $this->assertEquals(1593997200, $rawdata[$this->user4->id]->timeopen);
        $this->assertEquals(1594005000, $rawdata[$this->user4->id]->timeclose);
        $this->assertEquals(7200, $rawdata[$this->user4->id]->timelimit);
        $this->assertEquals('loggedin', $rawdata[$this->user4->id]->state);
    }
}
