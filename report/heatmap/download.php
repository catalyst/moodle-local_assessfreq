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
 * Download heatmap.
 *
 * @package    assessfreqreport_heatmap
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\dataformat;
use core_user\fields;
use local_assessfreq\frequency;

define('NO_OUTPUT_BUFFERING', true);

require_once(dirname(__FILE__, 5) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');

$year = required_param('year', PARAM_INT);
$modules = required_param_array('modules', PARAM_ALPHA);
$metric = required_param('metric', PARAM_ALPHA);

require_login(null, false);

// Capability requirements.
$context = context_system::instance();
$course = get_course(SITEID);

// If we have a course selected, update the PAGE object accordinging.
if ($courseid = optional_param('courseid', 0, PARAM_INT)) {
    $context = context_course::instance($courseid);
    $course = get_course($courseid);
}

$PAGE->set_context($context);

// Set the course to use in subsequent checks.
$PAGE->set_course($course);

// Capability check.
require_capability('assessfreqreport/heatmap:view', $context);

$dataformat = 'csv';
$fields = [
    get_string('download:open', 'assessfreqreport_heatmap'),
    get_string('download:close', 'assessfreqreport_heatmap'),
    get_string('download:activity', 'assessfreqreport_heatmap'),
    get_string('download:title', 'assessfreqreport_heatmap'),
    get_string('download:url', 'assessfreqreport_heatmap'),
];

if ($metric == 'students') {
    $extrafields = fields::get_identity_fields($context, false);
    $fields[] = get_string('fullname');
    $fields = array_merge($fields, $extrafields);
} else {
    $fields[] = get_string('download:students', 'assessfreqreport_heatmap');
}

$frequency = new frequency();
$orderedmonths = get_months_ordered();
$month = array_key_first($orderedmonths);
$data = $frequency->get_download_data($year, $month, $metric, $modules);

$rawfilename = $year . '_' . $metric . '_' . implode('_', $modules);
$filename = clean_filename($rawfilename);
dataformat::download_data($filename, $dataformat, $fields, $data);

die();
