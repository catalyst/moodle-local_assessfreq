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
use local_assessfreq\frequency;

/**
 * Assessment Frequency block rendrer.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the html for the report cards.
     * Most content is loaded by ajax
     *
     * @return string html to display.
     */
    public function render_report_cards(): string {
        $preferenceyear = get_user_preferences('local_assessfreq_overview_year_preference', date('Y'));
        $frequency = new frequency();
        $years = $frequency->get_years_has_events();

        if (empty($years)) {
            $years = array(date('Y'));
        }

        $context = array('years' => array(), 'currentyear' => $preferenceyear);

        if (!empty($years)) {
            foreach ($years as $year) {
                if ($year == $preferenceyear) {
                    $context['years'][] = array('year' => array('val' => $year, 'active' => 'true'));
                } else {
                    $context['years'][] = array('year' => array('val' => $year));
                }
            }
        } else {
            $context['years'][] = array('year' => array('val' => $preferenceyear, 'active' => 'true'));
        }

        return $this->render_from_template('local_assessfreq/report-cards', $context);
    }

    /**
     * Return heatmap HTML.
     *
     * @return string The heatmap HTML.
     */
    public function render_report_heatmap(): string {
        $preferenceyear = get_user_preferences('local_assessfreq_heatmap_year_preference', date('Y'));
        $preferencemetric = get_user_preferences('local_assessfreq_heatmap_metric_preference', 'assess');
        $preferencemodules = json_decode(get_user_preferences('local_assessfreq_heatmap_modules_preference', '["all"]'), true);

        $frequency = new frequency();

        // Initial context setup.
        $context = array(
            'years' => array(),
            'currentyear' => $preferenceyear,
            'modules' => array(),
            'metrics' => array(),
            'sesskey' => sesskey(),
            'downloadmetric' => $preferencemetric
        );

        // Get years that have events and load into context.
        $years = $frequency->get_years_has_events();
        if (empty($years)) {
            $years = array(date('Y'));
        }

        if (!empty($years)) {
            foreach ($years as $year) {
                if ($year == $preferenceyear) {
                    $context['years'][] = array('year' => array('val' => $year, 'active' => 'true'));
                    $context['downloadyear'] = $year;
                } else {
                    $context['years'][] = array('year' => array('val' => $year));
                }
            }
        } else {
            $context['years'][] = array('year' => array('val' => $preferenceyear, 'active' => 'true'));
            $context['downloadyear'] = $preferenceyear;
        }

        // Get modules for filters and load into context.
        $modules = $frequency->get_process_modules();
        if (empty($preferencemodules) || $preferencemodules === array('all')) {
            $context['modules'][] = array('module' => array('val' => 'all', 'name' => get_string('all'),  'active' => 'true'));
        } else {
            $context['modules'][] = array('module' => array('val' => 'all', 'name' => get_string('all')));
        }

        if (!empty($modules[0])) {
            foreach ($modules as $module) {
                $modulename = get_string('modulename', $module);
                if (in_array($module, $preferencemodules)) {
                    $context['modules'][] = array('module' => array('val' => $module, 'name' => $modulename,  'active' => 'true'));
                } else {
                    $context['modules'][] = array('module' => array('val' => $module, 'name' => $modulename));
                }
            }
        }

        // Get metric details and load into context.
        $context['metrics'] = array($preferencemetric => 'true');

        return $this->render_from_template('local_assessfreq/report-heatmap', $context);
    }

    /**
     * Get the html to render the assessment dashboard.
     *
     * @param string $baseurl the base url to render this report on.
     * @return string $html the html to display.
     */
    public function render_dashboard_assessment(string $baseurl) : string {
        $html = '';
        $html .= $this->header();
        $html .= $this->render_report_cards();
        $html .= $this->render_report_heatmap();
        $html .= $this->footer();

        return $html;
    }

    private function quiz_select_autocomplete(): string {
        // TODO: add in initial quiz id.

        $context = array();
        return $this->render_from_template('local_assessfreq/quiz-search', $context);

    }

    /**
     * Get the html to render the quiz dashboard.
     *
     * @param string $baseurl the base url to render this report on.
     * @return string $html the html to display.
     */
    public function render_dashboard_quiz(string $baseurl) : string {
        $html = '';
        $html .= $this->header();
        $html .= $this->quiz_select_autocomplete();
        $html .= $this->footer();

        return $html;
    }
}
