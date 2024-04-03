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
 * Renderable for participant trend card.
 *
 * @package   assessfreqsource_assign
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_assign\output;

use core\chart_line;
use core\chart_series;

class participant_trend {
    /**
     * Generate the markup for the trend chart,
     * used in the quiz dashboard.
     *
     * @param array $allparticipantdata Participant data.
     * @return array With Generated chart object and chart data status.
     */
    public function get_participant_trend_chart(array $allparticipantdata): array {
        global $OUTPUT;

        $notloggedin = [];
        $loggedin = [];
        $inprogress = [];
        $finished = [];
        $labels = [];
        $result = [];

        if (empty($allparticipantdata)) {
            $result['hasdata'] = false;
            $result['chart'] = false;
        } else {
            $result['hasdata'] = true;
            foreach ($allparticipantdata as $participantdata) {
                $notloggedin[] = $participantdata->notloggedin;
                $loggedin[] = $participantdata->loggedin;
                $inprogress[] = $participantdata->inprogress;
                $finished[] = $participantdata->finished;
                $labels[] = userdate($participantdata->timecreated);
            }

            $charttitle = get_string('summarychart:participantsummary', 'assessfreqsource_quiz');

            // Create chart object.
            $notloggedinseries = new chart_series(get_string('summarychart:notloggedin', 'assessfreqsource_quiz'), $notloggedin);
            $notloggedinseries->set_color(get_config('assessfreqsource_quiz', 'notloggedincolor'));

            $loggedinseries = new chart_series(get_string('summarychart:loggedin', 'assessfreqsource_quiz'), $loggedin);
            $loggedinseries->set_color(get_config('assessfreqsource_quiz', 'loggedincolor'));

            $inprogressseries = new chart_series(get_string('summarychart:inprogress', 'assessfreqsource_quiz'), $inprogress);
            $inprogressseries->set_color(get_config('assessfreqsource_quiz', 'inprogresscolor'));

            $finishedseries = new chart_series(get_string('summarychart:finished', 'assessfreqsource_quiz'), $finished);
            $finishedseries->set_color(get_config('assessfreqsource_quiz', 'finishedcolor'));

            $chart = new chart_line();
            $yaxis = $chart->get_yaxis(0, true);
            $yaxis->set_stepsize(1); // Set step size for y axis to 1, can't have half a user.
            $chart->set_smooth(true);
            $chart->set_title($charttitle);
            $chart->add_series($notloggedinseries);
            $chart->add_series($loggedinseries);
            $chart->add_series($inprogressseries);
            $chart->add_series($finishedseries);
            $chart->set_labels($labels);

            $result['chart'] = $OUTPUT->render($chart);
        }

        return $result;
    }
}
