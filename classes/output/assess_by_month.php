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

/**
 * Renderable for assessments due by month card.
 *
 * @package    local_assessfreq * Renderable summary for the AWS Elastic Transcode report.
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assess_by_month {

    /**
     * Generate the markup for the process summary chart,
     * used in the smart media dashboard.
     *
     * @return $output The generated chart to be fed to a template.
     */
    public function get_assess_due_chart(): \core\chart_base {
        global $OUTPUT;

        $events = new \core\chart_series('My series title', array(1, 2, 3, 4, 5, 6, 6, 5, 4, 3, 2, 1));
        $labels = array('j', 'f', 'm', 'a', 'm', 'j', 'j', 'a', 's', 'o', 'n', 'd');

        $chart = new \core\chart_bar();
        $chart->add_series($events);
        $chart->set_labels($labels);

        return $chart;

    }
}
