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
 * @package   local_assessfreq
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

use plugin_renderer_base;

class renderer extends plugin_renderer_base {
    /**
     * Render each of the assessfreqreport subplugins as tabs to display.
     *
     * @return void
     */
    public function render_reports() : void {
        $reports = get_reports();
        $reportoutputs = [];
        foreach ($reports as $report) {
            $reportoutputs[] = [
                'tablink' => $report->get_tablink(), // Plugin name.
                'tabname' => $report->get_name(), // Display name.
                'report' => $report->get_contents(),
                'weight' => $report->get_tab_weight(),
            ];
        }
        usort($reportoutputs, function($a, $b) {
            return $a['weight'] <=> $b['weight'];
        });
        $output = $this->output->header();
        $output .= $this->render_from_template(
            'local_assessfreq/index',
            [
                'reports' => $reportoutputs
            ]
        );
        $output .= $this->output->footer();
        echo $output;
    }
}
