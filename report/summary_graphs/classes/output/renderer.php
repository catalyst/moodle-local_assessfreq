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
 * @package   assessfreqreport_summary_graphs
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_summary_graphs\output;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_report($data) {
        $charts = [];

        // Assess by month container.
        if ($data->assessbymonthchart['hasdata']) {
            $contents = $data->assessbymonthchart['chart'];
        } else {
            $contents = 'No data';
        }

        $charts[] = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('chart:by_month_type', 'assessfreqreport_summary_graphs'),
                'contents' => $contents
            ]
        );

        // Assess by activity container.
        if ($data->assessbyactivitychart['hasdata']) {
            $contents = $data->assessbyactivitychart['chart'];
        } else {
            $contents = 'No data';
        }

        $charts[] = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('chart:by_activity_type', 'assessfreqreport_summary_graphs'),
                'contents' => $contents
            ]
        );

        // Assess by month student container.
        if ($data->assessbymonthstudentchart['hasdata']) {
            $contents = $data->assessbymonthstudentchart['chart'];
        } else {
            $contents = 'No data';
        }

        $charts[] = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('chart:assessments_due_type', 'assessfreqreport_summary_graphs'),
                'contents' => $contents
            ]
        );

        return $this->render_from_template(
            'assessfreqreport_summary_graphs/summary-graphs',
            [
                'charts' => $charts,
                'yearfilter' => get_config('assessfreqreport_summary_graphs', 'courselevelyearfilter'),
                'filters' => [
                    'years' => get_years(get_user_preferences('assessfreqreport_summary_graphs_year_preference', date('Y'))),
                ],
            ]
        );
    }
}
