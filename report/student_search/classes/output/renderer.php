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
 * @package   assessfreqreport_student_search
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_student_search\output;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Generate the HTML for the report.
     *
     * @return bool|string
     */
    public function render_report() {

        $preferencerows = get_user_preferences('local_assessfreq_student_search_table_rows_preference', 20);
        $rows = [
            20 => 'rows20',
            50 => 'rows50',
            100 => 'rows100',
        ];

        $preferencehoursahead = (int)get_user_preferences('assessfreqreport_student_search_progress_hoursahead_preference', 8);
        $preferencehoursbehind = (int)get_user_preferences('assessfreqreport_student_search_hoursbehind_preference', 1);

        $hours = [
            0 => 'hours0',
            1 => 'hours1',
            4 => 'hours4',
            8 => 'hours8',
        ];

        return $this->render_from_template(
            'assessfreqreport_student_search/student-search',
            [
                'filters' => [
                    'hoursahead' => [$hours[$preferencehoursahead] => 'true'],
                    'hoursbehind' => [$hours[$preferencehoursbehind] => 'true'],
                ],
                'table' => [
                    'id' => 'assessfreqreport-student-search',
                    'name' => get_string('studentsearch:head', 'assessfreqreport_student_search'),
                    'rows' => [$rows[$preferencerows] => 'true'],
                ]
            ]
        );
    }
}
