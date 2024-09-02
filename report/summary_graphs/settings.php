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
 * Settings.
 *
 * @package   assessfreqreport_summary_graphs
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

// Graph type settings.
$settings->add(new admin_setting_heading(
    'assessfreqreport_summary_graphs/graphsheading',
    get_string('settings:graphsheading', 'assessfreqreport_summary_graphs'),
    get_string('settings:graphsheading_desc', 'assessfreqreport_summary_graphs')
));


$types = [
    'bar' => get_string('settings:graphs:bar', 'assessfreqreport_summary_graphs'),
    'line' => get_string('settings:graphs:line', 'assessfreqreport_summary_graphs'),
    'pie' => get_string('settings:graphs:pie', 'assessfreqreport_summary_graphs'),
];

// By month graph type.
$setting = new admin_setting_configselect(
    'assessfreqreport_summary_graphs/by_month_type',
    get_string('settings:by_month_type', 'assessfreqreport_summary_graphs'),
    '',
    'bar',
    $types
);
$settings->add($setting);

// By activity graph type.
$setting = new admin_setting_configselect(
    'assessfreqreport_summary_graphs/by_activity_type',
    get_string('settings:by_activity_type', 'assessfreqreport_summary_graphs'),
    '',
    'bar',
    $types
);
$settings->add($setting);

// Assessments due graph type.
$setting = new admin_setting_configselect(
    'assessfreqreport_summary_graphs/assessments_due',
    get_string('settings:assessments_due_type', 'assessfreqreport_summary_graphs'),
    '',
    'bar',
    $types
);
$settings->add($setting);

// Course level year filter.
$settings->add(new admin_setting_configcheckbox(
    'assessfreqreport_summary_graphs/courselevelyearfilter',
    get_string('settings:courselevelyearfilter', 'assessfreqreport_summary_graphs'),
    get_string('settings:courselevelyearfilter_desc', 'assessfreqreport_summary_graphs'),
    1
));