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
 * Main source class.
 *
 * @package   assessfreqsource_assign
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_assign;

use assessfreqsource_assign\form\override_form;
use assessfreqsource_assign\output\participant_summary;
use assessfreqsource_assign\output\participant_trend;
use assessfreqsource_assign\output\renderer;
use assign;
use context_module;
use local_assessfreq\frequency;
use local_assessfreq\source_base;
use mod_assign\event\user_override_created;
use mod_assign\event\user_override_updated;
use moodle_exception;
use moodle_url;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/locallib.php');

class source extends source_base {

    /**
     * @inheritDoc
     */
    public function get_module() : string {
        return 'assign';
    }

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return get_string("source:name", "assessfreqsource_assign");
    }

    /**
     * @inheritDoc
     */
    public function get_open_field() : string {
        return 'allowsubmissionsfromdate';
    }

    /**
     * @inheritDoc
     */
    public function get_close_field() : string {
        return 'duedate';
    }

    /**
     * @inheritDoc
     */
    public function get_user_capabilities() : array {
        return ['mod/assign:submit', 'mod/assign:view'];
    }

    /**
     * Get the activity dashboard to be rendered in assessfreqreport_activity_dashboard plugin.
     *
     * @param $cm
     * @param $course
     * @return string
     */
    public function get_activity_dashboard($cm, $course) : string {
        global $PAGE, $DB;

        $assign = new assign($cm->context, $cm, $course);

        // Get a count of the distinct number of participant overrides.
        $overridenparticipants = [];
        if ($assign->has_overrides()) {
            $overrides = $DB->get_records('assign_overrides', ['assignid' => $cm->instance]);
            foreach ($overrides as $override) {
                if ($override->userid) {
                    $overridenparticipants[$override->userid] = 1;
                } else {
                    $groupmembers = groups_get_members($override->groupid);
                    foreach ($groupmembers as $groupmember) {
                        $overridenparticipants[$groupmember->id] = 1;
                    }
                }
            }
        }

        $assign->overridecount = count($overridenparticipants);

        $submissions = [];
        $firststart = false;
        $laststart = false;

        $participants = $assign->list_participants(0, false);

        foreach ($participants as $participant) {
            $submission = $assign->get_user_submission($participant->id, false);
            if (!$submission) {
                continue;
            }
            $submissions[] = [
                'participant' => $participant,
                'submission' => $submission,
            ];
            if ($submission->status === 'submitted') {
                if (!$firststart) {
                    $firststart = $submission->timemodified;
                }
                $firststart = min($firststart, $submission->timemodified);
                $laststart = max($laststart, $submission->timemodified);
            }
        }

        $assign->submissions = $submissions;
        $assign->firststart = $firststart;
        $assign->laststart = $laststart;

        $plugins = $assign->get_submission_plugins();
        $assign->enabledsubmission_plugins = [];
        foreach ($plugins as $plugin) {
            if ($plugin->is_enabled()) {
                $assign->enabledsubmission_plugins[] = $plugin->get_name();
            }
        }

        $assign->groupsubmissionenabled = $assign->get_instance()->teamsubmission ? get_string('yes') : get_string('no');

        $assign->summarychart = (new participant_summary())->get_participant_summary_chart(
            $this->get_tracking($cm->instance, true)
        );

        $assign->trendchart = (new participant_trend())->get_participant_trend_chart(
            $this->get_tracking($cm->instance, true)
        );

        $PAGE->requires->js_call_amd(
            'assessfreqsource_assign/activity_dashboard',
            'init',
            [
                $cm->context->id,
                $cm->instance,
            ]
        );

        /* @var $renderer renderer */
        $renderer = $PAGE->get_renderer("assessfreqsource_assign");
        return $renderer->render_activity_dashboard($cm, $course, $assign);
    }

    /**
     * Get a list of all assignments that have a start date less than now + 1 hour
     * AND end date is in the future OR end date is less then 1 hour in the past.
     * And startdate != 0. With assignment start and end times adjusted to take into account users with overrides.
     *
     * @param int $now Timestamp to use for reference for time.
     * @param int $lookahead The number of seconds from the provided now value to look ahead when getting assignments.
     * @param int $lookbehind The number of seconds from the provided now value to look behind when getting assignmenta.
     * @return array $assignemnts The assignments.
     */
    public function get_tracked_assignments_with_overrides(
        int $now,
        int $lookahead = HOURSECS,
        int $lookbehind = HOURSECS
    ) : array {
        global $DB;

        $assignments = $this->get_tracked_assignments($now, $lookahead, $lookbehind);
        $overrides = $this->get_tracked_overrides($now, $lookahead, $lookbehind);

        // Add override data to each assignment in the array.
        foreach ($overrides as $override) {
            $sql = 'SELECT id, allowsubmissionsfromdate, duedate, timelimit
                      FROM {assign}
                     WHERE id = :id';
            $params = [
                'id' => $override->assignid,
            ];

            $assignmentoverride = $DB->get_record_sql($sql, $params);

            if ($assignmentoverride) {
                if (array_key_exists($assignmentoverride->id, $assignments)) {
                    $assignmentoverride->isoverride = $assignments[$assignmentoverride->id]->isoverride;
                    if (isset($assignments[$assignmentoverride->id]->overrides)) {
                        $assignmentoverride->overrides = $assignments[$assignmentoverride->id]->overrides;
                    }
                } else {
                    $assignmentoverride->isoverride = 1;
                }
                $assignmentoverride->overrides[] = $override;
                $assignments[$assignmentoverride->id] = $assignmentoverride;
            }
        }

        return $assignments;
    }

    /**
     * Get a list of all assignemnts that have a start date less than now + 1 hour
     * AND end date is in the future OR end date is less then 1 hour in the past.
     * And startdate != 0.
     *
     * @param int $now Timestamp to use for reference for time.
     * @param int $lookahead The number of seconds from the provided now value to look ahead when getting assignments.
     * @param int $lookbehind The number of seconds from the provided now value to look behind when getting assignments.
     * @return array $assignments The assignments.
     */
    private function get_tracked_assignments(int $now, int $lookahead, int $lookbehind) : array {
        global $DB, $PAGE;

        $starttime = $now + $lookahead;
        $endtime = $now - $lookbehind;

        $sql = 'SELECT id, allowsubmissionsfromdate, duedate, timelimit, 0 AS isoverride
                  FROM {assign}
                 WHERE (allowsubmissionsfromdate > 0 AND allowsubmissionsfromdate < :starttime)
                       AND (duedate > :endtime OR duedate > :now)';
        $params = [
            'starttime' => $starttime,
            'endtime' => $endtime,
            'now' => $now,
        ];
        if ($PAGE->course->id != SITEID) {
            $sql .= " AND course = :courseid";
            $params['courseid'] = $PAGE->course->id;
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get a list of all assignment overrides that have a start date less than now + 1 hour
     * AND end date is in the future OR end date is less then 1 hour in the past.
     * And startdate != 0.
     *
     * @param int $now Timestamp to use for reference for time.
     * @param int $lookahead The number of seconds from the provided now value to look ahead when getting overrides.
     * @param int $lookbehind The number of seconds from the provided now value to look behind when getting overrides.
     * @return array $assignments The assignments with applicable overrides.
     */
    private function get_tracked_overrides(int $now, int $lookahead, int $lookbehind) : array {
        global $DB, $PAGE;

        $starttime = $now + $lookahead;
        $endtime = $now - $lookbehind;

        $sql = 'SELECT ao.id, ao.assignid, ao.userid, ao.allowsubmissionsfromdate, ao.duedate
                FROM {assign_overrides} ao
                JOIN {assign} a
                ON a.id = ao.assignid
                WHERE (ao.allowsubmissionsfromdate > 0 AND ao.allowsubmissionsfromdate < :starttime)
                AND (ao.duedate > :endtime OR ao.duedate > :now)';
        $params = [
            'starttime' => $starttime,
            'endtime' => $endtime,
            'now' => $now,
        ];

        if ($PAGE->course->id != SITEID) {
            $sql .= " AND a.course = :courseid";
            $params['courseid'] = $PAGE->course->id;
        }

        return $DB->get_records_sql($sql, $params);
    }

    public function get_submissions($assignmentid) : stdClass {
        global $DB;

        $inprogress = 0;
        $finished = 0;
        $inprogressusers = [];
        $finishedusers = [];

        $usersattempts = $DB->get_records('assign_submission', ['assignment' => $assignmentid, 'latest' => 1]);

        foreach ($usersattempts as $usersattempt) {
            if ($usersattempt->status == 'submitted') {
                $finished++;
                $finishedusers[] = $usersattempt->userid;
            } else {
                $inprogress++;
                $inprogressusers[] = $usersattempt->userid;
            }
        }

        $attemptcounts = new stdClass();
        $attemptcounts->inprogress = $inprogress;
        $attemptcounts->finished = $finished;
        $attemptcounts->inprogressusers = $inprogressusers;
        $attemptcounts->finishedusers = $finishedusers;

        return $attemptcounts;
    }


    /**
     * Get the override form for the modal.
     *
     * @param $assignid
     * @param $context
     * @param $userid
     * @param $formdata
     * @return override_form
     */
    public function get_override_form($assignid, $context, $userid, $formdata) : override_form {
        global $DB;

        require_capability("mod/assign:manageoverrides", $context);

        [$course, $cm] = get_course_and_cm_from_instance($assignid, 'assign');
        $assign = new assign($context, $cm, $course);
        $override = $DB->get_record("assign_overrides", ['assignid' => $assignid, 'userid' => $userid]);

        if ($override) {
            // Editing an override.
            $data = clone $override;
        } else {
            // Creating a new override.
            $data = new stdClass();
            $data->userid = $userid;
        }

        // Merge defaults with data.
        $keys = array('allowsubmissionsfromdate', 'duedate', 'timelimit', 'cutoffdate');
        foreach ($keys as $key) {
            if (!isset($data->{$key})) {
                $data->{$key} = $assign->{$key};
            }
        }
        $mform = new override_form($cm, $assign, $context, $data, $formdata);
        $mform->set_data($data);

        return $mform;
    }
    /**
     * Process the override form from the Ajax webservice call.
     *
     * @param $activityid
     * @param $submitteddata
     * @return int
     */
    public function process_override_form($activityid, $submitteddata) : int {
        global $DB, $PAGE;

        // Check access.
        [$course, $cm] = get_course_and_cm_from_instance($activityid, 'assign');

        $PAGE->set_context($cm->context);
        require_capability("mod/assign:manageoverrides", $cm->context);

        // Check if we have an existing override for this user.
        $override = $DB->get_record("assign_overrides", ['assignid' => $activityid, 'userid' => $submitteddata['userid']]);

        // Submit the form data.
        $assign = new assign($cm->context, $cm, $course);
        $mform = new override_form($cm, $assign, $cm->context, $override, $submitteddata);

        $mdata = $mform->get_data();

        if ($mdata) {
            $params = [
                'context' => $cm->context,
                'other' => [
                    'assignid' => $activityid,
                ],
                'relateduserid' => $mdata->userid,
            ];
            $mdata->assignid = $activityid;

            if (!empty($override->id)) {
                $mdata->id = $override->id;
                $DB->update_record('assign_overrides', $mdata);

                // Determine which override updated event to fire.
                $params['objectid'] = $override->id;
                $event = user_override_updated::create($params);
                // Trigger the override updated event.
            } else {
                $override = new stdClass();
                $override->id = $DB->insert_record('assign_overrides', $mdata);

                // Determine which override created event to fire.
                $params['objectid'] = $override->id;
                $event = user_override_created::create($params);
                // Trigger the override created event.
            }
            $event->trigger();
        } else {
            throw new moodle_exception('submitoverridefail', 'local_assessfreq');
        }

        return $override->id;
    }


    /**
     * Get counts for inprogress assessments, both total in progress assignment activities
     * and total participants in progress.
     *
     * @param int $now Timestamp to use for reference for time.
     * @param bool $textual If true counts are returned with text for output.
     */
    public function get_inprogress_count(int $now, bool $textual = true) {
        // Get tracked assignments.
        $trackedassignments = $this->get_tracked_assignments_with_overrides($now, 8 * HOURSECS, 8 * HOURSECS);

        $counts = [
            'assessments' => 0,
            'participants' => 0,
        ];

        foreach ($trackedassignments as $trackedassignment) {
            $counts['assessments']++;

            // Get tracked users for assignment.
            $tracking = $this->get_recent_tracking($trackedassignment->id);
            if (!empty($tracking)) {
                $counts['participants'] += $tracking->inprogress;
            }
        }

        if ($textual) {
            return get_string('inprogress:assessments', 'assessfreqsource_assign', $counts);
        }
        return $counts;
    }

    /**
     * Get data for all inprogress assignments.
     *
     * @param int $now
     * @return array|array[]
     */
    public function get_inprogress_data(int $now) : array {

        return $this->get_assign_summaries($now);
    }

    /**
     * Get all upcoming data.
     *
     * @param int $now
     * @return array|array[]
     */
    public function get_upcoming_data(int $now) : array {

        return $this->get_assign_summaries($now);
    }

    /**
     * Get finished, in progress and upcoming assignments and their associated data.
     *
     * @param int $now Timestamp to use for reference for time.
     * @return array $assignments Array of finished, inprogress and upcoming assignments with associated data.
     */
    public function get_assign_summaries(int $now) : array {
        $hoursahead = (int)get_user_preferences('assessfreqreport_activities_in_progress_hoursahead_preference', 8);
        $hoursbehind = (int)get_user_preferences('assessfreqreport_activities_in_progress_hoursbehind_preference', 1);

        // Get tracked assignments.
        $lookahead = $hoursahead * HOURSECS;
        $lookbehind = $hoursbehind * HOURSECS;
        $trackedassignments = $this->get_tracked_assignments_with_overrides($now, $lookahead, $lookbehind);

        // Set up array to hold assignments and data.
        $assignments = [
            'finished' => [],
            'inprogress' => [],
            'upcoming' => [],
        ];

        // Itterate through the hours, processing in progress and upcoming assignments.
        for ($hour = 0; $hour <= $hoursahead; $hour++) {
            $time = $now + (HOURSECS * $hour);

            if ($hour == 0) {
                $assignments['inprogress'] = [];
            }

            $assignments['upcoming'][$time] = [];

            // Seperate out inprogress and upcoming assignments, then get data for each assignment.
            foreach ($trackedassignments as $assignment) {
                $assigndata = $this->get_assign_data($assignment);
                $allowsubmissionsfromdate = $assignment->allowsubmissionsfromdate;
                if ($assignment->allowsubmissionsfromdate < $time && $assignment->duedate > $time && $hour === 0) {
                    $assignments['inprogress'][$assignment->id] = $assigndata;
                    unset($trackedassignments[$assignment->id]);
                } else if ($allowsubmissionsfromdate >= $time && $allowsubmissionsfromdate < ($time + HOURSECS)) {
                    $assignments['upcoming'][$time][$assignment->id] = $assigndata;
                    unset($trackedassignments[$assignment->id]);
                } else {
                    if (isset($assignment->overrides)) {
                        $assignments['inprogress'][$assignment->id] = $assigndata;
                        unset($trackedassignments[$assignment->id]);
                    }
                }
            }
        }

        // Iterate through the hours, processing finished assignments.
        for ($hour = 1; $hour <= $hoursbehind; $hour++) {
            $time = $now - (HOURSECS * $hour);

            $assignments['finished'][$time] = [];

            // Get data for each finished assignment.
            foreach ($trackedassignments as $assignment) {
                if (($assignment->duedate >= $time) && ($assignment->duedate < ($time + HOURSECS))) {
                    $assigndata = $this->get_assign_data($assignment);
                    $assignments['finished'][$time][$assignment->id] = $assigndata;
                    unset($trackedassignments[$assignment->id]);
                }
            }
        }

        return $assignments;
    }

    /**
     * Method returns data about an assignment.
     *
     * @param object $assign The assignment to get data for.
     * @return stdClass $assigndata The retrieved assignment data.
     */
    public function get_assign_data($assign) : stdClass {
        global $DB;
        $assigndata = new stdClass();
        [$course, $cm] = get_course_and_cm_from_instance($assign->id, 'assign');
        $context = $cm->context;

        $assignrecord = $DB->get_record('assign', ['id' => $assign->id]);
        $courseurl = new moodle_url('/course/view.php', ['id' => $assignrecord->course]);

        $overrideinfo = $this->get_assign_override_info($assign->id, $context);
        $frequency = new frequency();
        if (!empty($assignrecord->allowsubmissionsfromdate)) {
            $allowsubmissionsfromdate = userdate(
                $assignrecord->allowsubmissionsfromdate,
                get_string('strftimedatetime', 'langconfig')
            );
        } else {
            $allowsubmissionsfromdate = get_string('source:na', 'assessfreqsource_assign');
        }
        if (!empty($assignrecord->duedate)) {
            $duedate = userdate($assignrecord->duedate, get_string('strftimedatetime', 'langconfig'));
        } else {
            $duedate = get_string('source:na', 'assessfreqsource_assign');
        }
        if (!empty($overrideinfo->start)) {
            $overrideinfostart = userdate($overrideinfo->start, get_string('strftimedatetime', 'langconfig'));
        } else {
            $overrideinfostart = get_string('source:na', 'assessfreqsource_assign');
        }
        if (!empty($overrideinfo->end)) {
            $overrideinfoend = userdate($overrideinfo->end, get_string('strftimedatetime', 'langconfig'));
        } else {
            $overrideinfoend = get_string('source:na', 'assessfreqsource_assign');
        }

        // Handle override start.
        if ($overrideinfo->start != 0 && $overrideinfo->start < $assignrecord->allowsubmissionsfromdate) {
            $earlyopen = $overrideinfostart;
            $earlyopenstamp = $overrideinfo->start;
        } else {
            $earlyopen = $allowsubmissionsfromdate;
            $earlyopenstamp = $assignrecord->allowsubmissionsfromdate;
        }

        // Handle override end.
        if ($overrideinfo->end != 0 && $overrideinfo->end > $assignrecord->duedate) {
            $lateclose = $overrideinfoend;
            $lateclosestamp = $overrideinfo->end;
        } else {
            $lateclose = $duedate;
            $lateclosestamp = $assignrecord->duedate;
        }

        // Assignment result link.
        $resultlink = new moodle_url('/mod/assign/report.php', ['id' => $context->instanceid, 'mode' => 'overview']);
        // Override link.
        $overrridelink = new moodle_url('/mod/assign/overrides.php', ['cmid' => $context->instanceid, 'mode' => 'user']);
        // Participant link.
        $participantlink = new moodle_url('/user/index.php', ['id' => $assignrecord->course]);
        // Dashboard link.
        $dashboardlink = new moodle_url('/local/assessfreq/', ['activityid' => $context->instanceid], 'activity_dashboard');

        $assigndata->name = format_string($assignrecord->name, true, ["context" => $context, "escape" => true]);
        $assigndata->allowsubmissionsfromdate = $allowsubmissionsfromdate;
        $assigndata->duedate = $duedate;
        $assigndata->timelimit = format_time($assignrecord->timelimit);
        $assigndata->earlyopen = $earlyopen;
        $assigndata->earlyopenstamp = $earlyopenstamp;
        $assigndata->lateclose = $lateclose;
        $assigndata->lateclosestamp = $lateclosestamp;
        $assigndata->participants = count($frequency->get_event_users_raw($context->id, 'assign'));
        $assigndata->overrideparticipants = $overrideinfo->users;
        $assigndata->url = $context->get_url()->out(false);
        $assigndata->resultlink = $resultlink->out(false);
        $assigndata->overridelink = $overrridelink->out(false);
        $assigndata->coursefullname = format_string($course->fullname, true, ["context" => $context, "escape" => true]);
        $assigndata->courseshortname = $course->shortname;
        $assigndata->courselink = $courseurl->out(false);
        $assigndata->participantlink = $participantlink->out(false);
        $assigndata->dashboardlink = $dashboardlink->out(false);
        $assigndata->assessid = $assign->id;
        $assigndata->context = $cm->context;
        $assigndata->timestampopen = $assignrecord->allowsubmissionsfromdate;
        $assigndata->timestampclose = $assignrecord->duedate;
        $assigndata->timestamplimit = $assignrecord->timelimit;
        $assigndata->isoverride = $assign->isoverride;

        if (isset($assign->overrides)) {
            $assigndata->overrides = $assign->overrides;
        }

        // Get tracked users for assignment.
        $assigndata->tracking = $this->get_recent_tracking($assign->id);

        return $assigndata;
    }

    /**
     * Get override info for a paricular assignment.
     * Data returned is:
     * Number of users with overrides in assignment,
     * Ealiest override start,
     * Latest override end.
     *
     * @param int $assignid The ID of the assignment to get override data for.
     * @param context_module $context The context object of the assignment.
     * @return stdClass $overrideinfo Information about assignment overrides.
     */
    private function get_assign_override_info(int $assignid, context_module $context) : stdClass {
        global $DB;

        $capabilities = $this->get_user_capabilities();
        $overrideinfo = new stdClass();
        $users = [];
        $start = 0;
        $end = 0;

        $sql = 'SELECT id, userid, allowsubmissionsfromdate, duedate
                  FROM {assign_overrides}
                 WHERE assignid = ?';
        $params = [$assignid];
        $overrides = $DB->get_records_sql($sql, $params);

        foreach ($overrides as $override) {
            if (!has_all_capabilities($capabilities, $context, $override->userid)) {
                continue;
            }

            $users[] = $override->userid;
            $end = max($end, $override->duedate);
            $start = (!$start ? $override->allowsubmissionsfromdate : min($start, $override->allowsubmissionsfromdate));
        }

        $users = count(array_unique($users));

        $overrideinfo->start = $start;
        $overrideinfo->end = $end;
        $overrideinfo->users = $users;

        return $overrideinfo;
    }

}
