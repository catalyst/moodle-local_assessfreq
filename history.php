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

// Build the page output.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('clearhistory', 'local_assessfreq'));
echo $OUTPUT->box_start('search-areas-actions');
echo $OUTPUT->single_button(admin_searcharea_action_url('deleteall'), get_string('searchdeleteindex', 'admin'), 'get', $options);
echo $OUTPUT->box_end();
echo $OUTPUT->footer();