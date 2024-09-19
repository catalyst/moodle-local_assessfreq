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
 * @package   assessfreqreport_activity_dashboard
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_activity_dashboard;

use context_system;
use local_assessfreq\report_base;

class report extends report_base {
    const WEIGHT = 20;

    /**
     * @inheritDoc
     */
    public function get_name() : string {
        return get_string("tab:name", "assessfreqreport_activity_dashboard");
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
        return 'activity_dashboard';
    }

    /**
     * @inheritDoc
     */
    public function has_access() : bool {
        global $PAGE;

        return has_capability('assessfreqreport/activity_dashboard:view', $PAGE->context);
    }

    /**
     * @inheritDoc
     */
    public function get_contents() : string {
        global $PAGE;

        $renderer = $PAGE->get_renderer("assessfreqreport_activity_dashboard");

        return $renderer->render_report();
    }

    /**
     * @inheritDoc
     */
    protected function get_required_js() : void {
        global $PAGE;

        $PAGE->requires->js_call_amd(
            'assessfreqreport_activity_dashboard/activity_dashboard',
            'init',
            [$PAGE->context->id, $PAGE->course->id != SITEID]
        );
    }

    /**
     * @inheritDoc
     */
    protected function get_required_css(): void {
        global $PAGE;

        $PAGE->requires->css('/local/assessfreq/report/activity_dashboard/styles.css');
    }
}
