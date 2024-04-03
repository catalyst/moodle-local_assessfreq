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
 * @package   assessfreqsource_quiz
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_quiz;

use assessfreqsource_quiz\form\override_form;
use assessfreqsource_quiz\output\participant_summary;
use assessfreqsource_quiz\output\participant_trend;
use assessfreqsource_quiz\output\renderer;
use context_module;
use html_writer;
use local_assessfreq\frequency;
use local_assessfreq\source_base;
use mod_quiz\event\user_override_created;
use mod_quiz\event\user_override_updated;
use mod_quiz\question\bank\qbank_helper;
use moodle_exception;
use moodle_url;
use quiz;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class Source extends source_base {

    /**
     * @inheritDoc
     */
    public function get_module() : string {
        return 'quiz';
    }

    /**
     * @inheritDoc
     */
    public function get_name() : string {
        return get_string("source:name", "assessfreqsource_quiz");
    }

    /**
     * @inheritDoc
     */
    public function get_timelimit_field() : string {
        return 'timelimit';
    }

    /**
     * @inheritDoc
     */
    public function get_open_field() : string {
        return 'timeopen';
    }

    /**
     * @inheritDoc
     */
    public function get_close_field() : string {
        return 'timeclose';
    }

    /**
     * @inheritDoc
     */
    public function get_user_capabilities() : array {
        return ['mod/quiz:attempt', 'mod/quiz:view'];
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

        $quizobject = new quiz(
            $DB->get_record('quiz', ['id' => $cm->instance]),
            $cm,
            $course
        );

        $quiz = $quizobject->get_quiz();

        // Get a count of the distinct number of participant overrides.
        $overridenparticipants = [];
        if ($overrides = $DB->get_records('quiz_overrides', ['quiz' => $cm->instance])) {
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

        $quiz->overridecount = html_writer::link(
            new moodle_url('/mod/quiz/overrides.php', ['cmid' => $quizobject->get_context()->instanceid, 'mode' => 'user']),
            count($overridenparticipants)
        );

        $quiz->participants = 0;
        $quiz->attempts = $DB->get_records('quiz_attempts', ['quiz' => $quiz->id]);
        $firststart = false;
        $laststart = false;

        $frequency = new frequency();
        $quiz->participants = html_writer::link(
            new moodle_url('/user/index.php', ['id' => $course->id]),
            count($frequency->get_event_users_raw($quizobject->get_context()->id, 'quiz'))
        );

        foreach ($quiz->attempts as $attempt) {
            if (!$firststart) {
                $firststart = $attempt->timestart;
            }
            $firststart = min($firststart, $attempt->timestart);
            $laststart = max($laststart, $attempt->timefinish);
        }

        $quiz->firststart = $firststart;
        $quiz->laststart = $laststart;
        $quiz->questions = $this->get_quiz_questions($quizobject);

        $quiz->summarychart = (new participant_summary())->get_participant_summary_chart(
            $this->get_tracking($quizobject->get_quizid(), true)
        );
        $quiz->trendchart = (new participant_trend())->get_participant_trend_chart(
            $this->get_tracking($quizobject->get_quizid(), true)
        );

        /* @var $renderer renderer */
        $renderer = $PAGE->get_renderer("assessfreqsource_quiz");
        $PAGE->requires->js_call_amd(
            'assessfreqsource_quiz/activity_dashboard',
            'init',
            [
                $quizobject->get_context()->id,
                $quizobject->get_quizid()
            ]
        );
        return $renderer->render_activity_dashboard($cm, $course, $quiz);
    }

    /**
     * Get quiz question infromation.
     * Data returned is:
     * List of individual question types,
     * Count of questions in quiz,
     * Count of question types.
     *
     * @param quiz $quizobject
     * @return stdClass $questions The question data for the quiz.
     */
    private function get_quiz_questions(quiz $quizobject) : stdClass {
        $questions = new stdClass();
        $types = [];
        $questioncount = 0;

        $quizobject->preload_questions();
        $quizobject->load_questions();

        foreach ($quizobject->get_questions() as $question) {
            $types[] = get_string('pluginname', 'qtype_' . $question->qtype);
            $questioncount++;
        }

        $typeswithcounts = [];
        foreach (array_count_values($types) as $type => $count) {
            $typeswithcounts[] = ['type' => $type, 'count' => $count];
        }

        $questions->types = $typeswithcounts;
        $questions->typecount = count($typeswithcounts);
        $questions->questioncount = $questioncount;

        return $questions;
    }

    /**
     * Get counts for inprogress assessments, both total in progress quiz activities
     * and total participants in progress.
     *
     * @param int $now Timestamp to use for reference for time.
     */
    public function get_inprogress_count(int $now) {
        // Get tracked quizzes.
        $trackedquizzes = $this->get_tracked_quizzes_with_overrides($now, 8 * HOURSECS, 8 * HOURSECS);

        $counts = [
            'assessments' => 0,
            'participants' => 0,
        ];

        foreach ($trackedquizzes as $quiz) {
            $counts['assessments']++;

            // Get tracked users for quiz.
            $tracking = $this->get_recent_tracking($quiz->id);
            if (!empty($tracking)) {
                $counts['participants'] += $tracking->inprogress;
            }
        }

        return get_string('inprogress:assessments', 'assessfreqsource_quiz', $counts);
    }

    /**
     * Get data for all inprogress quizzes.
     *
     * @param int $now
     * @return array|array[]
     */
    public function get_inprogress_data(int $now) : array {

        return $this->get_quiz_summaries($now);
    }

    /**
     * Get all upcomming data.
     *
     * @param int $now
     * @return array|array[]
     */
    public function get_upcomming_data(int $now) : array {

        return $this->get_quiz_summaries($now);
    }

    /**
     * Get the override form for the modal.
     *
     * @param $quizid
     * @param $context
     * @param $userid
     * @param $formdata
     * @return override_form
     */
    public function get_override_form($quizid, $context, $userid, $formdata) : override_form {
        global $DB;

        require_capability("mod/quiz:manageoverrides", $context);

        [$course, $cm] = get_course_and_cm_from_instance($quizid, 'quiz');
        $instance = $DB->get_record('quiz', ['id' => $quizid], '*', MUST_EXIST);
        $override = $DB->get_record("quiz_overrides", ['quiz' => $instance->id, 'userid' => $userid]);

        if ($override) {
            // Editing an override.
            $data = clone $override;
        } else {
            // Creating a new override.
            $data = new stdClass();
            $data->userid = $userid;
        }

        // Merge defaults with data.
        $keys = array('timeopen', 'timeclose', 'timelimit', 'attempts', 'password');
        foreach ($keys as $key) {
            if (!isset($data->{$key})) {
                $data->{$key} = $instance->{$key};
            }
        }
        $mform = new override_form($cm, $instance, $context, $data, $formdata);
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
        [, $cm] = get_course_and_cm_from_instance($activityid, 'quiz');
        $PAGE->set_context($cm->context);
        require_capability("mod/quiz:manageoverrides", $cm->context);

        // Check if we have an existing override for this user.
        $override = $DB->get_record("quiz_overrides", ['quiz' => $activityid, 'userid' => $submitteddata['userid']]);

        // Submit the form data.
        $quiz = $DB->get_record('quiz', ['id' => $activityid], '*', MUST_EXIST);
        $cm = get_course_and_cm_from_cmid($cm->context->instanceid, 'quiz')[1];
        $mform = new override_form($cm, $quiz, $cm->context, $override, $submitteddata);

        $mdata = $mform->get_data();

        if ($mdata) {
            $params = [
                'context' => $cm->context,
                'other' => [
                    'quizid' => $activityid,
                ],
                'relateduserid' => $mdata->userid,
            ];
            $mdata->quiz = $activityid;

            if (!empty($override->id)) {
                $mdata->id = $override->id;
                $DB->update_record('quiz_overrides', $mdata);

                // Determine which override updated event to fire.
                $params['objectid'] = $override->id;
                $event = user_override_updated::create($params);
                // Trigger the override updated event.
            } else {
                $override = new stdClass();
                $override->id = $DB->insert_record('quiz_overrides', $mdata);

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
     * Generate the markup for the summary chart,
     * used in the in progress quizzes dashboard.
     *
     * @param int $now Timestamp to get chart data for.
     * @return array With Generated chart object and chart data status.
     */
    public function get_all_participants_inprogress_data(int $now) : array {

        // Get quizzes for the supplied timestamp.
        $quizzes = $this->get_quiz_summaries($now);

        $inprogressquizzes = $quizzes['inprogress'];
        $upcommingquizzes = $quizzes['upcomming'];
        $finishedquizzes = $quizzes['finished'];

        foreach ($upcommingquizzes as $upcommingquiz) {
            foreach ($upcommingquiz as $timestampupcomming => $upcomming) {
                $inprogressquizzes[$timestampupcomming] = $upcomming;
            }
        }

        foreach ($finishedquizzes as $finishedquiz) {
            foreach ($finishedquiz as $timestampfinished => $finished) {
                $inprogressquizzes[$timestampfinished] = $finished;
            }
        }

        $notloggedin = 0;
        $loggedin = 0;
        $inprogress = 0;
        $finished = 0;

        foreach ($inprogressquizzes as $quizobj) {
            if (!empty($quizobj->tracking)) {
                $notloggedin += $quizobj->tracking->notloggedin;
                $loggedin += $quizobj->tracking->loggedin;
                $inprogress += $quizobj->tracking->inprogress;
                $finished += $quizobj->tracking->finished;
            }
        }

        return [
            'notloggedin' => $notloggedin,
            'loggedin' => $loggedin,
            'inprogress' => $inprogress,
            'finished' => $finished,
        ];
    }

    /**
     * Get finished, in progress and upcomming quizzes and their associated data.
     *
     * @param int $now Timestamp to use for reference for time.
     * @return array $quizzes Array of finished, inprogress and upcomming quizzes with associated data.
     */
    public function get_quiz_summaries(int $now) : array {
        $hoursahead = (int)get_user_preferences('assessfreqreport_activities_in_progress_hoursahead_preference', 8);
        $hoursbehind = (int)get_user_preferences('assessfreqreport_activities_in_progress_hoursbehind_preference', 1);

        // Get tracked quizzes.
        $lookahead = $hoursahead * HOURSECS;
        $lookbehind = $hoursbehind * HOURSECS;
        $trackedquizzes = $this->get_tracked_quizzes_with_overrides($now, $lookahead, $lookbehind);

        // Set up array to hold quizzes and data.
        $quizzes = [
            'finished' => [],
            'inprogress' => [],
            'upcomming' => [],
        ];

        // Itterate through the hours, processing in progress and upcomming quizzes.
        for ($hour = 0; $hour <= $hoursahead; $hour++) {
            $time = $now + (HOURSECS * $hour);

            if ($hour == 0) {
                $quizzes['inprogress'] = [];
            }

            $quizzes['upcomming'][$time] = [];

            // Seperate out inprogress and upcomming quizzes, then get data for each quiz.
            foreach ($trackedquizzes as $quiz) {
                $quizdata = $this->get_quiz_data($quiz);

                if ($quiz->timeopen < $time && $quiz->timeclose > $time && $hour === 0) { // Get inprogress quizzes.
                    $quizzes['inprogress'][$quiz->id] = $quizdata;
                    unset($trackedquizzes[$quiz->id]); // Remove quiz from array to help with performance.
                } else if (($quiz->timeopen >= $time) && ($quiz->timeopen < ($time + HOURSECS))) { // Get upcomming quizzes.
                    $quizzes['upcomming'][$time][$quiz->id] = $quizdata;
                    unset($trackedquizzes[$quiz->id]);
                } else {
                    if (isset($quiz->overrides)) {
                        $quizzes['inprogress'][$quiz->id] = $quizdata;
                        unset($trackedquizzes[$quiz->id]);
                    }
                }
            }
        }

        // Iterate through the hours, processing finished quizzes.
        for ($hour = 1; $hour <= $hoursbehind; $hour++) {
            $time = $now - (HOURSECS * $hour);

            $quizzes['finished'][$time] = [];

            // Get data for each finished quiz.
            foreach ($trackedquizzes as $quiz) {
                if (($quiz->timeclose >= $time) && ($quiz->timeclose < ($time + HOURSECS))) { // Get finished quizzes.
                    $quizdata = $this->get_quiz_data($quiz);

                    $quizzes['finished'][$time][$quiz->id] = $quizdata;
                    unset($trackedquizzes[$quiz->id]);
                }
            }
        }

        return $quizzes;
    }

    /**
     * Method returns data about a quiz.
     * Data returned is:
     * Quiz name,
     * Quiz start time,
     * Quiz end time,
     * Earliest participant start time (override),
     * Latest participant end time (override),
     * Total participants taking the quiz,
     * Number participants with overrides in quiz,
     * Quiz link,
     * Number of questions,
     * Number of question types,
     * List of question types.
     *
     * @param object $quiz The quiz to get data for.
     * @return stdClass $quizdata The retrieved quiz data.
     */
    public function get_quiz_data($quiz) : stdClass {
        global $DB;
        $quizdata = new stdClass();
        [$course, $cm] = get_course_and_cm_from_instance($quiz->id, 'quiz');

        $quizobject = new quiz(
            $DB->get_record('quiz', ['id' => $cm->instance]),
            $cm,
            $course
        );

        $context = $cm->context;

        $quizrecord = $DB->get_record('quiz', ['id' => $quiz->id], 'name, timeopen, timeclose, timelimit, course');
        $courseurl = new moodle_url('/course/view.php', ['id' => $quizrecord->course]);

        $overrideinfo = $this->get_quiz_override_info($quiz->id, $context);
        $questions = $this->get_quiz_questions($quizobject);
        $frequency = new frequency();

        $dateformat = get_string('strftimedatetime', 'langconfig');
        $nastring = get_string('na', 'assessfreqsource_quiz');

        $timesopen = (!empty($quizrecord->timeopen)) ? userdate($quizrecord->timeopen, $dateformat) : $nastring;
        $timeclose = (!empty($quizrecord->timeclose)) ? userdate($quizrecord->timeclose, $dateformat) : $nastring;
        $overrideinfostart = (!empty($overrideinfo->start)) ? userdate($overrideinfo->start, $dateformat) : $nastring;
        $overrideinfoend = (!empty($overrideinfo->end)) ? userdate($overrideinfo->end, $dateformat) : $nastring;

        // Handle override start.
        $earlyopen = $timesopen;
        $earlyopenstamp = $quizrecord->timeopen;
        if ($overrideinfo->start != 0 && $overrideinfo->start < $quizrecord->timeopen) {
            $earlyopen = $overrideinfostart;
            $earlyopenstamp = $overrideinfo->start;
        }

        // Handle override end.
        $lateclose = $timeclose;
        $lateclosestamp = $quizrecord->timeclose;
        if ($overrideinfo->end != 0 && $overrideinfo->end > $quizrecord->timeclose) {
            $lateclose = $overrideinfoend;
            $lateclosestamp = $overrideinfo->end;
        }

        // Quiz result link.
        $resultlink = new moodle_url('/mod/quiz/report.php', ['id' => $context->instanceid, 'mode' => 'overview']);
        // Override link.
        $overrridelink = new moodle_url('/mod/quiz/overrides.php', ['cmid' => $context->instanceid, 'mode' => 'user']);
        // Participant link.
        $participantlink = new moodle_url('/user/index.php', ['id' => $quizrecord->course]);
        // Dashboard link.
        $dashboardlink = new moodle_url('/local/assessfreq/', ['activityid' => $context->instanceid], 'activity_dashboard');

        $quizdata->name = format_string($quizrecord->name, true, ["context" => $context, "escape" => true]);
        $quizdata->timeopen = $timesopen;
        $quizdata->timeclose = $timeclose;
        $quizdata->timelimit = format_time($quizrecord->timelimit);
        $quizdata->earlyopen = $earlyopen;
        $quizdata->earlyopenstamp = $earlyopenstamp;
        $quizdata->lateclose = $lateclose;
        $quizdata->lateclosestamp = $lateclosestamp;
        $quizdata->participants = count($frequency->get_event_users_raw($context->id, 'quiz'));
        $quizdata->overrideparticipants = $overrideinfo->users;
        $quizdata->url = $context->get_url()->out(false);
        $quizdata->types = $questions->types;
        $quizdata->typecount = $questions->typecount;
        $quizdata->questioncount = $questions->questioncount;
        $quizdata->resultlink = $resultlink->out(false);
        $quizdata->overridelink = $overrridelink->out(false);
        $quizdata->coursefullname = format_string($course->fullname, true, ["context" => $context, "escape" => true]);
        $quizdata->courseshortname = $course->shortname;
        $quizdata->courselink = $courseurl->out(false);
        $quizdata->participantlink = $participantlink->out(false);
        $quizdata->dashboardlink = $dashboardlink->out(false);
        $quizdata->assessid = $quiz->id;
        $quizdata->context = $cm->context;
        $quizdata->timestampopen = $quizobject->get_quiz()->timeopen;
        $quizdata->timestampclose = $quizobject->get_quiz()->timeclose;
        $quizdata->timestamplimit = $quizobject->get_quiz()->timelimit;
        $quizdata->isoverride = $quiz->isoverride;

        if (isset($quiz->overrides)) {
            $quizdata->overrides = $quiz->overrides;
        }

        // Get tracked users for quiz.
        $quizdata->tracking = $this->get_recent_tracking($quiz->id);

        return $quizdata;
    }

    /**
     * Get override info for a paricular quiz.
     * Data returned is:
     * Number of users with overrides in Quiz,
     * Ealiest override start,
     * Latest override end.
     *
     * @param int $quizid The ID of the quiz to get override data for.
     * @param context_module $context The context object of the quiz.
     * @return stdClass $overrideinfo Information about quiz overrides.
     */
    private function get_quiz_override_info(int $quizid, context_module $context) : stdClass {
        global $DB;

        $capabilities = $this->get_user_capabilities();
        $overrideinfo = new stdClass();
        $users = [];
        $start = 0;
        $end = 0;

        $sql = 'SELECT id, userid, COALESCE(timeopen, 0) AS timeopen, COALESCE(timeclose, 0) AS timeclose
                FROM {quiz_overrides}
                WHERE quiz = ?';
        $params = [$quizid];
        $overrides = $DB->get_records_sql($sql, $params);

        foreach ($overrides as $override) {
            if (!has_all_capabilities($capabilities, $context, $override->userid)) {
                continue; // Don't count users who can't access the quiz.
            }
            $users[] = $override->userid;
            $end = max($end, $override->timeclose);
            $start = (!$start ? $override->timeopen : min($start, $override->timeopen));
        }

        $users = count(array_unique($users));

        $overrideinfo->start = $start;
        $overrideinfo->end = $end;
        $overrideinfo->users = $users;

        return $overrideinfo;
    }

    /**
     * Get a list of all quizzes that have a start date less than now + 1 hour
     * AND end date is in the future OR end date is less then 1 hour in the past.
     * And startdate != 0. With quiz start and end times adjusted to take into account users with overrides.
     *
     * @param int $now Timestamp to use for reference for time.
     * @param int $lookahead The number of seconds from the provided now value to look ahead when getting quizzes.
     * @param int $lookbehind The number of seconds from the provided now value to look behind when getting quizzes.
     * @return array $quizzes The quizzes.
     */
    public function get_tracked_quizzes_with_overrides(int $now, int $lookahead = HOURSECS, int $lookbehind = HOURSECS) : array {
        global $DB;

        $quizzes = $this->get_tracked_quizzes($now, $lookahead, $lookbehind);
        $overrides = $this->get_tracked_overrides($now, $lookahead, $lookbehind);

        // Add override data to each quiz in the array.
        foreach ($overrides as $override) {
            $sql = 'SELECT id, timeopen, timeclose, timelimit
                      FROM {quiz}
                     WHERE id = :id';
            $params = [
                'id' => $override->quiz,
            ];

            $quizzesoverride = $DB->get_record_sql($sql, $params);

            if ($quizzesoverride) {
                if (array_key_exists($quizzesoverride->id, $quizzes)) {
                    $quizzesoverride->isoverride = $quizzes[$quizzesoverride->id]->isoverride;
                    if (isset($quizzes[$quizzesoverride->id]->overrides)) {
                        $quizzesoverride->overrides = $quizzes[$quizzesoverride->id]->overrides;
                    }
                } else {
                    $quizzesoverride->isoverride = 1;
                }
                $quizzesoverride->overrides[] = $override;
                $quizzes[$quizzesoverride->id] = $quizzesoverride;
            }
        }

        return $quizzes;
    }

    /**
     * Get a list of all quizzes that have a start date less than now + 1 hour
     * AND end date is in the future OR end date is less then 1 hour in the past.
     * And startdate != 0.
     *
     * @param int $now Timestamp to use for reference for time.
     * @param int $lookahead The number of seconds from the provided now value to look ahead when getting quizzes.
     * @param int $lookbehind The number of seconds from the provided now value to look behind when getting quizzes.
     * @return array $quizzes The quizzes.
     */
    private function get_tracked_quizzes(int $now, int $lookahead, int $lookbehind) : array {
        global $DB, $PAGE;

        $starttime = $now + $lookahead;
        $endtime = $now - $lookbehind;

        $sql = 'SELECT id, timeopen, timeclose, timelimit, 0 AS isoverride
                  FROM {quiz}
                 WHERE (timeopen > 0 AND timeopen < :starttime)
                       AND (timeclose > :endtime OR timeclose > :now)';
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
     * Get a list of all quiz overrides that have a start date less than now + 1 hour
     * AND end date is in the future OR end date is less then 1 hour in the past.
     * And startdate != 0.
     *
     * @param int $now Timestamp to use for reference for time.
     * @param int $lookahead The number of seconds from the provided now value to look ahead when getting overrides.
     * @param int $lookbehind The number of seconds from the provided now value to look behind when getting overrides.
     * @return array $quizzes The quizzes with applicable overrides.
     */
    private function get_tracked_overrides(int $now, int $lookahead, int $lookbehind) : array {
        global $DB, $PAGE;

        $starttime = $now + $lookahead;
        $endtime = $now - $lookbehind;

        $sql = 'SELECT qo.id, qo.quiz, qo.userid, qo.timeopen, qo.timeclose
                FROM {quiz_overrides} qo
                JOIN {quiz} q
                ON q.id = qo.quiz
                WHERE (qo.timeopen > 0 AND qo.timeopen < :starttime)
                AND (qo.timeclose > :endtime OR qo.timeclose > :now)';
        $params = [
            'starttime' => $starttime,
            'endtime' => $endtime,
            'now' => $now,
        ];

        if ($PAGE->course->id != SITEID) {
            $sql .= " AND q.course = :courseid";
            $params['courseid'] = $PAGE->course->id;
        }

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Get count of in progress and finished attempts for a quiz.
     *
     * @param int $quizid The id of the quiz to get the counts for.
     * @return stdClass $attemptcounts The found counts.
     */
    public function get_quiz_attempts(int $quizid) : stdClass {
        global $DB;

        $inprogress = 0;
        $finished = 0;
        $inprogressusers = [];
        $finishedusers = [];

        $sql = 'SELECT userid, state
                  FROM {quiz_attempts} qa
                  JOIN (
                      SELECT MAX(id) id
                      FROM {quiz_attempts}
                      WHERE quiz = ?
                      GROUP BY userid)
                    AS qb
                    ON qa.id = qb.id';

        $params = [$quizid];

        $usersattempts = $DB->get_records_sql($sql, $params);

        foreach ($usersattempts as $usersattempt) {
            if ($usersattempt->state == 'inprogress' || $usersattempt->state == 'overdue') {
                $inprogress++;
                $inprogressusers[] = $usersattempt->userid;
            } else if ($usersattempt->state == 'finished' || $usersattempt->state == 'abandoned') {
                $finished++;
                $finishedusers[] = $usersattempt->userid;
            }
        }

        $attemptcounts = new stdClass();
        $attemptcounts->inprogress = $inprogress;
        $attemptcounts->finished = $finished;
        $attemptcounts->inprogressusers = $inprogressusers;
        $attemptcounts->finishedusers = $finishedusers;

        return $attemptcounts;
    }
}
