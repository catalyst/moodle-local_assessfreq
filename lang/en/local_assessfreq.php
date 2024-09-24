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
 * @package   local_assessfreq
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'Assessment Frequency Report';

$string['privacy:metadata'] = 'The assessment frequency reports only display data';

$string['assessfreq:view'] = 'Ability to load the inital view. Report subplugins will also need to be allowed.';

$string['task:dataprocess'] = 'Data collection task';
$string['task:quiztracking'] = 'Quiz tracking task';

$string['courseselect'] = 'Select course...';
$string['noreports'] = 'No reports have been configured for you.
If you believe this is an error please contact your site administrator.';

$string['history:confirmreprocess'] = 'Delete ALL history and reprocess?';
$string['history:reprocessall'] = 'Reprocess all events';
$string['history:reprocessall_desc'] = 'This will delete ALL existing event records from the database and start a process to reprocess all events. This will happen in the background.';

$string['settings:clearhistory'] = 'Assessment Frequency Clear History';
$string['settings:head'] = 'Assessment Frequency Reports';
$string['settings:local_assessfreq'] = 'Global Settings';
$string['settings:start_month'] = 'Start month';
$string['settings:start_month_desc'] = 'Specify the month that the heatmap year should start from.';
$string['settings:hiddencourses'] = 'Include hidden courses';
$string['settings:hiddencourses_desc'] = 'Included hidden courses in the reports';
$string['settings:enablesource'] = 'Enable: {$a}';
$string['settings:enablesource_help'] = 'Check this control to allow the source to be used for the dashboard.';
$string['settings:enablereport'] = 'Enable: {$a}';
$string['settings:enablereport_help'] = 'Check this control to allow the report to be used for the dashboard.';

$string['filter:entersearch'] = 'Enter search';
$string['filter:reset'] = 'Reset';
$string['filter:showrows'] = 'Show rows';
$string['filter:rows20'] = '20 rows';
$string['filter:rows50'] = '50 rows';
$string['filter:rows100'] = '100 rows';

$string['modal:useroverride'] = 'User override';
