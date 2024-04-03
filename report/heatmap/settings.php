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
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

// Heat settings.
$settings->add(new admin_setting_heading(
    'assessfreqreport_heatmap/heatheading',
    get_string('settings:heatheading', 'assessfreqreport_heatmap'),
    get_string('settings:heatheading_desc', 'assessfreqreport_heatmap')
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_heatmap/heat1',
    get_string('settings:heat1', 'assessfreqreport_heatmap'),
    get_string('settings:heat1_desc', 'assessfreqreport_heatmap'),
    '#FDF9CD'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_heatmap/heat2',
    get_string('settings:heat2', 'assessfreqreport_heatmap'),
    get_string('settings:heat2_desc', 'assessfreqreport_heatmap'),
    '#A2DAB5'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_heatmap/heat3',
    get_string('settings:heat3', 'assessfreqreport_heatmap'),
    get_string('settings:heat3_desc', 'assessfreqreport_heatmap'),
    '#41B7C5'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_heatmap/heat4',
    get_string('settings:heat4', 'assessfreqreport_heatmap'),
    get_string('settings:heat4_desc', 'assessfreqreport_heatmap'),
    '#4D7FB9'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_heatmap/heat5',
    get_string('settings:heat5', 'assessfreqreport_heatmap'),
    get_string('settings:heat5_desc', 'assessfreqreport_heatmap'),
    '#283B94'
));

$settings->add(new admin_setting_configcolourpicker(
    'assessfreqreport_heatmap/heat6',
    get_string('settings:heat6', 'assessfreqreport_heatmap'),
    get_string('settings:heat6_desc', 'assessfreqreport_heatmap'),
    '#8C0010'
));
