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
 * Quiz data class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz data class.
 *
 * This class handles data processing to get quiz data.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz {

    /**
     * Ammount of time in hours for lookahead values.
     * Defaults to 12.
     *
     * @var int $hoursahead.
     */
    private $hoursahead = 12;

    /**
     * The direction used in sorting.
     *
     * @var string $sortdirection
     */
    private $sortdirection;

    /**
     * The quiz details to sort by.
     *
     * @var string $sorton
     */
    private $sorton;


    /**
     * Given a quiz id get the module context.
     *
     * @param int $quizid The quiz ID of the context to get.
     * @return \context_module $context The quiz module context.
     */
    public function get_quiz_context(int $quizid): \context_module {
        global $DB;

        $params = array('module' => 'quiz', 'quiz' => $quizid);
        $sql = 'SELECT cm.id
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON cm.module = m.id
            INNER JOIN {quiz} q ON cm.instance = q.id AND cm.course = q.course
                 WHERE m.name = :module
                       AND q.id = :quiz';
        $cmid = $DB->get_field_sql($sql, $params);
        $context = \context_module::instance($cmid);

        return $context;

    }

    /**
     * Get override info for a paricular quiz.
     * Data returned is:
     * Number of users with overrides in Quiz,
     * Ealiest override start,
     * Latest override end.
     *
     * @param int $quizid The ID of the quiz to get override data for.
     * @param \context_module $context The context object of the quiz.
     * @return \stdClass $overrideinfo Information about quiz overrides.
     */
    private function get_quiz_override_info(int $quizid, \context_module $context): \stdClass {
        global $DB;

        $capabilities = array('mod/quiz:attempt', 'mod/quiz:view');
        $overrideinfo = new \stdClass();
        $users = array();
        $start = 0;
        $end = 0;

        $sql = 'SELECT id, userid, COALESCE(timeopen, 0) AS timeopen, COALESCE(timeclose, 0) AS timeclose
                  FROM {quiz_overrides}
                 WHERE  quiz = ?';
        $params = array($quizid);
        $overrides = $DB->get_records_sql($sql, $params);

        foreach ($overrides as $override) {

            if (!has_all_capabilities($capabilities, $context, $override->userid)) {
                continue; // Don't count users who can't access the quiz.
            }

            $users[] = $override->userid;

            if ($override->timeclose > $end) {
                $end = $override->timeclose;
            }

            if ($start == 0) {
                $start = $override->timeopen;
            } else if ($override->timeopen < $start) {
                $start = $override->timeopen;
            }
        }

        $users = count(array_unique($users));

        $overrideinfo->start = $start;
        $overrideinfo->end = $end;
        $overrideinfo->users = $users;

        return $overrideinfo;
    }

    /**
     * Get quiz question infromation.
     * Data returned is:
     * List of individual question types,
     * Count of questions in quiz,
     * Count of question types.
     *
     * @param int $quizid The ID of the quiz to get override data for.
     * @return \stdClass $questions The question data for the quiz.
     */
    private function get_quiz_questions(int $quizid): \stdClass {
        global $DB;
        $questions = new \stdClass();
        $types = array();
        $questioncount = 0;

        $params = array($quizid);
        $sql = 'SELECT q.id, q.name, q.qtype
                  FROM {question} q
            INNER JOIN {quiz_slots} qs ON q.id = qs.questionid
                 WHERE quizid = ?';

        $questionsrecords = $DB->get_records_sql($sql, $params);

        foreach ($questionsrecords as $questionrecord) {
            $types[] = $questionrecord->qtype;
            $questioncount++;
        }

        $types = array_unique($types);

        $questions->types = $types;
        $questions->typecount = count($types);
        $questions->questioncount = $questioncount;

        return $questions;
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
     * @param int $quizid ID of the quiz to get data for.
     * @return \stdClass $quizdata The retrieved quiz data.
     */
    public function get_quiz_data(int $quizid): \stdClass {
        global $DB;
        $quizdata = new \stdClass();
        $context = $this->get_quiz_context($quizid);

        $quizrecord = $DB->get_record('quiz', array('id' => $quizid), 'name, timeopen, timeclose, timelimit, course');
        $course = get_course($quizrecord->course);
        $courseurl = new \moodle_url('/course/view.php', array('id' => $quizrecord->course));

        $overrideinfo = $this->get_quiz_override_info($quizid, $context);
        $questions = $this->get_quiz_questions($quizid);
        $frequency = new frequency();
        $timesopen = userdate($quizrecord->timeopen, get_string('strftimedatetime', 'langconfig'));
        $timeclose = userdate($quizrecord->timeclose, get_string('strftimedatetime', 'langconfig'));
        $overrideinfostart = userdate($overrideinfo->start, get_string('strftimedatetime', 'langconfig'));
        $overrideinfoend = userdate($overrideinfo->end, get_string('strftimedatetime', 'langconfig'));

        // Handle override start.
        if ($overrideinfo->start != 0 && $overrideinfo->start < $quizrecord->timeopen) {
            $earlyopen = $overrideinfostart;
        } else {
            $earlyopen = $timesopen;
        }

        // Handle override end.
        if ($overrideinfo->end != 0 && $overrideinfo->end > $quizrecord->timeclose) {
            $lateclose = $overrideinfoend;
        } else {
            $lateclose = $timeclose;
        }

        // Quiz result link.
        $resultlink = new \moodle_url('/mod/quiz/report.php', array('id' => $context->instanceid, 'mode' => 'overview'));
        // Override link.
        $overrridelink = new \moodle_url('/mod/quiz/overrides.php', array('cmid' => $context->instanceid, 'mode' => 'user'));
        // Participant link.
        $participantlink = new \moodle_url('/user/index.php', array('id' => $quizrecord->course));
        // Dashboard link.
        $dashboardlink = new \moodle_url('/local/assessfreq/dashboard_quiz.php', array('id' => $quizid));

        $quizdata->name = $quizrecord->name;
        $quizdata->timeopen = $timesopen;
        $quizdata->timeclose = $timeclose;
        $quizdata->timelimit = format_time($quizrecord->timelimit);
        $quizdata->earlyopen = $earlyopen;
        $quizdata->lateclose = $lateclose;
        $quizdata->participants = count($frequency->get_event_users_raw($context->id, 'quiz'));
        $quizdata->overrideparticipants = $overrideinfo->users;
        $quizdata->url = $context->get_url()->out(false);
        $quizdata->types = $questions->types;
        $quizdata->typecount = $questions->typecount;
        $quizdata->questioncount = $questions->questioncount;
        $quizdata->resultlink = $resultlink->out(false);
        $quizdata->overridelink = $overrridelink->out(false);
        $quizdata->coursefullname = $course->fullname;
        $quizdata->courseshortname = $course->shortname;
        $quizdata->courselink = $courseurl->out(false);
        $quizdata->participantlink = $participantlink->out(false);
        $quizdata->dashboardlink = $dashboardlink->out(false);

        return $quizdata;
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
    private function get_tracked_overrides(int $now, int $lookahead, int $lookbehind): array {
        global $DB;

        $starttime = $now + $lookahead;
        $endtime = $now - $lookbehind;

        $sql = 'SELECT id, quiz, timeopen, timeclose
                  FROM {quiz_overrides}
                 WHERE (timeopen > 0 AND timeopen < :starttime)
                       AND (timeclose > :endtime OR timeclose > :now)';
        $params = array(
            'starttime' => $starttime,
            'endtime' => $endtime,
            'now' => $now
        );

        $quizzes = $DB->get_records_sql($sql, $params);

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
    private function get_tracked_quizzes(int $now, int $lookahead, int $lookbehind): array {
        global $DB;

        $starttime = $now + $lookahead;
        $endtime = $now - $lookbehind;

        $sql = 'SELECT id, timeopen, timeclose
                  FROM {quiz}
                 WHERE (timeopen > 0 AND timeopen < :starttime)
                       AND (timeclose > :endtime OR timeclose > :now)';
        $params = array(
            'starttime' => $starttime,
            'endtime' => $endtime,
            'now' => $now
        );

        $quizzes = $DB->get_records_sql($sql, $params);

        return $quizzes;
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
    private function get_tracked_quizzes_with_overrides(int $now, int $lookahead=HOURSECS, int $lookbehind=HOURSECS): array {
        $quizzes = $this->get_tracked_quizzes($now, $lookahead, $lookbehind);
        $overrides = $this->get_tracked_overrides($now, $lookahead, $lookbehind);
        $quizoverides = array();

        // Find which quizzes have overrides and adjust start and end times accodingly.
        foreach ($quizzes as $quiz) {
            // Nested for each is bad, but number of overrides should always be small.
            foreach ($overrides as $override) {
                if ($override->quiz == $quiz->id && $override->timeopen < $quiz->timeopen) {
                    $quiz->timeopen = $override->timeopen;
                }

                if ($override->quiz == $quiz->id && $override->timeclose > $quiz->timeclose) {
                    $quiz->timeclose = $override->timeclose;
                }
            }

            $quizoverides[$quiz->id] = $quiz;
        }

        return $quizoverides;

    }

    /**
     * Get counts for inprogress assessments, both total in progress quiz activities
     * and total participants in progress.
     *
     * @param int $now Timestamp to use for reference for time.
     * @return array $quizzes Array of counts of inprogress assessments and participants.
     */
    public function get_inprogress_counts(int $now): array {
        // Get tracked quizzes.
        $trackedquizzes = $this->get_tracked_quizzes_with_overrides($now, 0, 0);

        $counts = array(
            'assessments' => 0,
            'participants' => 0,
        );

        foreach ($trackedquizzes as $quiz) {
            $counts['assessments']++;

            // Get tracked users for quiz.
            $trackedrecords = $this->get_quiz_tracking($quiz->id);
            $tracking = array_pop($trackedrecords);
            $counts['participants'] += $tracking->inprogress;
        }

        return $counts;
    }

    /**
     * Get in progress and upcomming quizzes and their associated data.
     *
     * @param int $now Timestamp to use for reference for time.
     * @return array $quizzes Array of inprogress and upcomming quizzes with associated data.
     */
    public function get_quiz_summaries(int $now): array {
        // Get tracked quizzes.
        $lookahead = HOURSECS * $this->hoursahead;
        $lookbehind = HOURSECS;
        $trackedquizzes = $this->get_tracked_quizzes_with_overrides($now, $lookahead, $lookbehind);

        // Set up array to hold quizzes and data.
        $quizzes = array(
            'inprogress' => array(),
            'upcomming' => array(),
        );

        // Itterate through the hours, processing in progress and upcomming quizzes.
        for ($hour = 0; $hour <= $this->hoursahead; $hour++) {
            $time = $now + (HOURSECS * $hour);

            if ($hour == 0) {
                $quizzes['inprogress'] = array();
            }

            $quizzes['upcomming'][$time] = array();

            //  Seperate out inprogress and upcomming quizzes, then get data for each quiz.
            foreach ($trackedquizzes as $quiz) {
                if ($quiz->timeopen < $time && $quiz->timeclose > $time && $hour === 0) { // Get inprogress quizzes.
                    $quizdata = $this->get_quiz_data($quiz->id);
                    $quizdata->timestampopen = $quiz->timeopen;
                    $quizdata->timestampclose = $quiz->timeclose;

                    // Get tracked users for quiz.
                    $trackedrecords = $this->get_quiz_tracking($quiz->id);
                    $quizdata->tracking = array_pop($trackedrecords);

                    $quizzes['inprogress'][$quiz->id] = $quizdata;
                    unset($trackedquizzes[$quiz->id]); // Remove quiz from array to help with performance.
                } else if (($quiz->timeopen >= $time) && ($quiz->timeopen < ($time + HOURSECS))) { // Get upcomming quizzes.
                    $quizdata = $this->get_quiz_data($quiz->id);
                    $quizdata->timestampopen = $quiz->timeopen;
                    $quizdata->timestampclose = $quiz->timeclose;
                    $quizzes['upcomming'][$time][$quiz->id] = $quizdata;
                    unset($trackedquizzes[$quiz->id]);
                }
            }

        }

        return $quizzes;
    }

    /**
     * Given a list of user ids, check if the user is logged in our not
     * and return summary counts of logged in and not logged in users.
     *
     * @param array $userids User ids to get logged in status.
     * @return \stdClass $usercounts Object with coutns of users logged in and not logged in.
     */
    private function get_loggedin_users(array $userids): \stdClass {
        global $CFG, $DB;

        $maxlifetime = $CFG->sessiontimeout;
        $timedout = time() - $maxlifetime;
        $userchunks = array_chunk($userids, 250); // Break list of users into chunks so we don't exceed DB IN limits.

        $loggedin = 0; // Count of logged in users.
        $loggedout = 0; // Count of not loggedin users.
        $loggedinusers = array();
        $loggedoutusers = array();

        foreach ($userchunks as $userchunk) {
            list($insql, $inparams) = $DB->get_in_or_equal($userchunk);
            $inparams[] = $timedout;

            $sql = "SELECT DISTINCT(userid)
                      FROM {sessions}
                     WHERE userid $insql
                           AND timemodified >= ?";
            $users = $DB->get_fieldset_sql($sql, $inparams);
            $loggedinusers = array_merge($loggedinusers, $users);
        }

        $loggedoutusers = array_diff($userids, $loggedinusers);

        $loggedin = count($loggedinusers);
        $loggedout = count($loggedoutusers);

        $usercounts = new \stdClass();
        $usercounts->loggedin = $loggedin;
        $usercounts->loggedout = $loggedout;
        $usercounts->loggedinusers = $loggedinusers;
        $usercounts->loggedoutusers = $loggedoutusers;

        return $usercounts;

    }

    /**
     * Get count of in porgress and finished attempts for a quiz.
     *
     * @param int $quizid The id of the quiz to get the counts for.
     * @return \stdClass $attemptcounts The found counts.
     */
    private function get_quiz_attempts(int $quizid): \stdClass {
        global $DB;

        $inprogress = 0;
        $finished = 0;
        $inprogressusers = array();
        $finishedusers = array();

        $sql = 'SELECT userid, state
                   FROM {quiz_attempts}
                  WHERE id IN (
                        SELECT MAX(id)
                          FROM {quiz_attempts}
                         WHERE quiz = ?
                      GROUP BY userid)';

        $params = array($quizid);

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

        $attemptcounts = new \stdClass();
        $attemptcounts->inprogress = $inprogress;
        $attemptcounts->finished = $finished;
        $attemptcounts->inprogressusers = $inprogressusers;
        $attemptcounts->finishedusers = $finishedusers;

        return $attemptcounts;

    }

    /**
     * Process and store user tracking information for a quiz.
     *
     * @param int $now Timestamp to use for reference for time.
     * @return int $count Count of processed quizzes
     */
    public function process_quiz_tracking(int $now): int {
        global $DB;

        $frequency = new frequency();
        $quizzes = $this->get_tracked_quizzes_with_overrides($now);
        $count = 0;

        // For each quiz get the list of users who are elligble to do the quiz.
        foreach ($quizzes as $quiz) {
            $context = $this->get_quiz_context($quiz->id);
            $quizusers = array_keys($frequency->get_event_users_raw($context->id, 'quiz'));
            $loggedinusers = $this->get_loggedin_users($quizusers);
            $attemptusers = $this->get_quiz_attempts($quiz->id);
            $loggedout = 0;
            $loggedin = 0;
            $inprogress = 0;
            $finished = 0;

            foreach ($quizusers as $user) {
                if (in_array($user, $attemptusers->finishedusers)) {
                    $finished++;
                    continue;
                } else if (in_array($user, $attemptusers->inprogressusers)) {
                    $inprogress++;
                    continue;
                } else if (in_array($user, $loggedinusers->loggedinusers)) {
                    $loggedin++;
                    continue;
                } else if (in_array($user, $loggedinusers->loggedoutusers)) {
                    $loggedout++;
                    continue;
                }
            }

            $record = new \stdClass();
            $record->assessid = $quiz->id;
            $record->notloggedin = $loggedout;
            $record->loggedin = $loggedin;
            $record->inprogress = $inprogress;
            $record->finished = $finished;
            $record->timecreated = time();

            $DB->insert_record('local_assessfreq_trend', $record);
            $count++;
        }

        return $count;
    }

    /**
     * Given a quiz ID get its tracking information.
     *
     * @param int $quizid The ID of the quiz.
     * @return array $tracking Tracking reocrds for the quiz.
     */
    public function get_quiz_tracking(int $quizid): array {
        global $DB;

        $tracking = $DB->get_records('local_assessfreq_trend', array('assessid' => $quizid), 'timecreated ASC');

        return $tracking;
    }

    /**
     * Given an array of quizzes, filter based on a provided search string and apply pagination.
     *
     * @param array $quizzes Array of quizzes to search.
     * @param string $search The string to search by.
     * @param int $page The page number of results.
     * @param int $pagesize The page size for results.
     * @return array $result Array containing list of filtered quizzes and total of how many quizzes matched the filter.
     */
    public function filter_quizzes(array $quizzes, string $search, int $page, int $pagesize): array {
        $filtered = array();
        $searchfields = array('name', 'coursefullname');
        $offset = $page * $pagesize;
        $offsetcount = 0;
        $recordcount = 0;

        foreach ($quizzes as $id => $quiz) {
            $searchcount = 0;
            if ($search != '') {
                $searchcount = -1;
                foreach ($searchfields as $searchfield) {
                    if (stripos($quiz->{$searchfield}, $search) !== false) {
                        $searchcount++;
                    }
                }
            }

            if ($searchcount > -1 && $offsetcount >= $offset && $recordcount < $pagesize) {
                $filtered[$id] = $quiz;
            }

            if ($searchcount > -1 && $offsetcount >= $offset) {
                $recordcount++;
            }

            if ($searchcount > -1) {
                $offsetcount ++;
            }
        }

        $result = array($filtered, $offsetcount);

        return $result;
    }

    /**
     * Sort an array of quizzes.
     *
     * @param array $quizzes Array of quizzes to sort.
     * @param string $sorton The value to sort the quizzes by.
     * @param string $direction The direction to sort the quizzes.
     * @return array $quizzes the sorted quizzes.
     */
    public function sort_quizzes(array $quizzes, string $sorton, string $direction):array {
        $this->sortdirection = $direction;
        $this->sorton = $sorton;

        // The spaceship operator is used for comparing two expressions.
        // It returns -1, 0 or 1 when $a is respectively less than, equal to, or greater than $b.
        // Comparisons are performed according to PHP's usual type comparison rules.
        uasort($quizzes, function($a, $b) {

            if ($this->sortdirection  == 'asc') {
                return strcasecmp($a->{$this->sorton}, $b->{$this->sorton});
            } else {
                return strcasecmp($b->{$this->sorton}, $a->{$this->sorton});
            }

        });

        return $quizzes;
    }
}
