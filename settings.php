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

// Module settings.
$sitesettings->add(new admin_setting_heading('local_assessfreq/moduleheading',
    get_string('settings:moduleheading', 'local_assessfreq'),
    get_string('settings:moduleheading_desc', 'local_assessfreq')));

$frequency = new \local_assessfreq\frequency();
$modules = $frequency->get_modules();
$enabledmodules = $frequency->get_enabled_modules();
$modarray = array();

foreach ($modules as $module) {
    if ($enabledmodules[$module] == 1) {
        $modarray[$module] = get_string('modulename', $module);
    } else {
        $modarray[$module] = get_string('modulename', $module) . get_string('systemdisabled', 'local_assessfreq');
    }
}

$sitesettings->add(new admin_setting_configmulticheckbox('local_assessfreq/modules',
    get_string('settings:modules', 'local_assessfreq'),
    get_string('settings:modules_desc', 'local_assessfreq'),
    $modules,
    $modarray));

// Include disabled modules.
$sitesettings->add(new admin_setting_configcheckbox('local_assessfreq/disabledmodules',
    get_string('settings:disabledmodules', 'local_assessfreq'),
    get_string('settings:disabledmodules_desc', 'local_assessfreq'),
    1));

// Include hidden courses.
$sitesettings->add(new admin_setting_configcheckbox('local_assessfreq/hiddencourses',
    get_string('settings:hiddencourses', 'local_assessfreq'),
    get_string('settings:hiddencourses_desc', 'local_assessfreq'),
    0));

// Heat settings.
$sitesettings->add(new admin_setting_heading('local_assessfreq/heatheading',
    get_string('settings:heatheading', 'local_assessfreq'),
    get_string('settings:heatheading_desc', 'local_assessfreq')));

$sitesettings->add(new admin_setting_configcolourpicker('local_assessfreq/heat1',
    get_string('settings:heat1', 'local_assessfreq'),
    get_string('settings:heat1_desc', 'local_assessfreq'), '#FDF9CD'));

$sitesettings->add(new admin_setting_configcolourpicker('local_assessfreq/heat2',
    get_string('settings:heat2', 'local_assessfreq'),
    get_string('settings:heat2_desc', 'local_assessfreq'), '#A2DAB5'));

$sitesettings->add(new admin_setting_configcolourpicker('local_assessfreq/heat3',
    get_string('settings:heat3', 'local_assessfreq'),
    get_string('settings:heat3_desc', 'local_assessfreq'), '#41B7C5'));

$sitesettings->add(new admin_setting_configcolourpicker('local_assessfreq/heat4',
    get_string('settings:heat4', 'local_assessfreq'),
    get_string('settings:heat4_desc', 'local_assessfreq'), '#4D7FB9'));

$sitesettings->add(new admin_setting_configcolourpicker('local_assessfreq/heat5',
    get_string('settings:heat5', 'local_assessfreq'),
    get_string('settings:heat5_desc', 'local_assessfreq'), '#283B94'));

$sitesettings->add(new admin_setting_configcolourpicker('local_assessfreq/heat6',
    get_string('settings:heat6', 'local_assessfreq'),
    get_string('settings:heat6_desc', 'local_assessfreq'), '#8C0010'));

// Build the admin menu tree.
$ADMIN->add('localplugins', new admin_category('local_assessfreq_settings',
    get_string('pluginname', 'local_assessfreq')));
$ADMIN->add('local_assessfreq_settings', $sitesettings);
$ADMIN->add('local_assessfreq_settings', $historysettings);

// Report link.
$ADMIN->add('reports', new admin_externalpage('local_assessfreq_report',
    get_string('pluginname', 'local_assessfreq'), "$CFG->wwwroot/local/assessfreq/report.php"));
