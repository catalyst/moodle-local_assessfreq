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

use local_assessfreq\quiz;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderable for participant summary card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participant_summary {

    /**
     * Generate the markup for the summary chart,
     * used in the quiz dashboard.
     *
     * @param int $quizid Quiz id to get chart data for.
     * @return \core\chart_base $chart Generated chart object.
     */
    public function get_participant_summary_chart(int $quizid): \core\chart_base {

        $quizdata = new quiz();
        $allparticipantdata = $quizdata->get_quiz_tracking($quizid);
        $participantdata = array_pop($allparticipantdata);

        error_log(print_r($participantdata, true));

        $seriesdata = array(
            $participantdata->notloggedin,
            $participantdata->loggedin,
            $participantdata->inprogress,
            $participantdata->finished
        );

        $labels = array(
            get_string('notloggedin', 'local_assessfreq'),
            get_string('loggedin', 'local_assessfreq'),
            get_string('inprogress', 'local_assessfreq'),
            get_string('finished', 'local_assessfreq')
        );

        $charttitle = get_string('participantsummary', 'local_assessfreq');

        // Create chart object.
        $participants = new \core\chart_series($charttitle, $seriesdata);

        $chart = new \core\chart_pie();
        $chart->set_doughnut(true);
        $chart->add_series($participants);
        $chart->set_labels($labels);

        return $chart;
    }
}
