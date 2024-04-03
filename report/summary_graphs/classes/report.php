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
 * Main report class.
 *
 * @package   assessfreqreport_summary_graphs
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_summary_graphs;

use assessfreqreport_summary_graphs\output\assess_by_activity;
use assessfreqreport_summary_graphs\output\assess_by_month;
use assessfreqreport_summary_graphs\output\assess_by_month_student;
use local_assessfreq\report_base;
use stdClass;

class report extends report_base {
    const WEIGHT = 4;

    /**
     * @inheritDoc
     */
    public function get_name() : string {
        return get_string("tab:name", "assessfreqreport_summary_graphs");
    }

    /**
     * @inheritDoc
     */
    public function get_tab_weight() : int {
        return self::WEIGHT;
    }

    /**
     * @inheritDoc
     */
    public function get_tablink() : string {
        return 'summary_graphs';
    }

    /**
     * @inheritDoc
     */
    public function has_access() : bool {
        global $PAGE;

        return has_capability('assessfreqreport/summary_graphs:view', $PAGE->context);
    }

    /**
     * @inheritDoc
     */
    public function get_contents() : string {
        global $PAGE;

        $year = get_user_preferences('assessfreqreport_summary_graphs_year_preference', date('Y'));

        $data = new stdClass();
        $data->assessbymonthchart = (new assess_by_month())->get_assess_by_month_chart($year);
        $data->assessbyactivitychart = (new assess_by_activity())->get_assess_by_activity_chart($year);
        $data->assessbymonthstudentchart = (new assess_by_month_student())->get_assess_by_month_student_chart($year);

        $renderer = $PAGE->get_renderer("assessfreqreport_summary_graphs");

        return $renderer->render_report($data);
    }

    /**
     * @inheritDoc
     */
    protected function get_required_js() : void {
        global $PAGE;

        $PAGE->requires->js_call_amd('assessfreqreport_summary_graphs/summary_graphs', 'init');
    }
}
