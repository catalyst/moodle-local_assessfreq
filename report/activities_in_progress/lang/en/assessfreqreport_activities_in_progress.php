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
 * @package   assessfreqreport_activities_in_progress
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Report - Activities in Progress';

$string['tab:name'] = 'Activities in Progress';

$string['activities_in_progress:view'] = 'Ability to view the activities in progress report.';

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
$string['settings:graphsheading'] = 'Graph settings';
$string['settings:graphsheading_desc'] = 'Specify the graph settings for each graph report';

$string['filter:selectassessment'] = 'Select assessment type';
$string['filter:closeapply'] = 'Close and apply';
$string['filter:header'] = 'Filters';
$string['filter:submit'] = 'Filter';
$string['filter:hours0'] = 'Now';
$string['filter:hours1'] = '1 Hour';
$string['filter:hours4'] = '4 Hours';
$string['filter:hours8'] = '8 Hours';
$string['filter:hoursahead'] = 'Hours ahead';
$string['filter:hoursbehind'] = 'Hours behind';

$string['inprogress'] = 'In progress';
$string['inprogress:head'] = 'In progress';

$string['upcomingchart:head'] = 'Upcoming activities starting';
$string['upcomingchart:inprogressdatetime'] = '%H:00';
$string['upcomingchart:activities'] = 'Activities';
$string['upcomingchart:participants'] = 'Students';

$string['summarychart:head'] = 'Participant summary';
$string['summarychart:participants'] = 'Students';
$string['summarychart:notloggedin'] = 'Not logged in';
$string['summarychart:loggedin'] = 'Logged in';
$string['summarychart:inprogress'] = 'In progress';
$string['summarychart:finished'] = 'Finished';

$string['inprogresstable:head'] = 'Activies in progress';
$string['inprogresstable:activity'] = 'Activity';
$string['inprogresstable:course'] = 'Course';
$string['inprogresstable:timelimit'] = 'Time limit';
$string['inprogresstable:timeopen'] = 'Time open';
$string['inprogresstable:timeclose'] = 'Time close';
$string['inprogresstable:participants'] = 'Participants (Overrides)';
$string['inprogresstable:dashboard'] = 'Dashboard';

$string['report:usage_guidlines'] = '';
