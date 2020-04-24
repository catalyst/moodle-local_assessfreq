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
 * Renderable for assessments due by month card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

defined('MOODLE_INTERNAL') || die;

use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderable for assessments due by month card.
 *
 * @package    local_assessfreq * Renderable summary for the AWS Elastic Transcode report.
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assess_due_month implements renderable, templatable {

    /**
     * Generate the markup for the process summary chart,
     * used in the smart media dashboard.
     *
     * @return $output The generated chart to be fed to a template.
     */
    private function get_assess_due_chart() {
        global $OUTPUT;



        return;

    }

    /**
     * Export the renderer data in a format that is suitable for a
     * mustache template.
     *
     * @param renderer_base $output Used to do a final render of any components that need to be rendered for export.
     *
     * @return stdClass $context for use in template rendering.
     * @throws \dml_exception
     * @throws \coding_exception
     */
    public function export_for_template(renderer_base $output) {
        $context = new stdClass();

        $context->process_summary = $this->get_assess_due_chart();

        return $context;
    }
}
