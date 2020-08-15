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
 * Quiz dashboard.
 *
 * @package     local_assessfreq
 * @copyright   2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$quizid = optional_param('id', 0, PARAM_INT);

$baseurl = $CFG->wwwroot . "/local/assessfreq/dashboard_quiz.php";

// Calls require_login and performs permissions checks for admin pages.
admin_externalpage_setup('local_assessfreq_quiz', '', null, '',
    array('pagelayout' => 'admin'));

$title = get_string('dashboard:quiz', 'local_assessfreq');
$url = new moodle_url($baseurl);
$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->requires->js_call_amd('local_assessfreq/dashboard_quiz', 'init', array($context->id, $quizid));

$output = $PAGE->get_renderer('local_assessfreq');

echo $output->render_dashboard_quiz($baseurl);
