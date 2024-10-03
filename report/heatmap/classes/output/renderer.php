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
 * Renderer.
 *
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_heatmap\output;

use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Generate the HTML for the report.
     *
     * @param $preferenceyear
     * @param $preferencemodules
     * @param $preferencemetric
     * @param $events
     * @param $heatrangescales
     * @return bool|string
     */
    public function render_report($preferenceyear, $preferencemodules, $preferencemetric, $events, $heatrangescales) {

        $originalyear = $preferenceyear;
        $orderedmonths = get_months_ordered();

        $months = $this->get_calendar($preferenceyear, $orderedmonths, $events);

        $scalestable = new html_table();
        $scalestable->attributes['class'] = 'scales-table';
        $scalecells = [];
        foreach ($heatrangescales as $heatrangescale => $heatrangecount) {
            if ($heatrangecount) {
                $cell = new html_table_cell("$heatrangecount+");
                $cell->attributes['class'] = "scales-cell heat-$heatrangescale";
                $scalecells[] = $cell;
            }
        }
        $scalestable->data = [new html_table_row($scalecells)];

        $modules = get_modules($preferencemodules);
        $selectedmodules = [];
        foreach ($modules as $module) {
            if (isset($module['module']['active'])) {
                $selectedmodules[] = $module['module']['name'];
            }
        }

        return $this->render_from_template(
            'assessfreqreport_heatmap/heatmap',
            [
                'filters' => [
                    'years' => get_years($preferenceyear),
                    'modules' => $modules,
                    'metrics' => [$preferencemetric => ['active' => true]],
                    'selected_modules' => implode(', ', $selectedmodules),
                    'selected_metric' => get_string("filter:metric:$preferencemetric", 'assessfreqreport_heatmap'),
                ],
                'downloadmetric' => $preferencemetric,
                'sesskey' => sesskey(),
                'yearfilter' => get_config('assessfreqreport_heatmap', 'courselevelyearfilter'),
                'courseid' => $this->page->course->id,
                'months' => $months,
                'scales' => html_writer::table($scalestable),
                'year' => $originalyear,
                'month' => array_shift($orderedmonths),
            ]
        );
    }

    /**
     * Get the calendar of events.
     *
     * @param $preferenceyear
     * @param $orderedmonths
     * @param $events
     * @return array
     */
    private function get_calendar($preferenceyear, $orderedmonths, $events) : array {
        $months = [];

        foreach ($orderedmonths as $monthnumber => $monthname) {
            $monthtable = new html_table();
            $monthtable->head = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            $monthtable->data = [];
            $monthtable->attributes['class'] = 'month-table';

            // Number of days in the month.
            $monthdays = date('t', mktime(0, 0, 0, date($monthnumber), 1, $preferenceyear));

            // Get first day of the month.
            $j = date('w', mktime(0, 0, 0, date($monthnumber), 1, $preferenceyear));

            $week = new html_table_row();
            for ($i = 1; $i <= $j; $i++) {
                $cell = new html_table_cell("&nbsp;");
                $cell->attributes = ['class' => "empty-cell"];
                $week->cells[] = $cell;
            }

            for ($i = 1; $i <= $monthdays; $i++) {
                // If we're at the end of a week, start a new row.
                if ($j % 7 == 0) {
                    $monthtable->data[] = $week;
                    $week = new html_table_row();
                }
                if (isset($events[$preferenceyear][$monthnumber][$i])) {
                    $cell = new html_table_cell($i);
                    $cell->attributes = [
                        'class' => "show-dialog has-events heat-" . $events[$preferenceyear][$monthnumber][$i]['heat'],
                        'data-target' => "$preferenceyear-$monthnumber-$i"
                    ];
                    $week->cells[] = $cell;
                } else {
                    $week->cells[] = new html_table_cell($i);
                }
                $j++;
            }
            $monthtable->data[] = $week;

            $months[] = ['month' => "$monthname - $preferenceyear", 'table' => html_writer::table($monthtable)];

            // If the start month isn't Jan then we need to increase the year for the months after Dec.
            if ($monthnumber == 12) {
                $preferenceyear++;
            }
        }

        return $months;
    }
}
