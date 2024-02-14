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
namespace local_assessfreq\output;

/**
 * Common code for generating charts of assessments by month
 *
 * @package   local_assessfreq
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait generate_assess_by_month_chart {
    /**
     * Generate chart markup from monthly events for a year.
     *
     * @param array $yeardata
     * @param string $charttitle
     * @return array
     */
    protected function generate_chart($yeardata, $charttitle): array {
        $result = [];
        $seriesdata = [];

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
        $labels = [
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
        ];

        $chart = new \core\chart_bar();
        $chart->add_series($events);
        $chart->set_labels($labels);

        $result['hasdata'] = true;
        $result['chart'] = $chart;

        return $result;
    }

}
