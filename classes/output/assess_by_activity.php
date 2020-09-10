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
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

use local_assessfreq\frequency;

defined('MOODLE_INTERNAL') || die;

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
    public function get_assess_by_activity_chart(int $year): array {

        // Get events for the supplied year.
        $frequency = new frequency();
        $modules = $frequency->get_process_modules();
        $activitydata = $frequency->get_events_due_by_activity($year);
        $seriesdata = array();
        $labels = array();
        $charttitle = get_string('assessbyactivity', 'local_assessfreq');
        $result = array();

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
            $events = new \core\chart_series($charttitle, $seriesdata);

            $chart = new \core\chart_bar();
            $chart->set_horizontal(true);
            $chart->add_series($events);
            $chart->set_labels($labels);
            $result['chart'] = $chart;
        }

        return $result;
    }
}
