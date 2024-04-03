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
 * Renderer.
 *
 * @package   assessfreqreport_activities_in_progress
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_activities_in_progress\output;

use context_system;
use core\chart_bar;
use core\chart_pie;
use core\chart_series;
use html_writer;
use local_assessfreq\source_base;
use local_assessfreq\utils;
use paging_bar;
use plugin_renderer_base;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/local/assessfreq/lib.php');

class renderer extends plugin_renderer_base {

    public function render_report($data) {

        // In progress counts.
        $contents = '';
        foreach ($data['inprogress'] as $count) {
            $contents .= html_writer::div($count);
        }

        $progresssummarycontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('inprogress:head', 'assessfreqreport_activities_in_progress'),
                'contents' => $contents
            ]
        );

        // Upcomming activities starting.
        $labels = [];
        $seriestitle = get_string('upcommingchart:activities', 'assessfreqreport_activities_in_progress');
        $participantseries = get_string('upcommingchart:participants', 'assessfreqreport_activities_in_progress');

        $seriesdata = [];
        $participantseriesdata = [];

        foreach ($data['upcomming'] as $sourceupcoming) {
            foreach ($sourceupcoming['upcomming'] as $timestamp => $upcomming) {
                $count = 0;
                $participantcount = 0;

                foreach ($upcomming as $activity) {
                    $count++;
                    $participantcount += $activity->participants;
                }

                foreach ($sourceupcoming['inprogress'] as $inprogress) {
                    if ($inprogress->timestampopen >= $timestamp && $inprogress->timestampopen < $timestamp + HOURSECS) {
                        $count++;
                        $participantcount += $inprogress->participants;
                    }
                }

                if (!isset($seriesdata[$timestamp])) {
                    $seriesdata[$timestamp] = 0;
                }
                $seriesdata[$timestamp] += $count;
                if (!isset($participantseriesdata[$timestamp])) {
                    $participantseriesdata[$timestamp] = 0;
                }
                $participantseriesdata[$timestamp] += $participantcount;
                $labels[$timestamp] = userdate(
                    $timestamp + HOURSECS,
                    get_string('upcommingchart:inprogressdatetime', 'assessfreqreport_activities_in_progress')
                );
            }
        }
        $seriesdata = array_values($seriesdata);
        $participantseriesdata = array_values($participantseriesdata);
        $labels = array_values($labels);

        $series = new chart_series($seriestitle, $seriesdata);
        $participantseries = new chart_series($participantseries, $participantseriesdata);

        $chart = new chart_bar();
        $chart->add_series($series);
        $chart->add_series($participantseries);
        $chart->set_labels($labels);

        $upcomingcontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('upcommingchart:head', 'assessfreqreport_activities_in_progress'),
                'contents' => $this->render($chart)
            ]
        );

        // Participant summary container.
        $seriesdata = [
            'notloggedin' => 0,
            'loggedin' => 0,
            'inprogress' => 0,
            'finished' => 0,
        ];

        foreach ($data['participants'] as $sourceparticipants) {
            foreach ($sourceparticipants as $status => $value) {
                $seriesdata[$status] = $value + ($seriesdata[$status] ?? 0);
            }
        }

        $seriesdata = array_values($seriesdata);

        $labels = [
            get_string('summarychart:notloggedin', 'assessfreqreport_activities_in_progress'),
            get_string('summarychart:loggedin', 'assessfreqreport_activities_in_progress'),
            get_string('summarychart:inprogress', 'assessfreqreport_activities_in_progress'),
            get_string('summarychart:finished', 'assessfreqreport_activities_in_progress'),
        ];

        $colors = [
            get_config('assessfreqreport_activities_in_progress', 'notloggedincolor'),
            get_config('assessfreqreport_activities_in_progress', 'loggedincolor'),
            get_config('assessfreqreport_activities_in_progress', 'inprogresscolor'),
            get_config('assessfreqreport_activities_in_progress', 'finishedcolor'),
        ];

        $chart = new chart_pie();
        $chart->set_doughnut(true);
        $participants = new chart_series(
            get_string('summarychart:participants', 'assessfreqreport_activities_in_progress'),
            $seriesdata
        );
        $participants->set_colors($colors);
        $chart->add_series($participants);
        $chart->set_labels($labels);

        $summarycontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('summarychart:head', 'assessfreqreport_activities_in_progress'),
                'contents' => $this->render($chart)
            ]
        );

        // Activies in progress container.
        $progresscontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('inprogresstable:head', 'assessfreqreport_activities_in_progress'),
                'contents' => 'No data'
            ]
        );

        $preferencerows = get_user_preferences('assessfreqreport_activities_in_progress_table_rows_preference', 20);
        $rows = [
            20 => 'rows20',
            50 => 'rows50',
            100 => 'rows100',
        ];

        $preferencehoursahead = (int)get_user_preferences('assessfreqreport_activities_in_progress_hoursahead_preference', 8);
        $preferencehoursbehind = (int)get_user_preferences('assessfreqreport_activities_in_progress_hoursbehind_preference', 1);

        $hours = [
            0 => 'hours0',
            1 => 'hours1',
            4 => 'hours4',
            8 => 'hours8',
        ];

        return $this->render_from_template(
            'assessfreqreport_activities_in_progress/activities-in-progress',
            [
                'filters' => [
                    'modules' => get_modules(
                        json_decode(
                            get_user_preferences(
                                'assessfreqreport_activities_in_progress_modules_preference',
                                '["all"]'
                            ),
                            true
                        )
                    ),
                    'hoursahead' => [$hours[$preferencehoursahead] => 'true'],
                    'hoursbehind' => [$hours[$preferencehoursbehind] => 'true'],
                ],
                'progresssummary' => $progresssummarycontainer,
                'upcoming' => $upcomingcontainer,
                'summary' => $summarycontainer,
                'progress' => $progresscontainer,
                'table' => [
                    'id' => 'assessfreqreport-activities-in-progress',
                    'name' => get_string('inprogresstable:head', 'assessfreqreport_activities_in_progress'),
                    'rows' => [$rows[$preferencerows] => 'true'],
                ]
            ]
        );
    }

    /**
     * Renders the activities in progress "table" on the dashboard screen.
     * We update the table via ajax.
     * The table isn't a real table it's a collection of divs.
     *
     * @param string $search The search string for the table.
     * @param int $page The page number of results.
     * @param string $sorton The value to sort by.
     * @param string $direction The direction to sort.
     * @param int $hoursahead Amount of time in hours to look ahead for activity starting.
     * @param int $hoursbehind Amount of time in hours to look behind for activity starting.
     * @return string $output HTML for the table.
     */
    public function render_activities_inprogress_table(
        string $search,
        int $page,
        string $sorton,
        string $direction
    ): string {
        $now = time();
        $sources = get_sources();
        $inprogress = [];
        /* @var $source source_base */
        foreach ($sources as $source) {
            if (method_exists($source, 'get_inprogress_data')) {
                $inprogress[] = $source->get_inprogress_data($now);
            }
        }
        $pagesize = get_user_preferences('assessfreqreport_activities_in_progress_table_rows_preference', 20);

        $activities = [];
        foreach ($inprogress as $activity) {
            array_push($activities, ...$activity['inprogress']);
            $upcommingactivities = $activity['upcomming'];
            $finishedactivities = $activity['finished'];

            foreach ($upcommingactivities as $upcommingactivity) {
                foreach ($upcommingactivity as $key => $upcomming) {
                    $activities[$key] = $upcomming;
                }
            }

            foreach ($finishedactivities as $finishedactivity) {
                foreach ($finishedactivity as $key => $finished) {
                    $activities[$key] = $finished;
                }
            }
        }

        [$filtered, $totalrows] = $this->filter($activities, $search, $page, $pagesize);
        $sortedactivities = utils::sort($filtered, $sorton, $direction);

        $pagingbar = new paging_bar($totalrows, $page, $pagesize, '/');
        $pagingoutput = $this->render($pagingbar);

        $context = [
            'activities' => array_values($sortedactivities),
            'pagingbar' => $pagingoutput,
        ];

        return $this->render_from_template('assessfreqreport_activities_in_progress/activities-in-progress-table', $context);
    }


    /**
     * Given an array of activities, filter based on a provided search string and apply pagination.
     *
     * @param array $activities Array of activities to search.
     * @param string $search The string to search by.
     * @param int $page The page number of results.
     * @param int $pagesize The page size for results.
     * @return array $result Array containing list of filtered activities and total of how many activities matched the filter.
     */
    private function filter(array $activities, string $search, int $page, int $pagesize): array {
        $filtered = [];
        $searchfields = ['name', 'coursefullname'];
        $offset = $page * $pagesize;
        $offsetcount = 0;
        $recordcount = 0;

        foreach ($activities as $id => $activity) {
            $searchcount = 0;
            if ($search != '') {
                $searchcount = -1;
                foreach ($searchfields as $searchfield) {
                    if (stripos($activity->{$searchfield}, $search) !== false) {
                        $searchcount++;
                    }
                }
            }

            if ($searchcount > -1 && $offsetcount >= $offset && $recordcount < $pagesize) {
                $filtered[$id] = $activity;
            }

            if ($searchcount > -1 && $offsetcount >= $offset) {
                $recordcount++;
            }

            if ($searchcount > -1) {
                $offsetcount++;
            }
        }

        return [$filtered, $offsetcount];
    }
}
