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
 * Main landing page for the reports
 *
 * @package   local_assessfreq
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__, 3) . '/config.php');

require_login();

require_once('lib.php');

// Capability requirements.
$context = context_system::instance();
$course = get_course(SITEID);

// If we have a course selected, update the PAGE object accordinging.
if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
    $context = context_course::instance($courseid);
    $PAGE->set_pagelayout('incourse');
    $course = get_course($courseid);
}

// Capability check.
require_capability('local/assessfreq:view', $context);

$PAGE->set_url('/local/assessfreq');
$PAGE->set_context($context);
// Set the course to use in subsequent checks.
$PAGE->set_course($course);

if ($course->id != SITEID) {
    $PAGE->set_heading($course->fullname);
}
$PAGE->set_title(get_string('pluginname', 'local_assessfreq'));

$output = $PAGE->get_renderer('local_assessfreq');
$PAGE->requires->js_call_amd('local_assessfreq/dashboard', 'init');

/* @var $output local_assessfreq\output\renderer */
$output->render_reports();
