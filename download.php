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
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define('NO_OUTPUT_BUFFERING', true);

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$year = required_param('year', PARAM_INT);
$modules = required_param_array('modules', PARAM_ALPHA);
$metric = required_param('metric', PARAM_ALPHA);

require_login(null, false);
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$dataformat = 'csv';
$fields = array(
    get_string('quiztimeopen', 'local_assessfreq'),
    get_string('duedate', 'local_assessfreq'),
    get_string('activity', 'local_assessfreq'),
    get_string('title', 'local_assessfreq'),
    get_string('url', 'local_assessfreq')
);

if ($metric == 'students') {
    $extrafields = \core_user\fields::get_identity_fields($context, false);
    $fields[] = get_string('fullname');
    $fields = array_merge($fields, $extrafields);
} else {
    $fields[] = get_string('students', 'local_assessfreq');
}

$frequency = new \local_assessfreq\frequency();
$data = $frequency->get_download_data($year, $metric, $modules);

$rawfilename = $year . '_' . $metric . '_' . implode('_', $modules);
$filename = clean_filename($rawfilename);
\core\dataformat::download_data($filename, $dataformat, $fields, $data);

die();
