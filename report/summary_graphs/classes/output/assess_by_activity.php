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
 * Renderable for assessments by activity card.
 *
 * @package    assessfreqreport_summary_graphs
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_summary_graphs\output;

use core\chart_series;
use local_assessfreq\frequency;

/**
 * Renderable for assessments by activity card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assess_by_activity {
    /**
     * Generate the markup for the process summary chart,
     * used in the smart media dashboard.
     *
     * @param int $year Year to get chart data for.
     * @return array With Generated chart object and chart data status.
     */
    public function get_assess_by_activity_chart(int $year) : array {
        global $OUTPUT;

        // Get events for the supplied year.
        $frequency = new frequency();
        $modules = $frequency->get_process_modules();
        $activitydata = $frequency->get_events_due_by_activity($year);
        $seriesdata = [];
        $labels = [];
        $charttitle = get_string('chart:by_activity_type', 'assessfreqreport_summary_graphs');
        $result = [];

        if (empty($modules[0])) {
            $result['hasdata'] = false;
            $result['chart'] = false;
        } else {
            $result['hasdata'] = true;

            foreach ($modules as $module) {
                if (! empty($activitydata[$module])) {
                    $seriesdata[] = $activitydata[$module]->count;
                } else {
                    $seriesdata[] = 0;
                }
                $labels[] = get_string('modulename', $module);
            }

            // Create chart object.
            $events = new chart_series($charttitle, $seriesdata);

            $config = get_config('assessfreqreport_summary_graphs', 'by_activity_type');
            $chartclass = "core\chart_bar";
            if ($config) {
                $chartclass = "core\chart_$config";
            }

            $chart = new $chartclass();
            if ($config == "bar") {
                $chart->set_horizontal(true);
            }
            $chart->add_series($events);
            $chart->set_labels($labels);
            $result['chart'] = $OUTPUT->render($chart);
        }

        return $result;
    }
}
