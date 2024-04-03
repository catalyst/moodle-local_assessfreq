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
 * @package   assessfreqreport_activity_dashboard
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_activity_dashboard\output;

use local_assessfreq\source_base;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    /**
     * Generate the HTML for the report.
     *
     * @return bool|string
     */
    public function render_report() {

        $activityid = optional_param('activityid', 0, PARAM_INT);
        $sources = get_sources();

        $report = '';
        if ($activityid) {
            [$course, $cm] = get_course_and_cm_from_cmid($activityid);
            if (isset($sources[$cm->modname])) {
                /* @var $source source_base */
                $source = $sources[$cm->modname];
                if (method_exists($source, 'get_activity_dashboard')) {
                    $report = $source->get_activity_dashboard($cm, $course);
                }
            }
        }

        return $this->render_from_template(
            'assessfreqreport_activity_dashboard/activity-dashboard',
            ['report' => $report, 'activity' => '']
        );
    }
}
