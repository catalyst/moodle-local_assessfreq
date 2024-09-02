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
 * @package   assessfreqreport_activities_in_progress
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_activities_in_progress;

use local_assessfreq\report_base;
use local_assessfreq\source_base;

class report extends report_base {
    const WEIGHT = 3;

    /**
     * @inheritDoc
     */
    public function get_name() : string {
        return get_string("tab:name", "assessfreqreport_activities_in_progress");
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
        return 'activities_in_progress';
    }

    /**
     * @inheritDoc
     */
    public function has_access() : bool {
        global $PAGE;

        return has_capability('assessfreqreport/activities_in_progress:view', $PAGE->context);
    }

    /**
     * @inheritDoc
     */
    public function get_contents() : string {
        global $PAGE;

        $data = [];
        $inprogress = [];
        $upcoming = [];
        $participants = [];
        $now = time();

        $modulepreference = json_decode(
            get_user_preferences('assessfreqreport_activities_in_progress_modules_preference', '["all"]')
        );
        $sources = get_sources();
        foreach ($sources as $source) {
            /* @var $source source_base */
            if (!in_array('all', $modulepreference) && !in_array($source->get_module(), $modulepreference)) {
                continue;
            }
            if (method_exists($source, 'get_inprogress_count')) {
                $inprogress[] = $source->get_inprogress_count($now);
            }
            if (method_exists($source, 'get_upcoming_data')) {
                $upcoming[] = $source->get_upcoming_data($now);
            }
            if (method_exists($source, 'get_all_participants_inprogress_data')) {
                $participants[] = $source->get_all_participants_inprogress_data($now);
            }
        }
        $data['inprogress'] = $inprogress;
        $data['upcoming'] = $upcoming;
        $data['participants'] = $participants;

        $renderer = $PAGE->get_renderer("assessfreqreport_activities_in_progress");

        return $renderer->render_report($data);
    }

    /**
     * @inheritDoc
     */
    protected function get_required_js() : void {
        global $PAGE;

        $PAGE->requires->js_call_amd(
            'assessfreqreport_activities_in_progress/activities_in_progress',
            'init',
            [$PAGE->context->id]
        );
    }

    /**
     * @inheritDoc
     */
    protected function get_required_css() {
        global $PAGE;

        $PAGE->requires->css('/local/assessfreq/report/activities_in_progress/styles.css');
    }
}
