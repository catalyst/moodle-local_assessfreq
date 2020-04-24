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
 * Assessment Frequency block rendrer.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_assessfreq\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;

/**
 * Assessment Frequency block rendrer.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the html for the report summary.
     *
     * @return string html to display.
     */
    public function render_report_cards() {
        $context = new \stdClass();

        $assessduemonth = new assess_due_month();
        $context->assessduemonth = $assessduemonth->export_for_template($this);

        return $this->render_from_template('local_assessfreq/report-cards', $context);
    }

    /**
     * Get the html to render the local_smartmedia report.
     *
     * @param string $baseurl the base url to render this report on.
     * @return string $html the html to display.
     */
    public function render_report(string $baseurl) : string {
            $html = '';
            $html .= $this->header();
            // content goes here.
            $html .= $this->render_report_cards();
            $html .= $this->footer();

            return $html;
    }
}
