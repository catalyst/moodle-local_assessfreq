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
 * Settings page for local/assessfreq that also dynamically loads the settings for sources and reports.
 *
 * @package   local_assessfreq
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Assessment dashboard link.
$ADMIN->add('reports', new admin_externalpage(
    'local_assessfreq_report',
    get_string('pluginname', 'local_assessfreq'),
    $CFG->wwwroot . '/local/assessfreq/',
    'local/assessfreq:view'
));

$ADMIN->add('localplugins', new admin_category('local_assessfreq', get_string('settings:head', 'local_assessfreq')));

$reports = core_plugin_manager::instance()->get_plugins_of_type('assessfreqreport');
$sources = core_plugin_manager::instance()->get_plugins_of_type('assessfreqsource');

$settings = new admin_settingpage(
    'local_assessfreq_settings',
    get_string('settings:local_assessfreq', 'local_assessfreq')
);

// Include hidden courses.
$setting = new admin_setting_configcheckbox(
    'local_assessfreq/hiddencourses',
    get_string('settings:hiddencourses', 'local_assessfreq'),
    get_string('settings:hiddencourses_desc', 'local_assessfreq'),
    0
);
$settings->add($setting);

// Add the start month to calculate reports from for the year.
$options = [];
for ($m = 1; $m <= 12; $m++) {
    $dateobj = DateTime::createFromFormat('!m', $m);
    $options[$m] = $dateobj->format('F');
}

$settings->add(new admin_setting_configselect(
    'local_assessfreq/start_month',
    get_string('settings:start_month', 'local_assessfreq'),
    get_string('settings:start_month_desc', 'local_assessfreq'),
    '1',
    $options
));

// Add the enable checkboxes for reports and sources.
foreach ($sources as $source) {
    $enabled = new admin_setting_configcheckbox(
        'assessfreqsource_' . $source->name . '/enabled',
        get_string('settings:enablesource', 'local_assessfreq', $source->displayname),
        get_string('settings:enablesource_help', 'local_assessfreq'),
        1
    );
    $settings->add($enabled);
}

foreach ($reports as $report) {
    $enabled = new admin_setting_configcheckbox(
        'assessfreqreport_' . $report->name . '/enabled',
        get_string('settings:enablereport', 'local_assessfreq', $report->displayname),
        get_string('settings:enablereport_help', 'local_assessfreq'),
        1
    );
    $settings->add($enabled);
}

$ADMIN->add('local_assessfreq', $settings);

// Add the individual reports settings.
foreach ($reports as $report) {
    $report->load_settings($ADMIN, 'local_assessfreq', $hassiteconfig);
}

// Add the individual sources settings.
foreach ($sources as $source) {
    $source->load_settings($ADMIN, 'local_assessfreq', $hassiteconfig);
}
