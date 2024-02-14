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
 * Plugin strings are defined here.
 *
 * @package     local_assessfreq
 * @category    string
 * @copyright   2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

defined('MOODLE_INTERNAL') || die();

admin_externalpage_setup('local_assessfreq_history');

$action = optional_param('action', null, PARAM_ALPHA);

// Build the page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('clearhistory', 'local_assessfreq'));

// Page content. (This feels like the lazy way to do things).
$url = new \moodle_url('/local/assessfreq/history.php', ['action' => 'deleteall']);

if ($action === null) {
    echo $OUTPUT->box_start();
    echo $OUTPUT->container(get_string('reprocessall_desc', 'local_assessfreq'));
    echo $OUTPUT->single_button($url, get_string('reprocessall', 'local_assessfreq'), 'get');
    echo $OUTPUT->box_end();
} else if ($action == 'deleteall') {
    $actionurl = new moodle_url('/local/assessfreq/history.php', ['action' => 'confirmed']);
    $cancelurl = new moodle_url('/local/assessfreq/history.php');
    echo $OUTPUT->confirm(
        get_string('confirmreprocess', 'local_assessfreq'),
        new single_button($actionurl, get_string('continue'), 'post', true),
        new single_button($cancelurl, get_string('cancel'), 'get')
    );
} else if ($action == 'confirmed') {
    // Create an adhoc task that will process all historical event data.
    $task = new \local_assessfreq\task\history_process();
    \core\task\manager::queue_adhoc_task($task, true);
    echo $OUTPUT->box_start();
    echo $OUTPUT->container(get_string('reprocessall_desc', 'local_assessfreq'));
    echo $OUTPUT->single_button($url, get_string('reprocessall', 'local_assessfreq'), 'get');
    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
