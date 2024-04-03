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
 * @package    assessfreqsource_quiz
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_quiz\output;

use coding_exception;
use context;
use context_system;
use core\dml\sql_join;
use core_user\fields;
use html_writer;
use local_assessfreq\frequency;
use renderable;
use stdClass;
use table_sql;

class user_table extends table_sql implements renderable {

    /**
     * @var integer $quizid The ID of the braodcast to get the acknowledgements for.
     */
    private int $quizid;

    /**
     *
     * @var string $search The string to search for in the table data.
     */
    private string $search;

    /**
     * @var string[] Extra fields to display.
     */
    protected array $extrafields;

    /**
     * @var int $timeopen
     */
    private $timeopen;

    /**
     * @var int $timeclose
     */
    private $timeclose;

    /**
     * @var int $timelimit
     */
    private $timelimit;

    /**
     * @var bool|context|context_system|null The context.
     */
    private $context;


    /**
     * report_table constructor.
     *
     * @param string $baseurl Base URL of the page that contains the table.
     * @param int $quizid The id from the quiz table to get data for.
     * @param int $contextid The context for the context the table is being displayed in.
     * @param string $search The string to search for in the table.
     * @param int $page the page number for pagination.
     *
     * @throws coding_exception
     */
    public function __construct(string $baseurl, int $quizid, int $contextid, string $search, int $page = 0) {
        parent::__construct('assessfreqsource-quiz-student-table');
        global $DB;

        $this->quizid = $quizid;
        $this->context = context::instance_by_id($contextid);
        $this->search = $search;
        $this->set_attribute('class', 'generaltable generalbox');
        $this->downloadable = false;
        $this->define_baseurl($baseurl);

        $quizrecord = $DB->get_record('quiz', ['id' => $this->quizid], 'timeopen, timeclose, timelimit');
        $this->timeopen = $quizrecord->timeopen;
        $this->timeclose = $quizrecord->timeclose;
        $this->timelimit = $quizrecord->timelimit;

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $extrafields = fields::get_identity_fields($this->context, false);
        foreach ($extrafields as $field) {
            $headers[] = fields::get_display_name($field);
            $columns[] = $field;
        }

        $this->define_columns(array_merge($columns, $this->get_common_columns()));
        $this->define_headers(array_merge($headers, $this->get_common_headers()));
        $this->extrafields = $extrafields;

        // Setup pagination.
        $this->currpage = $page;
        $this->sortable(true);
        $this->column_nosort = ['actions'];
    }

    /**
     * This function is used for the extra user fields.
     *
     * These are being dynamically added to the table so there are no functions 'col_<userfieldname>' as
     * the list has the potential to increase in the future and we don't want to have to remember to add
     * a new method to this class. We also don't want to pollute this class with unnecessary methods.
     *
     * @param string $column The column name
     * @param stdClass $row
     * @return string
     */
    public function other_cols($column, $row) : string {
        // Do not process if it is not a part of the extra fields.
        if (!in_array($column, $this->extrafields)) {
            return '';
        }

        return s($row->{$column});
    }

    /**
     * Get content for time open column.
     * Displays when the user attempt opens.
     *
     * @param stdClass $row
     * @return string html used to display the field.
     */
    public function col_timeopen(stdClass $row) : string {
        $datetime = userdate($row->timeopen, get_string('studentattempt:trenddatetime', 'assessfreqsource_quiz'));

        if ($row->timeopen != $this->timeopen) {
            $content = html_writer::span($datetime, 'local-assessfreq-override-status');
        } else {
            $content = html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for time close column.
     * Displays when the user attempt closes.
     *
     * @param stdClass $row
     * @return string html used to display the field.
     */
    public function col_timeclose(stdClass $row) : string {
        $datetime = userdate($row->timeclose, get_string('studentattempt:trenddatetime', 'assessfreqsource_quiz'));

        if ($row->timeclose != $this->timeclose) {
            $content = html_writer::span($datetime, 'local-assessfreq-override-status');
        } else {
            $content = html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for time limit column.
     * Displays the time the user has to finsih the quiz.
     *
     * @param stdClass $row
     * @return string html used to display the field.
     */
    public function col_timelimit(stdClass $row) : string {
        $timelimit = format_time($row->timelimit);

        if ($row->timelimit != $this->timelimit) {
            $content = html_writer::span($timelimit, 'local-assessfreq-override-status');
        } else {
            $content = html_writer::span($timelimit);
        }

        return $content;
    }

    /**
     * Get content for actions column.
     * Displays the actions for the user.
     *
     * @param stdClass $row
     * @return string html used to display the field.
     */
    public function col_actions(stdClass $row) : string {
        global $OUTPUT;

        $manage = '';

        $icon = $OUTPUT->render(new \pix_icon('i/duration', ''));
        $manage .= html_writer::link('#', $icon, [
            'class' => 'action-icon override',
            'id' => 'tool-assessfreq-override-' . $row->id,
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => get_string('studentattempt:useroverride', 'assessfreqsource_quiz'),
        ]);

        $manage .= $this->get_common_column_actions($row);

        return $manage;
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
        $sort = $this->get_sql_sort();

        // We never want initial bars. We are using a custom search.
        $this->initialbars(false);

        $frequency = new frequency();
        $capabilities = $frequency->get_module_capabilities('quiz');

        [$joins, $wheres, $params] = $frequency->generate_enrolled_wheres_joins_params($this->context, $capabilities);
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

        $params['qaquiz'] = $this->quizid;
        $params['qoquiz'] = $this->quizid;
        $params['stm'] = $timedout;

        $finaljoin = new sql_join($joins, $wheres, $params);
        $params = $finaljoin->params;

        $sql = "SELECT u.*,
                       COALESCE(qo.timeopen, $this->timeopen) AS timeopen,
                       COALESCE(qo.timeclose, $this->timeclose) AS timeclose,
                       COALESCE(qo.timelimit, $this->timelimit) AS timelimit,
                       COALESCE(qa.state, (CASE
                                              WHEN us.userid > 0 THEN 'loggedin'
                                              ELSE 'notloggedin'
                                           END)) AS state,
                       qa.attemptid,
                       qa.timestart,
                       qa.timefinish
                  FROM {user} u
                       $finaljoin->joins
                 WHERE $finaljoin->wheres";

        $pagesize = get_user_preferences('assessfreqsource_quiz_table_rows_preference', 20);

        if (!empty($sort)) {
            $sql .= " ORDER BY $sort";
        }

        $records = $DB->get_recordset_sql($sql, $params);
        $data = [];
        $offset = $this->currpage * $pagesize;
        $offsetcount = 0;
        $recordcount = 0;

        foreach ($records as $record) {
            $searchcount = 0;
            if ($this->search != '') {
                // Because we are using COALESE and CASE for state we can't use SQL WHERE so we need to filter in PHP land.
                // Also because we need to do some filtering in PHP land, we'll do it all here.
                $searchcount = -1;
                $searchfields = array_merge($this->extrafields, ['firstname', 'lastname', 'state']);

                foreach ($searchfields as $searchfield) {
                    if (stripos($record->{$searchfield}, $this->search) !== false) {
                        $searchcount++;
                    }
                }
            }

            if ($searchcount > -1 && $offsetcount >= $offset && $recordcount < $pagesize) {
                $data[$record->id] = $record;
            }

            if ($searchcount > -1 && $offsetcount >= $offset) {
                $recordcount++;
            }

            if ($searchcount > -1) {
                $offsetcount++;
            }
        }

        $records->close();

        $this->pagesize($pagesize, $offsetcount);
        $this->rawdata = $data;
    }

    /**
     * Get content for title column.
     *
     * @param stdClass $row
     * @return string html used to display the video field.
     * @throws \moodle_exception
     */
    public function col_fullname($row) : string {
        global $OUTPUT;

        return $OUTPUT->user_picture($row, ['size' => 35, 'includefullname' => true]);
    }

    /**
     * Get content for time start column.
     * Displays the user attempt start time.
     *
     * @param stdClass $row
     * @return string html used to display the field.
     */
    public function col_timestart(stdClass $row) : string {
        if ($row->timestart == 0) {
            $content = html_writer::span(get_string('studentattempt:na', 'assessfreqsource_quiz'));
        } else {
            $datetime = userdate($row->timestart, get_string('studentattempt:trenddatetime', 'assessfreqsource_quiz'));
            $content = html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for time finish column.
     * Displays the user attempt finish time.
     *
     * @param stdClass $row
     * @return string html used to display the field.
     */
    public function col_timefinish($row) {
        if ($row->timefinish == 0 && $row->timestart == 0) {
            $content = html_writer::span(get_string('studentattempt:na', 'assessfreqsource_quiz'));
        } else if ($row->timefinish == 0 && $row->timestart > 0) {
            $time = $row->timestart + $row->timelimit;
            $datetime = userdate($time, get_string('studentattempt:trenddatetime', 'assessfreqsource_quiz'));
            $content = html_writer::span($datetime, 'local-assessfreq-disabled');
        } else {
            $datetime = userdate($row->timefinish, get_string('studentattempt:trenddatetime', 'assessfreqsource_quiz'));
            $content = html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for state column.
     * Displays the users state in the quiz.
     *
     * @param stdClass $row
     * @return string html used to display the field.
     */
    public function col_state(stdClass $row) : string {

        $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'notloggedincolor');
        if ($row->state == 'notloggedin') {
            $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'notloggedincolor');
        } else if ($row->state == 'loggedin') {
            $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'loggedincolor');
        } else if ($row->state == 'inprogress') {
            $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'inprogresscolor');
        } else if ($row->state == 'uploadpending') {
            $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'inprogresscolor');
        } else if ($row->state == 'finished') {
            $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'finishedcolor');
        } else if ($row->state == 'abandoned') {
            $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'finishedcolor');
        } else if ($row->state == 'overdue') {
            $color = 'background: ' . get_config('assessfreqreport_activity_dashboard', 'finishedcolor');
        }

        $content = html_writer::span('', 'local-assessfreq-status-icon', ['style' => $color]);
        $content .= get_string("studentattempt:{$row->state}", 'assessfreqsource_quiz');

        return $content;
    }

    /**
     * Return an array of headers common across dashboard tables.
     *
     * @return array
     */
    protected function get_common_headers() : array {
        return [
            get_string('studentattempt:quiztimeopen', 'assessfreqsource_quiz'),
            get_string('studentattempt:quiztimeclose', 'assessfreqsource_quiz'),
            get_string('studentattempt:quiztimelimit', 'assessfreqsource_quiz'),
            get_string('studentattempt:quiztimestart', 'assessfreqsource_quiz'),
            get_string('studentattempt:quiztimefinish', 'assessfreqsource_quiz'),
            get_string('studentattempt:status', 'assessfreqsource_quiz'),
            get_string('studentattempt:actions', 'assessfreqsource_quiz'),
        ];
    }

    /**
     * Return an array of columns common across dashboard tables.
     *
     * @return array
     */
    protected function get_common_columns(): array {
        return [
            'timeopen',
            'timeclose',
            'timelimit',
            'timestart',
            'timefinish',
            'state',
            'actions',
        ];
    }

    /**
     * Return HTML for common column actions.
     *
     * @param stdClass $row
     * @return string
     */
    protected function get_common_column_actions(stdClass $row): string {
        global $OUTPUT;
        $actions = '';
        if (
            $row->state == 'finished'
            || $row->state == 'inprogress'
            || $row->state == 'uploadpending'
            || $row->state == 'abandoned'
            || $row->state == 'overdue'
        ) {
            $classes = 'action-icon';
            $attempturl = new \moodle_url('/mod/quiz/review.php', ['attempt' => $row->attemptid]);
            $attributes = [
                'class' => $classes,
                'id' => 'tool-assessfreq-attempt-' . $row->id,
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'title' => get_string('studentattempt:userattempt', 'assessfreqsource_quiz'),
            ];
        } else {
            $classes = 'action-icon disabled';
            $attempturl = '#';
            $attributes = [
                'class' => $classes,
                'id' => 'tool-assessfreq-attempt-' . $row->id,
            ];
        }
        $icon = $OUTPUT->render(new \pix_icon('i/search', ''));
        $actions .= html_writer::link($attempturl, $icon, $attributes);

        $profileurl = new \moodle_url('/user/profile.php', ['id' => $row->id]);
        $icon = $OUTPUT->render(new \pix_icon('i/completion_self', ''));
        $actions .= html_writer::link($profileurl, $icon, [
            'class' => 'action-icon',
            'id' => 'tool-assessfreq-profile-' . $row->id,
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => get_string('studentattempt:userprofile', 'assessfreqsource_quiz'),
        ]);

        $logurl = new \moodle_url('/report/log/user.php', ['id' => $row->id, 'course' => 1, 'mode' => 'all']);
        $icon = $OUTPUT->render(new \pix_icon('i/report', ''));
        $actions .= html_writer::link($logurl, $icon, [
            'class' => 'action-icon',
            'id' => 'tool-assessfreq-log-' . $row->id,
            'data-toggle' => 'tooltip',
            'data-placement' => 'top',
            'title' => get_string('studentattempt:userlogs', 'assessfreqsource_quiz'),
        ]);
        return $actions;
    }

    public function get_report() {
        ob_start();
        $this->out(50, true);
        $participanttablehtml = ob_get_contents();
        ob_end_clean();

        return $participanttablehtml;
    }
}
