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
 * Renderable for all participant summary card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

use local_assessfreq\quiz;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderable for all participant summary card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class all_participants_inprogress {

    /**
     * Generate the markup for the summary chart,
     * used in the in progress quizzes dashboard.
     *
     * @param int $now Timestamp to get chart data for.
     * @return array With Generated chart object and chart data status.
     */
    public function get_all_participants_inprogress_chart(int $now, int $hoursahead = 0, int $hoursbehind = 0): array {

        // Get quizzes for the supplied timestamp.
        $quiz = new quiz($hoursahead, $hoursbehind);
        $quizzes = $quiz->get_quiz_summaries($now);

        $inprogressquizzes = $quizzes['inprogress'];
        $upcommingquizzes = $quizzes['upcomming'];
        $finishedquizzes = $quizzes['finished'];

        foreach ($upcommingquizzes as $key=>$upcommingquiz) {
            foreach ($upcommingquiz as $keyupcomming=>$upcomming) {
                $inprogressquizzes[$keyupcomming] = $upcomming;
            }
        }

        foreach ($finishedquizzes as $key=>$finishedquiz) {
            foreach ($finishedquiz as $keyfinished=>$finished) {
                $inprogressquizzes[$keyfinished] = $finished;
            }
        }

        $notloggedin = 0;
        $loggedin = 0;
        $inprogress = 0;
        $finished = 0;

        foreach ($inprogressquizzes as $quizobj) {
            if (!empty($quizobj->tracking)) {
                $notloggedin += $quizobj->tracking->notloggedin;
                $loggedin += $quizobj->tracking->loggedin;
                $inprogress += $quizobj->tracking->inprogress;
                $finished += $quizobj->tracking->finished;
            }

        }

        $result = array();

        if (($notloggedin == 0) && ($loggedin == 0) && ($inprogress == 0) && ($finished == 0)) {
            $result['hasdata'] = false;
            $result['chart'] = false;
        } else {
            $result['hasdata'] = true;

            $seriesdata = array(
                $notloggedin,
                $loggedin,
                $inprogress,
                $finished
            );

            $labels = array(
                get_string('notloggedin', 'local_assessfreq'),
                get_string('loggedin', 'local_assessfreq'),
                get_string('inprogress', 'local_assessfreq'),
                get_string('finished', 'local_assessfreq')
            );

            $colors = array(
                get_config('local_assessfreq', 'notloggedincolor'),
                get_config('local_assessfreq', 'loggedincolor'),
                get_config('local_assessfreq', 'inprogresscolor'),
                get_config('local_assessfreq', 'finishedcolor')
            );

            // Create chart object.
            $chart = new \core\chart_pie();
            $chart->set_doughnut(true);
            $participants = new \core\chart_series(get_string('participants', 'local_assessfreq'), $seriesdata);
            $participants->set_colors($colors);
            $chart->add_series($participants);
            $chart->set_labels($labels);

            $result['chart'] = $chart;
        }

        return $result;
    }
}
