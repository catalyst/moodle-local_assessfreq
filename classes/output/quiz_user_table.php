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
class quiz_user_table extends table_sql implements renderable {

    /**
     * @var integer $quizid The ID of the braodcast to get the acknowledgements for.
     */
    private $quizid;

    /**
     *
     * @var integer $contextid The context id.
     */
    private $contextid;

    /**
     * @var string[] Extra fields to display.
     */
    protected $extrafields;

    /**
     * report_table constructor.
     *
     * @param string $uniqueid Unique id of table.
     * @param int $quizid The id from the quiz table to get data for.
     * @param int $contextid The context id for the context the table is being displayed in.
     * @param int $page the page number for pagination.
     *
     * @throws \coding_exception
     */
    public function __construct(string $uniqueid, string $baseurl, int $quizid, int $contextid, int $page = 0) {
        parent::__construct($uniqueid);

        $this->quizid = $quizid;
        $this->contextid = $contextid;
        $this->set_attribute('id', 'local_assessfreq_ackreport_table');
        $this->set_attribute('class', 'generaltable generalbox');
        $this->downloadable = false;
        $this->define_baseurl($baseurl);


        $context = \context::instance_by_id($contextid);

        // Define the headers and columns.
        $headers = [];
        $columns = [];

        $headers[] = get_string('fullname');
        $columns[] = 'fullname';

        $extrafields = get_extra_user_fields($context);
        foreach ($extrafields as $field) {
            $headers[] = get_user_field_name($field);
            $columns[] = $field;
        }

        // TODO: Add extra columns, related to the report.

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->extrafields = $extrafields;

        // Setup pagination.
        $this->currpage = $page;
        $this->sortable(true);

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
     * Get content for format column.
     * Requires `metadata` field.
     *
     * @param \stdClass $row
     * @return string html used to display the type field.
     */
    public function col_contextid($row) {

        $context = \context::instance_by_id($row->contextid);
        $name = $context->get_context_name();
        $url = $context->get_url();

        $link = \html_writer::link($url, $name);

        return $this->format_text($link);
    }


    /**
     * Get content for created column.
     * Displays when the conversion was started
     *
     * @param \stdClass $row
     *
     * @return string html used to display the column field.
     */
    public function col_acktime($row) {
        $date = userdate($row->acktime, get_string('strftimedatetime', 'langconfig'));
        return $this->format_text($date);
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

        $frequency = new \local_assessfreq\frequency();
        $quiz = new \local_assessfreq\quiz();
        $capabilities = $frequency->get_module_capabilities('quiz');
        $context = $quiz->get_quiz_context($this->quizid);
        $quizrecord = $DB->get_record('quiz', array('id' => $this->quizid), 'timeopen, timeclose, timelimit');

        list($joins, $wheres, $params) = $frequency->generate_enrolled_wheres_joins_params($context, $capabilities);
        $attemptsql = 'SELECT qa_a.userid, qa_a.state, qa_a.quiz
                         FROM {quiz_attempts} qa_a
                   INNER JOIN (SELECT userid, MAX(timestart) as timestart
                                 FROM {quiz_attempts}
                             GROUP BY userid) qa_b ON qa_a.userid = qa_b.userid
                                              AND qa_a.timestart = qa_b.timestart
                        WHERE qa_a.quiz = :qaquiz';

        $sessionsql = 'SELECT DISTINCT (userid)
                         FROM {sessions}
                        WHERE timemodified >= :stm';

        $joins .= ' LEFT JOIN {quiz_overrides} qo ON u.id = qo.userid';
        $joins .= " LEFT JOIN ($attemptsql) qa ON u.id = qa.userid";
        $joins .= " LEFT JOIN ($sessionsql) us ON u.id = us.userid";

        $params['qaquiz'] = $this->quizid;
        $params['stm'] = $timedout;

        $finaljoin = new \core\dml\sql_join($joins, $wheres, $params);

        $sql = "SELECT u.*,
                       COALESCE(qo.timeopen, $quizrecord->timeopen) AS timeopen,
                       COALESCE(qo.timeclose, $quizrecord->timeclose) AS timeclose,
                       COALESCE(qo.timelimit, $quizrecord->timelimit) AS timelimit,
                       COALESCE(qa.state, (CASE
                                              WHEN us.userid > 0 THEN 'loggedin'
                                              ELSE 'notloggedin'
                                           END)) AS state
                  FROM {user} u
                       $finaljoin->joins
                 WHERE $finaljoin->wheres";

        $countsql = "SELECT COUNT(1)
                  FROM {user} u
                       $finaljoin->joins
                 WHERE $finaljoin->wheres";

        $params = $finaljoin->params;

        $total = $DB->count_records_sql($countsql, $params);
        $this->pagesize($pagesize, $total);

        if (!empty($sort)) {
            $sql .= " ORDER BY $sort";
        }

        // TODO: Add search.

        $records = $DB->get_records_sql($sql, $params, $this->get_page_start(), $this->get_page_size());

        foreach ($records as $record) {
            $this->rawdata[$record->id] = $record;
        }


    }
}
