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
 * Renderable table for quiz dashboard users.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/tablelib.php');

use \table_sql;
use \renderable;

/**
 * Renderable table for quiz dashboard users.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class student_search_table extends table_sql implements renderable {

    /**
     * Ammount of time in hours for lookahead values.
     *
     * @var int $hoursahead.
     */
    private $hoursahead;

    /**
     * Ammount of time in hours for lookbehind values.
     *
     * @var int $hoursahead.
     */
    private $hoursbehind;

    /**
     * The timestamp used when getting quiz data.
     *
     * @var int $now.
     */
    private $now;

    /**
     *
     * @var string $search The string to search for in the table data.
     */
    private $search;

    /**
     * @var string[] Extra fields to display.
     */
    protected $extrafields;

    /**
     * report_table constructor.
     *
     * @param string $baseurl Base URL of the page that contains the table.
     * @param int $contextid The context id for the context the table is being displayed in.
     * @param string $search The string to search for in the table.
     * @param int $hoursahead Ammount of time in hours to look ahead for quizzes starting.
     * @param int $hoursbehind Ammount of time in hours to look behind for quizzes starting.
     * @param int $now The timestamp to use for the current time.
     * @param int $page the page number for pagination.
     *
     * @throws \coding_exception
     */
    public function __construct(
        string $baseurl, int $contextid, string $search, int $hoursahead, int $hoursbehind, int $now, int $page=0) {
        parent::__construct('local_assessfreq_student_search_table');

        $this->search = $search;
        $this->set_attribute('id', 'local_assessfreq_ackreport_table');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->downloadable = false;
        $this->define_baseurl($baseurl);
        $this->hoursahead = $hoursahead;
        $this->hoursbehind = $hoursbehind;
        $this->now = $now;

        $context = \context::instance_by_id($contextid);

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $extrafields = \core_user\fields::get_identity_fields($context, false);
        foreach ($extrafields as $field) {
            $headers[] = \core_user\fields::get_display_name($field);
            $columns[] = $field;
        }

        $headers[] = get_string('quiz', 'local_assessfreq');
        $columns[] = 'quizname';

        $headers[] = get_string('quiztimeopen', 'local_assessfreq');
        $columns[] = 'timeopen';

        $headers[] = get_string('quiztimeclose', 'local_assessfreq');
        $columns[] = 'timeclose';

        $headers[] = get_string('quiztimelimit', 'local_assessfreq');
        $columns[] = 'timelimit';

        $headers[] = get_string('quiztimestart', 'local_assessfreq');
        $columns[] = 'timestart';

        $headers[] = get_string('quiztimefinish', 'local_assessfreq');
        $columns[] = 'timefinish';

        $headers[] = get_string('status', 'local_assessfreq');
        $columns[] = 'state';

        $headers[] = get_string('actions', 'local_assessfreq');
        $columns[] = 'actions';

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->extrafields = $extrafields;

        // Setup pagination.
        $this->currpage = $page;
        $this->sortable(true);
        $this->column_nosort = array('actions');

    }

    /**
     * Get content for title column.
     *
     * @param \stdClass $row
     * @return string html used to display the video field.
     * @throws \moodle_exception
     */
    public function col_fullname($row) {
        global $OUTPUT;

        return $OUTPUT->user_picture($row, array('size' => 35, 'includefullname' => true));
    }

    /**
     * This function is used for the extra user fields.
     *
     * These are being dynamically added to the table so there are no functions 'col_<userfieldname>' as
     * the list has the potential to increase in the future and we don't want to have to remember to add
     * a new method to this class. We also don't want to pollute this class with unnecessary methods.
     *
     * @param string $colname The column name
     * @param \stdClass $data
     * @return string
     */
    public function other_cols($colname, $data) {
        // Do not process if it is not a part of the extra fields.
        if (!in_array($colname, $this->extrafields)) {
            return '';
        }

        return s($data->{$colname});
    }

    /**
     * Displays quiz name
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_quizname($row) {

        $quizurl = new \moodle_url('/mod/quiz/view.php', array('id' => $row->quizinstance));
        $quizlink = \html_writer::link($quizurl, format_string($row->quizname, true));

        return $quizlink;
    }

    /**
     * Get content for time open column.
     * Displays when the user attempt opens.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_timeopen($row) {
        $datetime = userdate($row->timeopen, get_string('trenddatetime', 'local_assessfreq'));

        if ($row->timeopen != $row->quiztimeopen) {
            $content = \html_writer::span($datetime, 'local-assessfreq-override-status');
        } else {
            $content = \html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for time close column.
     * Displays when the user attempt closes.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_timeclose($row) {
        $datetime = userdate($row->timeclose, get_string('trenddatetime', 'local_assessfreq'));

        if ($row->timeclose != $row->quiztimeclose) {
            $content = \html_writer::span($datetime, 'local-assessfreq-override-status');
        } else {
            $content = \html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for time limit column.
     * Displays the time the user has to finsih the quiz.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_timelimit($row) {
        $timelimit = format_time($row->timelimit);

        if ($row->timelimit != $row->quiztimelimit) {
            $content = \html_writer::span($timelimit, 'local-assessfreq-override-status');
        } else {
            $content = \html_writer::span($timelimit);
        }

        return $content;
    }

    /**
     * Get content for time start column.
     * Displays the user attempt start time.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_timestart($row) {
        if ($row->timestart == 0) {
            $content = \html_writer::span(get_string('na', 'local_assessfreq'));
        } else {
            $datetime = userdate($row->timestart, get_string('trenddatetime', 'local_assessfreq'));
            $content = \html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for time finish column.
     * Displays the user attempt finish time.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_timefinish($row) {
        if ($row->timefinish == 0 && $row->timestart == 0) {
            $content = \html_writer::span(get_string('na', 'local_assessfreq'));
        } else if ($row->timefinish == 0 && $row->timestart > 0) {
            $time = $row->timestart + $row->timelimit;
            $datetime = userdate($time, get_string('trenddatetime', 'local_assessfreq'));
            $content = \html_writer::span($datetime, 'local-assessfreq-disabled');
        } else {
            $datetime = userdate($row->timefinish, get_string('trenddatetime', 'local_assessfreq'));
            $content = \html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for state column.
     * Displays the users state in the quiz.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_state($row) {
        if ($row->state == 'notloggedin') {
            $color = 'background: ' . get_config('local_assessfreq', 'notloggedincolor');
        } else if ($row->state == 'loggedin') {
            $color = 'background: ' . get_config('local_assessfreq', 'loggedincolor');
        } else if ($row->state == 'inprogress') {
            $color = 'background: ' . get_config('local_assessfreq', 'inprogresscolor');
        } else if ($row->state == 'uploadpending') {
            $color = 'background: ' . get_config('local_assessfreq', 'inprogresscolor');
        } else if ($row->state == 'finished') {
            $color = 'background: ' . get_config('local_assessfreq', 'finishedcolor');
        } else if ($row->state == 'abandoned') {
            $color = 'background: ' . get_config('local_assessfreq', 'finishedcolor');
        } else if ($row->state == 'overdue') {
            $color = 'background: ' . get_config('local_assessfreq', 'finishedcolor');
        }

        $content = \html_writer::span('', 'local-assessfreq-status-icon', array('style' => $color));
        $content .= get_string($row->state, 'local_assessfreq');

        return $content;
    }

    /**
     * Get content for actions column.
     * Displays the actions for the user.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_actions($row) {
        global $OUTPUT;

        $manage = '';

        $icon = $OUTPUT->render(new \pix_icon('i/duration', ''));
        $manage .= \html_writer::link('#', $icon, array(
            'class' => 'action-icon override',
            'id' => 'tool-assessfreq-override-' . $row->id . '-' . $row->quiz,
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => get_string('useroverride', 'local_assessfreq')
        ));

        if ($row->state == 'finished'
                || $row->state == 'inprogress'
                || $row->state == 'uploadpending'
                || $row->state == 'abandoned'
                || $row->state == 'overdue') {
            $classes = 'action-icon';
            $attempturl = new \moodle_url('/mod/quiz/review.php', array('attempt' => $row->attemptid));
            $attributes = array(
                'class' => $classes,
                'id' => 'tool-assessfreq-attempt-' . $row->id,
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'title' => get_string('userattempt', 'local_assessfreq')
            );
        } else {
            $classes = 'action-icon disabled';
            $attempturl = '#';
            $attributes = array(
                'class' => $classes,
                'id' => 'tool-assessfreq-attempt-' . $row->id,
            );
        }
        $icon = $OUTPUT->render(new \pix_icon('i/search', ''));
        $manage .= \html_writer::link($attempturl, $icon, $attributes);

        $profileurl = new \moodle_url('/user/profile.php', array('id' => $row->id));
        $icon = $OUTPUT->render(new \pix_icon('i/completion_self', ''));
        $manage .= \html_writer::link($profileurl, $icon, array(
            'class' => 'action-icon',
            'id' => 'tool-assessfreq-profile-' . $row->id,
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => get_string('userprofile', 'local_assessfreq')
            ));

        $logurl = new \moodle_url('/report/log/user.php', array('id' => $row->id, 'course' => 1, 'mode' => 'all'));
        $icon = $OUTPUT->render(new \pix_icon('i/report', ''));
        $manage .= \html_writer::link($logurl, $icon, array(
            'class' => 'action-icon',
            'id' => 'tool-assessfreq-log-' . $row->id,
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => get_string('userlogs', 'local_assessfreq')
        ));

        return $manage;
    }

    /**
     * Sort an array of quizzes.
     *
     * @param array $quizzes Array of quizzes to sort.
     * @return array $quizzes the sorted quizzes.
     */
    public function sort_quizzes(array $quizzes):array {
        // Comparisons are performed according to PHP's usual type comparison rules.
        uasort($quizzes, function($a, $b) {

            $sort = $this->get_sql_sort();
            $sortobj = explode(' ', $sort);
            $sorton = $sortobj[0];
            $dir = $sortobj[1];

            if ($dir == 'ASC') {
                if (gettype($a->{$sorton}) == 'string') {
                    return strcasecmp($a->{$sorton}, $b->{$sorton});
                } else {
                    // The spaceship operator is used for comparing two expressions.
                    // It returns -1, 0 or 1 when $a is respectively less than, equal to, or greater than $b.
                    return $a->{$sorton} <=> $b->{$sorton};
                }
            } else {
                if (gettype($a->{$sorton}) == 'string') {
                    return strcasecmp($b->{$sorton}, $a->{$sorton});
                } else {
                    // The spaceship operator is used for comparing two expressions.
                    // It returns -1, 0 or 1 when $a is respectively less than, equal to, or greater than $b.
                    return $b->{$sorton} <=> $a->{$sorton};
                }
            }

        });

        return $quizzes;
    }

    /**
     * Query the database for results to display in the table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = false) {
        global $CFG, $DB;

        $maxlifetime = $CFG->sessiontimeout;
        $timedout = time() - $maxlifetime;

        // We never want initial bars. We are using a custom search.
        $this->initialbars(false);

        $frequency = new \local_assessfreq\frequency();
        $capabilities = $frequency->get_module_capabilities('quiz');

        // Get the quizzes that we want users for.
        $quiz = new \local_assessfreq\quiz($this->hoursahead, $this->hoursbehind);
        $allquizzes = $quiz->get_quiz_summaries($this->now);

        $inprogressquizzes = $allquizzes['inprogress'];
        $upcommingquizzes = array();
        $finishedquizzes = array();

        foreach ($allquizzes['upcomming'] as $upcomming) {
            foreach ($upcomming as $quizobj) {
                $upcommingquizzes[] = $quizobj;
            }
        }

        foreach ($allquizzes['finished'] as $finished) {
            foreach ($finished as $quizobj) {
                $finishedquizzes[] = $quizobj;
            }
        }

        $quizzes = array_merge($inprogressquizzes, $upcommingquizzes, $finishedquizzes);

        $allrecords = array();

        foreach ($quizzes as $quizobj) {
            $context = $quiz->get_quiz_context($quizobj->assessid);

            list($joins, $wheres, $params) = $frequency->generate_enrolled_wheres_joins_params($context, $capabilities);
            $attemptsql = 'SELECT qa_a.userid, qa_a.state, qa_a.quiz, qa_a.id as attemptid,
                              qa_a.timestart as timestart, qa_a.timefinish as timefinish
                         FROM {quiz_attempts} qa_a
                   INNER JOIN (SELECT userid, MAX(timestart) as timestart
                                 FROM {quiz_attempts}
                             GROUP BY userid) qa_b ON qa_a.userid = qa_b.userid
                                              AND qa_a.timestart = qa_b.timestart
                        WHERE qa_a.quiz = :qaquiz';

            $sessionsql = 'SELECT DISTINCT (userid)
                         FROM {sessions}
                        WHERE timemodified >= :stm';

            $joins .= ' LEFT JOIN {quiz_overrides} qo ON u.id = qo.userid AND qo.quiz = :qoquiz';
            $joins .= " LEFT JOIN ($attemptsql) qa ON u.id = qa.userid";
            $joins .= " LEFT JOIN ($sessionsql) us ON u.id = us.userid";

            $params['qaquiz'] = $quizobj->assessid;
            $params['qoquiz'] = $quizobj->assessid;
            $params['stm'] = $timedout;

            $finaljoin = new \core\dml\sql_join($joins, $wheres, $params);
            $params = $finaljoin->params;

            // If a quiz has overrides, get only students that are in the window time.
            if ($quizobj->isoverride) {
                $userids = array();
                foreach ($quizobj->overrides as $override) {
                    $userids[] = $override->userid;
                }

                list($insql, $inparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'usr');
                $finaljoin->wheres = $finaljoin->wheres . " AND u.id " .  $insql;
                $params = array_merge($params, $inparams);

            }

            $sql = "SELECT u.*,
                       COALESCE(qo.timeopen, $quizobj->timestampopen) AS timeopen,
                       COALESCE(qo.timeclose, $quizobj->timestampclose) AS timeclose,
                       COALESCE(qo.timelimit, $quizobj->timestamplimit) AS timelimit,
                       COALESCE(qa.state, (CASE
                                              WHEN us.userid > 0 THEN 'loggedin'
                                              ELSE 'notloggedin'
                                           END)) AS state,
                       qa.attemptid,
                       qa.timestart,
                       qa.timefinish,
                       $quizobj->assessid AS quiz,
                       $context->instanceid as quizinstance,
                       $quizobj->timestampopen AS quiztimeopen,
                       $quizobj->timestampclose AS quiztimeclose,
                       $quizobj->timestamplimit AS quiztimelimit,
                       '$quizobj->name' AS quizname
                  FROM {user} u
                       $finaljoin->joins
                 WHERE $finaljoin->wheres";

             $records = $DB->get_records_sql($sql, $params);
             $allrecords = array_merge($allrecords, $records);
        }

        if (!empty($this->get_sql_sort())) {
            $allrecords = $this->sort_quizzes($allrecords);
        }

        $pagesize = get_user_preferences('local_assessfreq_student_search_table_rows_preference', 20);
        $data = array();
        $offset = $this->currpage * $pagesize;
        $offsetcount = 0;
        $recordcount = 0;

        foreach ($allrecords as $key => $record) {
            $searchcount = 0;
            if ($this->search != '') {
                // Because we are using COALESCE and CASE for state we can't use SQL WHERE so we need to filter in PHP land.
                // Also because we need to do some filtering in PHP land, we'll do it all here.
                $searchcount = -1;
                $searchfields = array_merge($this->extrafields, array('firstname', 'lastname', 'state', 'quiz'));

                foreach ($searchfields as $searchfield) {
                    if (stripos($record->{$searchfield}, $this->search) !== false) {
                        $searchcount++;
                    }
                }

            }

            if ($searchcount > -1 && $offsetcount >= $offset && $recordcount < $pagesize) {
                $data[$key] = $record;
            }

            if ($searchcount > -1 && $offsetcount >= $offset) {
                $recordcount++;
            }

            if ($searchcount > -1) {
                $offsetcount ++;
            }

        }

        $this->pagesize($pagesize, $offsetcount);
        $this->rawdata = $data;
    }
}
