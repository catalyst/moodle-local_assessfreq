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
 * Plugin administration pages are defined here.
 *
 * @package     local_assessfreq
 * @copyright   2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

// Site wide plugin admin settings.
$sitesettings = new admin_settingpage('local_assessfreq', get_string('pluginsettings', 'local_assessfreq'));

// Settings page historic data processing.
$historysettings = new admin_externalpage(
    'local_assessfreq_history',
    get_string('clearhistory', 'local_assessfreq'),
    new moodle_url('/local/assessfreq/history.php'));

// TODO: Add which activities we should include in the reports
// TODO: add setting to filter visible courses or not
// TODO: add setting to filter visibile activities or not.

// Build the admin menu tree.
$ADMIN->add('localplugins', new admin_category('local_assessfreq_settings',
    get_string('pluginname', 'local_assessfreq')));
$ADMIN->add('local_assessfreq_settings', $sitesettings);
$ADMIN->add('local_assessfreq_settings', $historysettings);

// Report link.
$ADMIN->add('reports', new admin_externalpage('local_assessfreq_report',
    get_string('pluginname', 'local_assessfreq'), "$CFG->wwwroot/local/assessfreq/report.php"));
