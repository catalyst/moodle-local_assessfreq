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
 * Plugin strings are defined here.
 *
 * @package     local_assessfreq
 * @category    string
 * @copyright   2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Assessment Frequency';
$string['title'] = 'Assessment Frequency';

$string['activity'] = 'Activity';
$string['actions'] = 'Actions';
$string['assessbyactivity'] = 'Assessments by activity';
$string['assessbymonth'] = 'Assessments due by month';
$string['assessbymonthstudents'] = 'Students with assessments due by month';
$string['assessheatmap'] = 'Assessment heatmap for year:';
$string['assessoverview'] = 'Assessment overviews for year:';
$string['cachedef_eventsdueactivity'] = 'Events due by activity cache';
$string['cachedef_eventsduemonth'] = 'Events due by month cache';
$string['cachedef_eventusers'] = 'Users for month cache';
$string['cachedef_monthlyuser'] = 'User events due by month cache';
$string['cachedef_courseevents'] = 'Assessment frequency course event cache';
$string['cachedef_siteevents'] = 'Assessment frequency site event cache';
$string['cachedef_userevents'] = 'Assessment frequency user event cache';
$string['cachedef_usereventsall'] = 'Assessment frequency all user event cache';
$string['cachedef_yearevents'] = 'Years that have events';
$string['clearhistory'] = 'Clear history';
$string['closeapply'] = 'Close and apply';
$string['confirmreprocess'] = 'Delete ALL history and reprocess?';
$string['course'] = 'Course';
$string['dashboard:assessment'] = 'Assessment dashboard';
$string['dashboard:quiz'] = 'Quiz dashboard';
$string['duedate'] = 'Due date';
$string['eventeventprocessed'] = 'event_processed';
$string['eventeven_processed_desc'] = 'local assessfreq task event processing';
$string['entercourse'] = 'Enter course name';
$string['findcourse'] = 'Find course';
$string['finished'] = 'Finished';
$string['inprogress'] = 'In progress';
$string['loading'] = 'Loading';
$string['loadingquiz'] = 'Loading quizzes';
$string['loadingquiztitle'] = 'Loading quiz';
$string['loggedin'] = 'Logged in';
$string['na'] = 'N/A';
$string['minuteone'] = '1 Minute';
$string['minutetwo'] = '2 Minutes';
$string['minutefive'] = '5 Minutes';
$string['minuteten'] = '10 Minutes';
$string['nocourse'] = 'No course selected';
$string['noquiz'] = 'No quiz selected...';
$string['noquizselected'] = 'No quiz selected. Select quiz or cancel';
$string['notloggedin'] = 'Not logged in';
$string['numberassessments'] = 'By number of assessments';
$string['numberevents'] = 'Event Count';
$string['numberstudents'] = 'By number of students with assessments';
$string['participantsummary'] = 'Participant summary';
$string['participanttrend'] = 'Participant trend';
$string['period'] = 'Period';
$string['privacy:metadata:local_assessfreq'] = 'Data relating users for the local assessfreq plugin';
$string['privacy:metadata:local_assessfreq_user'] = 'Data relating users with assessment events';
$string['privacy:metadata:local_assessfreq_user:id'] = 'Record ID';
$string['privacy:metadata:local_assessfreq_user:userid'] = 'The ID of the user that is effected by the assessment event';
$string['privacy:metadata:local_assessfreq_user:eventid'] = 'The ID that relates to the assessment event';
$string['privacy:metadata:local_assessfreq_conf_user'] = 'Data relating users with assessment conflicts';
$string['privacy:metadata:local_assessfreq_conf_user:id'] = 'Record ID';
$string['privacy:metadata:local_assessfreq_conf_user:userid'] = 'The ID of the user that is effected by the assessment conflict';
$string['privacy:metadata:local_assessfreq_conf_user:conflictid'] = 'The ID that relates to the assessment conflict';
$string['pluginsettings'] = 'Plugin settings';
$string['quiz'] = 'Quiz';
$string['quizdetails'] = 'Quiz details';
$string['quiztparticipantsoverride'] = 'Participants with an override:';
$string['quiztquestionnumber'] = 'Questions in quiz:';
$string['quizquestiontypes'] = 'Question types in quiz:';
$string['quiztimeclose'] = 'Close time';
$string['quiztimeearlyopen'] = 'First participant starts:';
$string['quiztimelateclose'] = 'Last participant finishes:';
$string['quiztimelimit'] = 'Time limit';
$string['quiztimeopen'] = 'Open time';
$string['quizparticipants'] = 'Participant count:';
$string['reports'] = 'Assessment reports';
$string['reprocessall'] = 'Reprocess all events';
$string['reprocessall_desc'] = 'This will delete ALL existing event records from the database and start a process to reprocess all events. This will happen in the background.';
$string['schedule'] = 'Daily schedule';
$string['selectassessment'] = 'Select assessment type';
$string['selectcourse'] = 'Select course first';
$string['selectquiz'] = 'Select quiz';
$string['searchquiz'] = 'Search for quiz';
$string['searchquizform'] = 'Search and select the quiz to display on the dashboard';
$string['selectmetric'] = 'Select metric';
$string['selectyear'] = 'Select year';
$string['settings:heat1'] = 'First heat color';
$string['settings:heat1_desc'] = 'Select color for the first level of the frequency heatmap';
$string['settings:heat1'] = 'First heat color';
$string['settings:heat1_desc'] = 'Select color for the first level of the frequency heatmap';
$string['settings:heat2'] = 'Second heat color';
$string['settings:heat2_desc'] = 'Select color for the second level of the frequency heatmap';
$string['settings:heat3'] = 'Third heat color';
$string['settings:heat3_desc'] = 'Select color for the third level of the frequency heatmap';
$string['settings:heat4'] = 'Fourth heat color';
$string['settings:heat4_desc'] = 'Select color for the fourth level of the frequency heatmap';
$string['settings:heat5'] = 'Fifth heat color';
$string['settings:heat5_desc'] = 'Select color for the fifth level of the frequency heatmap';
$string['settings:heat6'] = 'Sixth heat color';
$string['settings:heat6_desc'] = 'Select color for the sixth level of the frequency heatmap';
$string['settings:heatheading'] = 'Heatmap colors';
$string['settings:heatheading_desc'] = 'These settings allow you to configure the colors used in the heatmap';
$string['settings:hiddencourses'] = 'Include hidden courses';
$string['settings:hiddencourses_desc'] = 'Included hidden courses in the heatmap calculations';
$string['settings:modules'] = 'Enabled modules';
$string['settings:modules_desc'] = 'Select the modules that you want to appear in the heatmap calculations';
$string['settings:moduleheading'] = 'Modules and courses';
$string['settings:moduleheading_desc'] = 'These settings control how modules and courses are used in processing';
$string['settings:disabledmodules'] = 'Include disabled modules';
$string['settings:disabledmodules_desc'] = 'Include modules that have been disabled in calculations';
$string['status'] = 'Status';
$string['students'] = 'Students';
$string['studenttable'] = 'Student attempt status';
$string['systemdisabled'] = ' (module disabled)';
$string['task:dataprocess'] = 'Data collection task';
$string['task:quiztracking'] = 'Quiz tracking task';
$string['time'] = 'Time';
$string['timelimit'] = 'Time limit (minutes)';
$string['title'] = 'Title';
$string['toggleoverview'] = 'Toggle overview graphs';
$string['trenddatetime'] = '%H:%M, %d-%m-%y';
$string['userattempt'] = 'View user attempt';
$string['useroverride'] = 'Add user override';
$string['userprofile'] = 'View user profile';
$string['url'] = 'URL';
$string['zoom'] = 'Zoom in';
$string['jan'] = 'January';
$string['feb'] = 'February';
$string['mar'] = 'March';
$string['apr'] = 'April';
$string['may'] = 'May';
$string['jun'] = 'June';
$string['jul'] = 'July';
$string['aug'] = 'August';
$string['sep'] = 'September';
$string['oct'] = 'October';
$string['nov'] = 'November';
$string['dec'] = 'December';
