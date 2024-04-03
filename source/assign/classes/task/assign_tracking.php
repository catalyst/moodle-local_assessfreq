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
 * A scheduled task to track the process of assignments in the system.
 *
 * @package    assessfreqsource_assign
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace assessfreqsource_assign\task;

use assessfreqsource_assign\source;
use core\task\scheduled_task;
use local_assessfreq\frequency;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/assessfreq/lib.php');

class assign_tracking extends scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() : string {
        return get_string('task:assigntracking', 'assessfreqsource_assign');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() : void {
        global $DB;
        mtrace('assessfreqsource_assign: Processing assignment tracking');

        $actionstart = time();

        $source = new source();
        $frequency = new frequency();

        $assignments = $source->get_tracked_assignments_with_overrides($actionstart);
        $assignmentusersbyid = [];
        $count = 0;

        foreach ($assignments as $assignment) {
            [, $cm] = get_course_and_cm_from_instance($assignment->id, 'assign');
            $assignmentusersbyid[$assignment->id] = array_column($frequency->get_event_users_raw($cm->context->id, 'assign'), 'id');
        }

        $loggedinusers = get_loggedin_users(array_unique(array_reduce($assignmentusersbyid, 'array_merge', [])));

        // For each assignment get the list of users who are elligble to do the assignment.
        foreach ($assignments as $assignment) {
            $assignmentusers = $assignmentusersbyid[$assignment->id];
            $attemptusers = $source->get_submissions($assignment->id);
            $loggedout = 0;
            $loggedin = 0;
            $inprogress = 0;
            $finished = 0;

            foreach ($assignmentusers as $user) {
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
            $record->module = 'assign';
            $record->assessid = $assignment->id;
            $record->notloggedin = $loggedout;
            $record->loggedin = $loggedin;
            $record->inprogress = $inprogress;
            $record->finished = $finished;
            $record->timecreated = time();

            $DB->insert_record('local_assessfreq_trend', $record);
            $count++;
        }

        $actionduration = time() - $actionstart;

        mtrace("assessfreqsource_assign: Processing assignment tracking of $count records finished in: $actionduration seconds");
    }
}
