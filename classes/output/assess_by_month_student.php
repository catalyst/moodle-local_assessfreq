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
 * Renderable for assessments due by month student card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

use local_assessfreq\frequency;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderable for assessments due by month student card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assess_by_month_student {

    /**
     * Generate the markup for the process summary chart,
     * used in the dashboard.
     *
     * @param int $year Year to get chart data for.
     * @return \core\chart_base $chart Generated chart object.
     */
    public function get_assess_by_month_student_chart(int $year): \core\chart_base {

        // Get events for the supplied year.
        $frequency = new frequency();
        $yeardata = $frequency->get_events_due_monthly_by_user($year);
        $seriesdata = array();
        $charttitle = get_string('assessbymonthstudents', 'local_assessfreq');

        // There is always 12 months in a year,
        // even if we don't have data for them all.
        for ($i = 1; $i <= 12; $i++) {
            if (!empty($yeardata[$i])) {
                $seriesdata[] = $yeardata[$i]->count;
            } else {
                $seriesdata[] = 0;
            }
        }

        // Create chart object.
        $events = new \core\chart_series($charttitle, $seriesdata);
        $labels = array(
            get_string('jan', 'local_assessfreq'),
            get_string('feb', 'local_assessfreq'),
            get_string('mar', 'local_assessfreq'),
            get_string('apr', 'local_assessfreq'),
            get_string('may', 'local_assessfreq'),
            get_string('jun', 'local_assessfreq'),
            get_string('jul', 'local_assessfreq'),
            get_string('aug', 'local_assessfreq'),
            get_string('sep', 'local_assessfreq'),
            get_string('oct', 'local_assessfreq'),
            get_string('nov', 'local_assessfreq'),
            get_string('dec', 'local_assessfreq'),
        );

        $chart = new \core\chart_bar();
        $chart->add_series($events);
        $chart->set_labels($labels);

        return $chart;
    }
}
