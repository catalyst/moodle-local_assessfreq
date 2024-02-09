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
 * Renderable for upcomming quizzes card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

use local_assessfreq\quiz;

/**
 * Renderable for upcomming quizzes card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class upcomming_quizzes {
    /**
     * Generate the markup for the upcomming quizzes chart,
     * used in the in progress quizzes dashboard.
     *
     * @param int $now Timestamp to get chart data for.
     * @return array With Generated chart object and chart data status.
     */
    public function get_upcomming_quizzes_chart(int $now): array {

        // Get quizzes for the supplied timestamp.
        $quiz = new quiz();
        $quizzes = $quiz->get_quiz_summaries($now);

        $labels = [];
        $quizseriestitle = get_string('quizzes', 'local_assessfreq');
        $participantseries = get_string('students', 'local_assessfreq');
        $result = [];
        $result['hasdata'] = true;

        $quizseriesdata = [];
        $participantseriesdata = [];

        foreach ($quizzes['upcomming'] as $timestamp => $upcomming) {
            $quizcount = 0;
            $participantcount = 0;

            foreach ($upcomming as $quiz) {
                $quizcount++;
                $participantcount += $quiz->participants;
            }

            // Check if inprogress quizzes are upcomming quizzes with overrides.
            foreach ($quizzes['inprogress'] as $inprogress) {
                if ($inprogress->timestampopen >= $timestamp && $inprogress->timestampopen < $timestamp + HOURSECS) {
                    $quizcount++;
                    $participantcount += $inprogress->participants;
                }
            }

            $quizseriesdata[] = $quizcount;
            $participantseriesdata[] = $participantcount;
            $labels[] = userdate($timestamp + HOURSECS, get_string('inprogressdatetime', 'local_assessfreq'));
        }

        // Create chart object.
        $quizseries = new \core\chart_series($quizseriestitle, $quizseriesdata);
        $participantseries = new \core\chart_series($participantseries, $participantseriesdata);

        $chart = new \core\chart_bar();
        $chart->add_series($quizseries);
        $chart->add_series($participantseries);
        $chart->set_labels($labels);
        $result['chart'] = $chart;

        return $result;
    }
}
