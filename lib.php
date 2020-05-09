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


/**tool_lp
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


    return $preferences;
}

/**
 *
 * @param string $args
 * @return string
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
