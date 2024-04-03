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
 * A scheduled task to track the process of quizzes in the system.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assessfreqsource_quiz\task;

use assessfreqsource_quiz\source;
use core\task\scheduled_task;
use local_assessfreq\frequency;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/assessfreq/lib.php');

/**
 * A scheduled task to track the process of quizzes in the system.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_tracking extends scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() : string {
        return get_string('task:quiztracking', 'assessfreqsource_quiz');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() : void {
        global $DB;
        mtrace('assessfreqsource_quiz: Processing quiz tracking');

        $actionstart = time();

        $source = new source();
        $frequency = new frequency();

        $quizzes = $source->get_tracked_quizzes_with_overrides($actionstart);
        $quizusersbyquizid = [];
        $count = 0;

        foreach ($quizzes as $quiz) {
            [, $cm] = get_course_and_cm_from_instance($quiz->id, 'quiz');
            $quizusersbyquizid[$quiz->id] = array_column($frequency->get_event_users_raw($cm->context->id, 'quiz'), 'id');
        }

        $loggedinusers = get_loggedin_users(array_unique(array_reduce($quizusersbyquizid, 'array_merge', [])));

        // For each quiz get the list of users who are elligble to do the quiz.
        foreach ($quizzes as $quiz) {
            $quizusers = $quizusersbyquizid[$quiz->id];
            $attemptusers = $source->get_quiz_attempts($quiz->id);
            $loggedout = 0;
            $loggedin = 0;
            $inprogress = 0;
            $finished = 0;

            foreach ($quizusers as $user) {
                if (in_array($user, $attemptusers->finishedusers)) {
                    $finished++;
                } else if (in_array($user, $attemptusers->inprogressusers)) {
                    $inprogress++;
                } else if (in_array($user, $loggedinusers->loggedinusers)) {
                    $loggedin++;
                } else if (in_array($user, $loggedinusers->loggedoutusers)) {
                    $loggedout++;
                }
            }

            $record = new stdClass();
            $record->module = 'quiz';
            $record->assessid = $quiz->id;
            $record->notloggedin = $loggedout;
            $record->loggedin = $loggedin;
            $record->inprogress = $inprogress;
            $record->finished = $finished;
            $record->timecreated = time();

            $DB->insert_record('local_assessfreq_trend', $record);
            $count++;
        }

        $actionduration = time() - $actionstart;

        mtrace("assessfreqsource_quiz: Processing quiz tracking of $count records finished in: $actionduration seconds");
    }
}
