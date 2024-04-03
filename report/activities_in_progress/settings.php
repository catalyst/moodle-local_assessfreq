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
 * @package   assessfreqreport_activities_in_progress
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

// Graph settings.
$settings->add(new admin_setting_heading(
    'assessfreqreport_activities_in_progress/graphsheading',
    get_string('settings:graphsheading', 'assessfreqreport_activities_in_progress'),
    get_string('settings:graphsheading_desc', 'assessfreqreport_activities_in_progress')
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activities_in_progress/notloggedincolor',
    get_string('settings:notloggedincolor', 'assessfreqreport_activities_in_progress'),
    get_string('settings:notloggedincolor_desc', 'assessfreqreport_activities_in_progress'),
    '#8C0010'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activities_in_progress/loggedincolor',
    get_string('settings:loggedincolor', 'assessfreqreport_activities_in_progress'),
    get_string('settings:loggedincolor_desc', 'assessfreqreport_activities_in_progress'),
    '#FA8900'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activities_in_progress/inprogresscolor',
    get_string('settings:inprogresscolor', 'assessfreqreport_activities_in_progress'),
    get_string('settings:inprogresscolor_desc', 'assessfreqreport_activities_in_progress'),
    '#875692'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_activities_in_progress/finishedcolor',
    get_string('settings:finishedcolor', 'assessfreqreport_activities_in_progress'),
    get_string('settings:finishedcolor_desc', 'assessfreqreport_activities_in_progress'),
    '#1B8700'
));
