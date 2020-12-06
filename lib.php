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
 * This page contains callbacks.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 * Returns the name of the user preferences as well as the details this plugin uses.
 *
 * @return array
 */
function local_assessfreq_user_preferences() {

    $preferences['local_assessfreq_overview_year_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => date('Y'),
        'type' => PARAM_INT
    );

    $preferences['local_assessfreq_heatmap_year_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => date('Y'),
        'type' => PARAM_INT
    );

    $preferences['local_assessfreq_heatmap_metric_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => 'assess',
        'type' => PARAM_ALPHA
    );

    $preferences['local_assessfreq_heatmap_modules_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => '[]',
        'type' => PARAM_RAW
    );

    $preferences['local_assessfreq_quiz_refresh_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => 60,
        'type' => PARAM_INT
    );

    $preferences['local_assessfreq_quiz_table_rows_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => 20,
        'type' => PARAM_INT
    );

    $preferences['local_assessfreq_student_search_table_rows_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => 20,
        'type' => PARAM_INT
    );

    $preferences['local_assessfreq_quiz_table_inprogress_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => 20,
        'type' => PARAM_INT
    );

    $preferences['local_assessfreq_quiz_table_inprogress_sort_preference'] = array(
        'null' => NULL_NOT_ALLOWED,
        'default' => 'name_asc',
        'type' => PARAM_ALPHAEXT
    );

    return $preferences;
}

/**
 * Return the HTML for the given chart.
 *
 * @param string $args JSON from the calling AJAX function.
 * @return string $chartdata The generated chart.
 */
function local_assessfreq_output_fragment_get_chart($args): string {
    $allowedcalls = array(
        'assess_by_month',
        'assess_by_activity',
        'assess_by_month_student'
    );

    $context = $args['context'];
    has_capability('moodle/site:config', $context);
    $data = json_decode($args['data']);

    if (in_array($data->call, $allowedcalls)) {
        $classname = '\\local_assessfreq\\output\\' . $data->call;
        $methodname = 'get_' . $data->call . '_chart';
    } else {
        throw new moodle_exception('Call not allowed');
    }

    $assesschart = new $classname();
    $chart = $assesschart->$methodname($data->year);

    $chartdata = json_encode($chart);
    return $chartdata;
}

/**
 * Return the HTML for the given chart.
 *
 * @param string $args JSON from the calling AJAX function.
 * @return string $chartdata The generated chart.
 */
function local_assessfreq_output_fragment_get_quiz_chart($args): string {
    $allowedcalls = array(
        'participant_summary',
        'participant_trend'
    );

    $context = $args['context'];
    has_capability('moodle/site:config', $context);
    $data = json_decode($args['data']);

    if (in_array($data->call, $allowedcalls)) {
        $classname = '\\local_assessfreq\\output\\' . $data->call;
        $methodname = 'get_' . $data->call . '_chart';
    } else {
        throw new moodle_exception('Call not allowed');
    }

    $assesschart = new $classname();
    $chart = $assesschart->$methodname($data->quiz);

    $chartdata = json_encode($chart);
    return $chartdata;
}

/**
 * Return the HTML for the given chart.
 *
 * @param string $args JSON from the calling AJAX function.
 * @return string $chartdata The generated chart.
 */
function local_assessfreq_output_fragment_get_quiz_inprogress_chart($args): string {
    $allowedcalls = array(
        'upcomming_quizzes',
        'all_participants_inprogress'
    );

    $context = $args['context'];
    has_capability('moodle/site:config', $context);
    $data = json_decode($args['data']);

    if (in_array($data->call, $allowedcalls)) {
        $classname = '\\local_assessfreq\\output\\' . $data->call;
        $methodname = 'get_' . $data->call . '_chart';
    } else {
        throw new moodle_exception('Call not allowed');
    }

    $assesschart = new $classname();
    $now = time();
    $chart = $assesschart->$methodname($now);

    $chartdata = json_encode($chart);
    return $chartdata;
}

/**
 * Renders the quiz search form for the modal on the quiz dashboard.
 *
 * @param array $args
 * @return string $o Form HTML.
 */
function local_assessfreq_output_fragment_new_base_form($args): string {

    $context = $args['context'];
    has_capability('moodle/site:config', $context);

    $mform = new \local_assessfreq\form\quiz_search_form(null, null, 'post', '', array('class' => 'ignoredirty'));

    ob_start();
    $mform->display();
    $o = ob_get_contents();
    ob_end_clean();

    return $o;
}

/**
 * Renders the student table on the quiz dashboard screen.
 * We update the table via ajax.
 *
 * @param array $args
 * @return string $o Form HTML.
 */
function local_assessfreq_output_fragment_get_student_table($args): string {
    global $CFG, $PAGE;

    $context = $args['context'];
    has_capability('moodle/site:config', $context);
    $data = json_decode($args['data']);

    $baseurl = $CFG->wwwroot . '/local/assessfreq/dashboard_quiz.php';
    $output = $PAGE->get_renderer('local_assessfreq');

    $o = $output->render_student_table($baseurl, $data->quiz, $context->id, $data->search, $data->page);

    return $o;
}

/**
 * Renders the student table on the student search screen.
 * We update the table via ajax.
 *
 * @param array $args
 * @return string $o Form HTML.
 */
function local_assessfreq_output_fragment_get_student_search_table($args): string {
    global $CFG, $PAGE;

    $context = $args['context'];
    has_capability('moodle/site:config', $context);
    $data = json_decode($args['data']);
    $search = is_null($data->search) ? '' : $data->search;
    $now = time();
    $hoursahead = 4;
    $hoursbehind = 1;

    $baseurl = $CFG->wwwroot . '/local/assessfreq/student_search.php';
    $output = $PAGE->get_renderer('local_assessfreq');

    $o = $output->render_student_search_table($baseurl, $context->id, $search, $hoursahead, $hoursbehind, $now, $data->page);

    return $o;
}

/**
 * Renders the quizzes in progress "table" on the quiz dashboard screen.
 * We update the table via ajax.
 * The table isn't a real table it's a collection of divs.
 *
 * @param array $args
 * @return string $o Form HTML.
 */
function local_assessfreq_output_fragment_get_quizzes_inprogress_table($args): string {
    global $PAGE;

    $context = $args['context'];
    has_capability('moodle/site:config', $context);

    $data = json_decode($args['data']);
    $search = is_null($data->search) ? '' : $data->search;
    $sorton = is_null($data->sorton) ? 'name' : $data->sorton;
    $direction = is_null($data->direction) ? 'asc' : $data->direction;

    $output = $PAGE->get_renderer('local_assessfreq');
    $o = $output->render_quizzes_inprogress_table($search, $data->page, $sorton, $direction);

    return $o;
}

/**
 * Renders the quiz user override form for the modal on the quiz dashboard.
 *
 * @param array $args
 * @return string $o Form HTML.
 */
function local_assessfreq_output_fragment_new_override_form($args): string {
    global $DB;

    $context = $args['context'];
    has_capability('mod/quiz:manageoverrides', $context);

    $serialiseddata = json_decode($args['jsonformdata'], true);

    $formdata = array();

    if (!empty($serialiseddata)) {
        parse_str($serialiseddata, $formdata);
    }

    // Get some data needed to generate the form.
    $quizid = $args['quizid'];
    $quizdata = new \local_assessfreq\quiz();
    $quizcontext = $quizdata->get_quiz_context($quizid);
    $quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);

    $cm = get_course_and_cm_from_cmid($quizcontext->instanceid, 'quiz')[1];

    // Check if we have an existing override for this user.
    $override = $DB->get_record('quiz_overrides', array('quiz' => $quiz->id, 'userid' => $args['userid']));

    if ($override) {
        $data = clone $override;
    } else {
        $data = new \stdClass();
        $data->userid = $args['userid'];
    }

    $mform = new \local_assessfreq\form\quiz_override_form($cm, $quiz, $quizcontext, $override, $formdata);
    $mform->set_data($data);

    if (!empty($serialiseddata)) {
        // If we were passed non-empty form data we want the mform to call validation functions and show errors.
        $mform->is_validated();
    }

    ob_start();
    $mform->display();
    $o = ob_get_contents();
    ob_end_clean();

    return $o;
}
