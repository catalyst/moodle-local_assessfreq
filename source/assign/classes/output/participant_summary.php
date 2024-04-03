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
 * @package   assessfreqsource_assign
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_assign\output;

use core\chart_pie;
use core\chart_series;

class participant_summary {

    /**
     * Generate the markup for the summary chart,
     * used in the quiz dashboard.
     *
     * @param array $allparticipantdata Participant data.
     * @return array With Generated chart object and chart data status.
     */
    public function get_participant_summary_chart(array $allparticipantdata) : array {
        global $OUTPUT;

        $participantdata = array_pop($allparticipantdata);
        $result = [];

        if (empty($participantdata)) {
            $result['hasdata'] = false;
            $result['chart'] = false;
        } else {
            $result['hasdata'] = true;

            $seriesdata = [
                $participantdata->notloggedin,
                $participantdata->loggedin,
                $participantdata->inprogress,
                $participantdata->finished,
            ];

            $labels = [
                get_string('participanttrend:notloggedin', 'assessfreqsource_assign'),
                get_string('participanttrend:loggedin', 'assessfreqsource_assign'),
                get_string('participanttrend:inprogress', 'assessfreqsource_assign'),
                get_string('participanttrend:finished', 'assessfreqsource_assign'),
            ];

            $colors = [
                get_config('assessfreqreport_activity_dashboard', 'notloggedincolor'),
                get_config('assessfreqreport_activity_dashboard', 'loggedincolor'),
                get_config('assessfreqreport_activity_dashboard', 'inprogresscolor'),
                get_config('assessfreqreport_activity_dashboard', 'finishedcolor'),
            ];

            // Create chart object.
            $chart = new chart_pie();
            $chart->set_doughnut(true);
            $participants = new chart_series(get_string('participanttrend:participants', 'assessfreqsource_assign'), $seriesdata);
            $participants->set_colors($colors);
            $chart->add_series($participants);
            $chart->set_labels($labels);

            $result['chart'] = $OUTPUT->render($chart);
        }

        return $result;
    }
}
