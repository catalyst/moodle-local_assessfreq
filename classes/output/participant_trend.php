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
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

use local_assessfreq\quiz;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderable for participant trend card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class participant_trend {

    /**
     * Generate the markup for the trend chart,
     * used in the quiz dashboard.
     *
     * @param int $quizid Quiz id to get chart data for.
     * @return \core\chart_base $chart Generated chart object.
     */
    public function get_participant_trend_chart(int $quizid): \core\chart_base {

        $quizdata = new quiz();
        $allparticipantdata = $quizdata->get_quiz_tracking($quizid);
        $notloggedin = array();
        $inprogress = array();
        $finished = array();

        foreach ($allparticipantdata as $participantdata){
            $notloggedin[] = $participantdata->notloggedin;
            $inprogress[] = $participantdata->inprogress;
            $finished[] = $participantdata->finished;
        }

        $labels = array(
            get_string('notloggedin', 'local_assessfreq'),
            get_string('inprogress', 'local_assessfreq'),
            get_string('finished', 'local_assessfreq')
        );

        $charttitle = get_string('participantsummary', 'local_assessfreq');

        // Create chart object.
        $notloggedinseries = new \core\chart_series(get_string('notloggedin', 'local_assessfreq'), $notloggedin);
        $inprogressseries = new \core\chart_series(get_string('inprogress', 'local_assessfreq'), $inprogress);
        $finishedseries = new \core\chart_series(get_string('finished', 'local_assessfreq'), $finished);

        $chart = new \core\chart_line();
        $chart->set_smooth(true);
        $chart->set_title($charttitle);
        $chart->add_series($notloggedinseries);
        $chart->add_series($inprogressseries);
        $chart->add_series($finishedseries);
        $chart->set_labels($labels);

        return $chart;
    }
}
