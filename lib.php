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
 * Inject the competencies elements into all moodle module settings forms.
 *
 * @param moodleform $formwrapper The moodle quickforms wrapper object.
 * @param MoodleQuickForm $mform The actual form object (required to modify the form).
 */
function local_assessfreq_coursemodule_standard_elements($formwrapper, $mform) {
    global $CFG;
    $modname = $formwrapper->get_current()->modulename;  // Gets module name so we can filter.

    // Register the new form element.
    MoodleQuickForm::registerElementType('local_assessfreq_scheduler',
        "$CFG->dirroot/local/assessfreq/classes/form/scheduler.php",
        'scheduler_form_element');

    // TODO: Figure out if this is a new activity or an existing one.
    // If it is new there is no point checking for schedule conflicts.
    // Instead just render the schedule assistnace button. (Further checks will be done via ajax.)

    // Figure out if this is a module we want to override the form for.
    if ($modname === 'quiz') {
        $scheduler =& $mform->createElement('local_assessfreq_scheduler', 'schedular', 'Schedule', 'stuff');
        $mform->insertElementBefore($scheduler, 'timeopen');
        $mform->setExpanded('timing');
    }
}

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

    $o = $output->render_student_table($baseurl, $data->quiz, $context);

    return $o;
}
