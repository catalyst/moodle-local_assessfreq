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
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_heatmap;

use local_assessfreq\frequency;
use local_assessfreq\report_base;

class report extends report_base {
    const WEIGHT = 10;

    /**
     * @var int
     */
    private int $preferenceyear;

    /**
     * @var mixed|string|null
     */
    private $preferencemodules;

    /**
     * @var string
     */
    private string $preferencemetric;

    /**
     * @var int
     */
    private int $heatrangemax = 0;

    /**
     * @var int
     */
    private int $heatrangemin = 0;

    /**
     * @var int[]
     */
    private array $heatrangescales = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0];

    /**
     * @inheritDoc
     */
    public function get_name() : string {
        return get_string("tab:name", "assessfreqreport_heatmap");
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
        return 'heatmap';
    }

    /**
     * @inheritDoc
     */
    public function has_access() : bool {
        global $PAGE;

        return has_capability('assessfreqreport/heatmap:view', $PAGE->context);
    }

    /**
     * @inheritDoc
     */
    public function get_contents() : string {
        global $PAGE;

        if ($PAGE->course->id !== SITEID && !get_config('assessfreqreport_heatmap', 'courselevelyearfilter')) {
            $this->preferenceyear = date('Y', $PAGE->course->startdate);
        } else {
            $this->preferenceyear = get_user_preferences('assessfreqreport_heatmap_year_preference', date('Y'));
        }
        $this->preferencemodules = json_decode(
            get_user_preferences('assessfreqreport_heatmap_modules_preference', '["all"]'),
            true
        );
        $this->preferencemetric = get_user_preferences('assessfreqreport_heatmap_metric_preference', 'assess');

        $renderer = $PAGE->get_renderer("assessfreqreport_heatmap");

        return $renderer->render_report(
            $this->preferenceyear,
            $this->preferencemodules,
            $this->preferencemetric,
            $this->get_events(),
            $this->heatrangescales
        );
    }

    /**
     * Get all of the events and heat for each.
     *
     * @return array
     */
    private function get_events() : array {
        $frequency = new frequency();

        $orderedmonths = get_months_ordered();
        $startmonth = array_key_first($orderedmonths);

        $eventlist = $frequency->get_frequency_array(
            $this->preferenceyear,
            $startmonth,
            $this->preferencemetric,
            $this->preferencemodules
        );

        foreach ($eventlist as $year) {
            foreach ($year as $month) {
                foreach ($month as $day) {
                    $this->heatrangemax = max($this->heatrangemax, $day['number']);
                    $this->heatrangemin = min($this->heatrangemax, $day['number']);
                }
            }
        }

        foreach ($eventlist as &$year) {
            foreach ($year as &$month) {
                foreach ($month as &$day) {
                    $heat = $this->get_heat($day['number']);
                    $day['heat'] = $heat;
                    if (!$this->heatrangescales[$heat]) {
                        $this->heatrangescales[$heat] = $day['number'];
                    }
                    $this->heatrangescales[$heat] = min($day['number'], $this->heatrangescales[$heat]);
                }
            }
        }
        return $eventlist;
    }

    /**
     * Calculate the heat value based on the ranges available.
     *
     * @param $count
     * @return int
     */
    private function get_heat($count) : int {
        $scalemin = 1;

        if ($count == $this->heatrangemin) {
            return $scalemin;
        }

        $scalerange = 5;  // 0 - 5  steps.
        $localrange = $this->heatrangemax - $this->heatrangemin;
        if ($localrange <= 0) {
            return 1;
        }
        $localpercent = ($count - $this->heatrangemin) / $localrange;
        $heat = round(($localpercent * $scalerange) + 1);

        // Clamp values.
        if ($heat < 1) {
            $heat = 1;
        } else if ($heat > 6) {
            $heat = 6;
        }

        return $heat;
    }

    /**
     * @inheritDoc
     */
    protected function get_required_js() : void {
        global $PAGE;

        $PAGE->requires->js_call_amd('assessfreqreport_heatmap/heatmap', 'init', [$PAGE->course->id]);
    }

    /**
     * @inheritDoc
     */
    protected function get_required_css(): void {
        global $PAGE;
        // The CSS for the heatmap is based on plugin config. As such this needs to be in-line.
        $PAGE->requires->css('/local/assessfreq/report/heatmap/dynamic-styles.php');
    }
}
