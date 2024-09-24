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
use local_assessfreq\frequency;
use local_assessfreq\source_base;
use local_assessfreq\report_base;

/**
 * This page contains callbacks.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function extends the navigation with the report link.
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 */
function local_assessfreq_extend_navigation_course(navigation_node $navigation, stdClass $course, context $context) {
    if (has_capability('local/assessfreq:view', $context)) {
        $url = new moodle_url('/local/assessfreq/', ['courseid' => $course->id]);
        $settingsnode = navigation_node::create(get_string('pluginname', 'local_assessfreq'), $url);
        $reportnode = $navigation->get('coursereports');
        if (isset($settingsnode) && !empty($reportnode)) {
            $reportnode->add_node($settingsnode);
        }
    }
}

/**
 * Get all of the subplugin reports that are enabled and instantiate the class.
 *
 * @param $ignoreenabled
 * @return array
 */
function get_reports($ignoreenabled = false) : array {
    $reports = [];
    $pluginmanager = core_plugin_manager::instance();
    foreach ($pluginmanager->get_plugins_of_type('assessfreqreport') as $subplugin) {
        /* @var $class report_base */
        if ($subplugin->is_enabled() || $ignoreenabled) {
            $class = "assessfreqreport_{$subplugin->name}\\report";
            $report = $class::get_instance();
            if ($report->has_access()) {
                $reports[$subplugin->name] = $report;
            }
        }
    }
    return $reports;
}

/**
 * Get all of the subplugin sources that are enabled and instantiate the class.
 *
 * @param $ignoreenabled
 * @return array
 */
function get_sources($ignoreenabled = false) : array {
    $sources = [];
    $pluginmanager = core_plugin_manager::instance();
    foreach ($pluginmanager->get_plugins_of_type('assessfreqsource') as $subplugin) {
        if ($subplugin->is_enabled() || $ignoreenabled) {
            /* @var $class source_base */
            $class = "assessfreqsource_{$subplugin->name}\\source";
            $source = $class::get_instance();
            $sources[$subplugin->name] = $source;
        }
    }
    return $sources;
}

/**
 * Using the start month defined in config get an ordered year of month names.
 *
 * @return array
 */
function get_months_ordered() : array {

    $months = [];
    $startmonth = get_config('local_assessfreq', 'start_month');

    for ($i = $startmonth; $i < $startmonth + 12; $i++) {
        $month = $i - 12 > 0 ? $i - 12 : $i;

        $date = DateTime::createFromFormat('!m', $month);
        $monthname = $date->format('F');

        $months[$month] = $monthname;
    }

    return $months;
}

/**
 * Get the years that have events with the preferred year active.
 *
 * @param $preference
 * @return array
 */
function get_years($preference) : array {

    $currentyear = date('Y');

    // Get years that have events and load into context.
    $frequency = new frequency();
    $yearlist = $frequency->get_years_has_events();

    if (empty($yearlist)) {
        $yearlist = [$currentyear];
    }

    // Add current year to the selection of years if missing.
    if (!in_array($currentyear, $yearlist)) {
        $yearlist[] = $currentyear;
    }

    $years = [];

    foreach ($yearlist as $year) {
        $years[$year] = ['year' => ['val' => $year]];
    }

    if (!$preference) {
        $preference = date('Y');
    }

    $years[$preference]['year']['active'] = true;

    return array_values($years);
}

/**
 * Get the modules to use in data collection.
 * This is based on which sources have been enabled.
 *
 * @return array $modules The enabled modules.
 */
function get_modules($preferences) : array {

    $sources = get_sources();

    // Get modules for filters and load into context.
    $modules = [];
    $modules['all'] = ['module' => ['val' => 'all', 'name' => get_string('all')]];

    foreach ($sources as $source) {
        $modulename = get_string('modulename', $source->get_module());
        $modules[$source->get_module()] = ['module' => ['val' => $source->get_module(), 'name' => $modulename]];
    }

    if (!$preferences) {
        $preferences = ["all"];
    }

    foreach ($preferences as $preference) {
        if (isset($modules[$preference])) {
            $modules[$preference]['module']['active'] = true;
        }
    }

    return array_values($modules);
}

/**
 * Given a list of user ids, check if the user is logged in our not
 * and return summary counts of logged in and not logged in users.
 *
 * @param array $userids User ids to get logged in status.
 * @return stdClass $usercounts Object with coutns of users logged in and not logged in.
 */
function get_loggedin_users(array $userids): stdClass {
    global $CFG, $DB;

    $maxlifetime = $CFG->sessiontimeout;
    $timedout = time() - $maxlifetime;
    $userchunks = array_chunk($userids, 250); // Break list of users into chunks so we don't exceed DB IN limits.

    $loggedinusers = [];

    foreach ($userchunks as $userchunk) {
        [$insql, $inparams] = $DB->get_in_or_equal($userchunk);
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

    $usercounts = new stdClass();
    $usercounts->loggedin = $loggedin;
    $usercounts->loggedout = $loggedout;
    $usercounts->loggedinusers = $loggedinusers;
    $usercounts->loggedoutusers = $loggedoutusers;

    return $usercounts;
}

/**
 * Renders the user override form for the modal.
 *
 * @param array $args
 * @return string $o Form HTML.
 */
function local_assessfreq_output_fragment_new_override_form($args): string {
    global $DB, $CFG;

    $module = $args['activitytype'];

    $serialiseddata = json_decode($args['jsonformdata'], true);

    $formdata = [];

    if (!empty($serialiseddata)) {
        parse_str($serialiseddata, $formdata);
    }

    $sources = get_sources();
    $source = $sources[$module];
    $o = '';
    /* @var $source source_base */
    if (method_exists($source, 'get_override_form')) {
        $mform = $source->get_override_form($args['activityid'], $args['context'], $args['userid'], $serialiseddata);
        ob_start();
        $mform->display();
        $o = ob_get_contents();
        ob_end_clean();
    }

    return $o;
}
