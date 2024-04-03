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
 * Settings file.
 *
 * @package   assessfreqreport_activity_dashboard
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

// Chart settings.
$settings->add(new admin_setting_heading(
    'assessfreqreport_activity_dashboard/chartheading',
    get_string('settings:chartheading', 'assessfreqreport_activity_dashboard'),
    get_string('settings:chartheading_desc', 'assessfreqreport_activity_dashboard')
));

require_once($CFG->dirroot . '/local/assessfreq/settingslib.php');
$settings->add(new admin_setting_configint(
    'assessfreqreport_activity_dashboard/trendcount',
    get_string('settings:trendcount', 'assessfreqreport_activity_dashboard'),
    get_string('settings:trendcount_desc', 'assessfreqreport_activity_dashboard'),
    300
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activity_dashboard/notloggedincolor',
    get_string('settings:notloggedincolor', 'assessfreqreport_activity_dashboard'),
    get_string('settings:notloggedincolor_desc', 'assessfreqreport_activity_dashboard'),
    '#8C0010'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activity_dashboard/loggedincolor',
    get_string('settings:loggedincolor', 'assessfreqreport_activity_dashboard'),
    get_string('settings:loggedincolor_desc', 'assessfreqreport_activity_dashboard'),
    '#FA8900'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activity_dashboard/inprogresscolor',
    get_string('settings:inprogresscolor', 'assessfreqreport_activity_dashboard'),
    get_string('settings:inprogresscolor_desc', 'assessfreqreport_activity_dashboard'),
    '#875692'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activity_dashboard/finishedcolor',
    get_string('settings:finishedcolor', 'assessfreqreport_activity_dashboard'),
    get_string('settings:finishedcolor_desc', 'assessfreqreport_activity_dashboard'),
    '#1B8700'
));
