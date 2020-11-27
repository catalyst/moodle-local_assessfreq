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
 * Renderable for participant summary card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderable for participant summary card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inprogress_participant_summary {

    /**
     * Generate the markup for the summary chart,
     * used in the quiz dashboard.
     *
     * @param int $quizid Quiz id to get chart data for.
     * @return array With Generated chart object and chart data status.
     */
    public function get_inprogress_participant_summary_chart(\stdClass $participants): \core\chart_pie {

        $seriesdata = array(
            $participants->notloggedin,
            $participants->loggedin,
            $participants->inprogress,
            $participants->finished
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
        $chart->set_legend_options(['display' => false]);

        return $chart;
    }
}
