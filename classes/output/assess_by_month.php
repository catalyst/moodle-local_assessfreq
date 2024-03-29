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

use local_assessfreq\frequency;

/**
 * Renderable for assessments due by month card.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assess_by_month {
    use generate_assess_by_month_chart;

    /**
     * Generate the markup for the process summary chart,
     * used in the smart media dashboard.
     *
     * @param int $year Year to get chart data for.
     * @return array With Generated chart object and chart data status.
     */
    public function get_assess_by_month_chart(int $year): array {

        // Get events for the supplied year.
        $frequency = new frequency();
        $yeardata = $frequency->get_events_due_by_month($year);
        $charttitle = get_string('assessbymonth', 'local_assessfreq');
        return $this->generate_chart($yeardata, $charttitle);
    }
}
