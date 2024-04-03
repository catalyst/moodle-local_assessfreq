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
 * Lang file.
 *
 * @package   assessfreqreport_activity_dashboard
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Report - Activity Dashboard';

$string['tab:name'] = 'Activity Dashboard';

$string['activity_dashboard:view'] = 'Ability to view the activity dashboard report.';

$string['searchactivity'] = 'Search for activity';

$string['settings:chartheading'] = 'Chart settings';
$string['settings:chartheading_desc'] = 'These settings allow you to configure the the settings used in the charts and graphs';
$string['settings:notloggedincolor'] = 'Not logged in color';
$string['settings:notloggedincolor_desc'] = 'Select color to display for not logged in users in charts';
$string['settings:loggedincolor'] = 'Logged in color';
$string['settings:loggedincolor_desc'] = 'Select color to display for logged in users in charts';
$string['settings:inprogresscolor'] = 'In progress color';
$string['settings:inprogresscolor_desc'] = 'Select color to display for in progress users in charts';
$string['settings:finishedcolor'] = 'Finished color';
$string['settings:finishedcolor_desc'] = 'Select color to display for finished users in charts';
$string['settings:trendcount'] = 'Trend chart limit';
$string['settings:trendcount_desc'] = 'The trend data is run every minute and can contain a lot of data.
For example an assessment running for 5 days can have 7200 points that can be mapped which can overwhelm the chart.
This setting specifies the number of points that will be evenly plotted on the graph';

$string['form:activity'] = 'Activity';
$string['form:entercourse'] = 'Enter course name';
$string['form:entersearch'] = 'Enter search text';
$string['form:loadingactivity'] = 'Loading activity';
$string['form:nocourse'] = 'No course';
$string['form:searchactivityform'] = 'Search and select the activity to display on the dashboard';
$string['form:selectactivity'] = 'Select activity';
$string['form:selectcourse'] = 'Select course';

$string['modal:loading'] = 'Loading';
$string['modal:loadingactivity'] = 'Loading activities';
$string['modal:noactivityselected'] = 'No activity selected';
$string['modal:searchactivity'] = 'Search for activity';
$string['modal:selectcourse'] = 'Select course';
