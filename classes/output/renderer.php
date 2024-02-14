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

use local_assessfreq\quiz;
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
        $currentyear = date('Y');
        $preferenceyear = get_user_preferences('local_assessfreq_overview_year_preference', $currentyear);
        $frequency = new frequency();

        // Get years that have events and load into context.
        $years = $frequency->get_years_has_events();

        if (empty($years)) {
            $years = [$currentyear];
        }

        // Add current year to the selection of years if missing.
        if (!in_array($currentyear, $years)) {
            $years[] = $currentyear;
        }

        $context = ['years' => [], 'currentyear' => $preferenceyear];

        if (!empty($years)) {
            foreach ($years as $year) {
                if ($year == $preferenceyear) {
                    $context['years'][] = ['year' => ['val' => $year, 'active' => 'true']];
                } else {
                    $context['years'][] = ['year' => ['val' => $year]];
                }
            }
        } else {
            $context['years'][] = ['year' => ['val' => $preferenceyear, 'active' => 'true']];
        }

        return $this->render_from_template('local_assessfreq/report-cards', $context);
    }

    /**
     * Render the HTML for the student quiz table.
     *
     * @param string $baseurl the base url to render the table on.
     * @param int $quizid the id of the quiz in the quiz table.
     * @param int $contextid the id of the context the table is being called in.
     * @param string $search The string to search for.
     * @param int $page the page number for pagination.
     * @return string $output HTML for the table.
     */
    public function render_student_table(string $baseurl, int $quizid, int $contextid, string $search = '', int $page = 0): string {
        $renderable = new quiz_user_table($baseurl, $quizid, $contextid, $search, $page);
        $perpage = 50;
        ob_start();
        $renderable->out($perpage, true);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Render the HTML for the student search table.
     *
     * @param string $baseurl the base url to render the table on.
     * @param int $contextid the id of the context the table is being called in.
     * @param string $search The string to search for.
     * @param int $hoursahead Ammount of time in hours to look ahead for quizzes starting.
     * @param int $hoursbehind Ammount of time in hours to look behind for quizzes starting.
     * @param int $now The timestamp to use for the current time.
     * @param int $page the page number for pagination.
     * @return string $output HTML for the table.
     */
    public function render_student_search_table(
        string $baseurl,
        int $contextid,
        string $search,
        int $hoursahead,
        int $hoursbehind,
        int $now,
        int $page = 0
    ): string {

        $renderable = new student_search_table($baseurl, $contextid, $search, $hoursahead, $hoursbehind, $now, $page);
        $perpage = 50;

        ob_start();
        $renderable->out($perpage, true);
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Renders the quizzes in progress "table" on the quiz dashboard screen.
     * We update the table via ajax.
     * The table isn't a real table it's a collection of divs.
     *
     * @param string $search The search string for the table.
     * @param int $page The page number of results.
     * @param string $sorton The value to sort the quizzes by.
     * @param string $direction The direction to sort the quizzes.
     * @param int $hoursahead Amount of time in hours to look ahead for quizzes starting.
     * @param int $hoursbehind Amount of time in hours to look behind for quizzes starting.
     * @return string $output HTML for the table.
     */
    public function render_quizzes_inprogress_table(
        string $search,
        int $page,
        string $sorton,
        string $direction,
        int $hoursahead = 0,
        int $hoursbehind = 0
    ): string {
        $context = \context_system::instance(); // TODO: pass the actual context in from the caller.
        $now = time();
        $quiz = new quiz($hoursahead, $hoursbehind);
        $quizzes = $quiz->get_quiz_summaries($now);
        $pagesize = get_user_preferences('local_assessfreq_quiz_table_inprogress_preference', 5);

        $inprogressquizzes = $quizzes['inprogress'];
        $upcommingquizzes = $quizzes['upcomming'];
        $finishedquizzes = $quizzes['finished'];

        foreach ($upcommingquizzes as $key => $upcommingquiz) {
            foreach ($upcommingquiz as $keyupcomming => $upcomming) {
                $inprogressquizzes[$keyupcomming] = $upcomming;
            }
        }

        foreach ($finishedquizzes as $key => $finishedquiz) {
            foreach ($finishedquiz as $keyfinished => $finished) {
                $inprogressquizzes[$keyfinished] = $finished;
            }
        }

        [$filtered, $totalrows] = $quiz->filter_quizzes($inprogressquizzes, $search, $page, $pagesize);
        $sortedquizzes = \local_assessfreq\utils::sort($filtered, $sorton, $direction);

        $pagingbar = new \paging_bar($totalrows, $page, $pagesize, '/');
        $pagingoutput = $this->render($pagingbar);

        $context = [
            'quizzes' => array_values($sortedquizzes),
            'quizids' => json_encode(array_keys($sortedquizzes)),
            'context' => $context->id,
            'pagingbar' => $pagingoutput,
        ];

        $output = $this->render_from_template('local_assessfreq/quiz-inprogress-summary', $context);

        return $output;
    }

    /**
     * Return heatmap HTML.
     *
     * @return string The heatmap HTML.
     */
    public function render_report_heatmap(): string {
        $currentyear = date('Y');
        $preferenceyear = get_user_preferences('local_assessfreq_heatmap_year_preference', $currentyear);
        $preferencemetric = get_user_preferences('local_assessfreq_heatmap_metric_preference', 'assess');
        $preferencemodules = json_decode(get_user_preferences('local_assessfreq_heatmap_modules_preference', '["all"]'), true);

        $frequency = new frequency();

        // Initial context setup.
        $context = [
            'years' => [],
            'currentyear' => $preferenceyear,
            'modules' => [],
            'metrics' => [],
            'sesskey' => sesskey(),
            'downloadmetric' => $preferencemetric,
        ];

        // Get years that have events and load into context.
        $years = $frequency->get_years_has_events();

        if (empty($years)) {
            $years = [$currentyear];
        }

        // Add current year to the selection of years if missing.
        if (!in_array($currentyear, $years)) {
            $years[] = $currentyear;
        }

        if (!empty($years)) {
            foreach ($years as $year) {
                if ($year == $preferenceyear) {
                    $context['years'][] = ['year' => ['val' => $year, 'active' => 'true']];
                    $context['downloadyear'] = $year;
                } else {
                    $context['years'][] = ['year' => ['val' => $year]];
                }
            }
        } else {
            $context['years'][] = ['year' => ['val' => $preferenceyear, 'active' => 'true']];
            $context['downloadyear'] = $preferenceyear;
        }

        // Get modules for filters and load into context.
        $modules = $frequency->get_process_modules();
        if (empty($preferencemodules) || $preferencemodules === ['all']) {
            $context['modules'][] = ['module' => ['val' => 'all', 'name' => get_string('all'), 'active' => 'true']];
        } else {
            $context['modules'][] = ['module' => ['val' => 'all', 'name' => get_string('all')]];
        }

        if (!empty($modules[0])) {
            foreach ($modules as $module) {
                $modulename = get_string('modulename', $module);
                if (in_array($module, $preferencemodules)) {
                    $context['modules'][] = ['module' => ['val' => $module, 'name' => $modulename, 'active' => 'true']];
                } else {
                    $context['modules'][] = ['module' => ['val' => $module, 'name' => $modulename]];
                }
            }
        }

        // Get metric details and load into context.
        $context['metrics'] = [$preferencemetric => 'true'];

        return $this->render_from_template('local_assessfreq/report-heatmap', $context);
    }

    /**
     * Get the html to render the assessment dashboard.
     *
     * @param string $baseurl the base url to render this report on.
     * @return string $html the html to display.
     */
    public function render_dashboard_assessment(string $baseurl): string {
        $html = '';
        $html .= $this->header();
        $html .= $this->render_report_cards();
        $html .= $this->render_report_heatmap();
        $html .= $this->footer();

        return $html;
    }

    /**
     * Add HTML for quiz selection and quiz refresh buttons.
     *
     * @return string html for the button.
     */
    private function render_quiz_select_refresh_button(): string {
        $preferencerefresh = get_user_preferences('local_assessfreq_quiz_refresh_preference', 60);
        $refreshminutes = [
            60 => 'minuteone',
            120 => 'minutetwo',
            300 => 'minutefive',
            600 => 'minuteten',
        ];

        $context = [
            'refreshinitial' => get_string($refreshminutes[$preferencerefresh], 'local_assessfreq'),
            'refresh' => [$refreshminutes[$preferencerefresh] => 'true'],
            'hide' => true,
        ];

        return $this->render_from_template('local_assessfreq/quiz-dashboard-controls', $context);
    }

    /**
     * Add HTML for quiz refresh button.
     *
     * @return string html for the button.
     */
    private function render_quiz_refresh_button(): string {
        $preferencerefresh = get_user_preferences('local_assessfreq_quiz_refresh_preference', 60);
        $preferencehoursahead = get_user_preferences('local_assessfreq_quizzes_inprogress_table_hoursahead_preference', 0);
        $preferencehoursbehind = get_user_preferences('local_assessfreq_quizzes_inprogress_table_hoursbehind_preference', 0);

        $refreshminutes = [
            60 => 'minuteone',
            120 => 'minutetwo',
            300 => 'minutefive',
            600 => 'minuteten',
        ];

        $hours = [
            0 => 'hours0',
            1 => 'hours1',
            4 => 'hours4',
            8 => 'hours8',
        ];

        $context = [
            'refreshinitial' => get_string($refreshminutes[$preferencerefresh], 'local_assessfreq'),
            'refresh' => [$refreshminutes[$preferencerefresh] => 'true'],
            'hoursahead' => [$hours[$preferencehoursahead] => 'true'],
            'hoursbehind' => [$hours[$preferencehoursbehind] => 'true'],
        ];

        return $this->render_from_template('local_assessfreq/quiz-dashboard-inprogress-controls', $context);
    }

    /**
     * Render the cards on the quiz dashboard.
     *
     * @return string
     */
    private function render_quiz_dashboard_cards(): string {
        $preferencerows = get_user_preferences('local_assessfreq_quiz_table_rows_preference', 20);
        $rows = [
            20 => 'rows20',
            50 => 'rows50',
            100 => 'rows100',
        ];

        $context = [
            'rows' => [$rows[$preferencerows] => 'true'],
        ];

        return $this->render_from_template('local_assessfreq/quiz-dashboard-cards', $context);
    }

    /**
     * Render the cards on the quiz dashboard.
     *
     * @return string
     */
    private function render_quiz_dashboard_inprogress_cards(): string {
        $preferencerows = get_user_preferences('local_assessfreq_quiz_table_inprogress_preference', 10);
        $preferencesort = get_user_preferences('local_assessfreq_quiz_table_inprogress_sort_preference', 'name_asc');
        $rows = [
            5 => 'rows5',
            10 => 'rows10',
            20 => 'rows20',
        ];

        $context = [
            'rows' => [$rows[$preferencerows] => 'true'],
            'sort' => [$preferencesort => 'true'],
        ];

        return $this->render_from_template('local_assessfreq/quiz-dashboard-inprogress-cards', $context);
    }

    /**
     * Render the cards on the quiz dashboard.
     *
     * @return string
     */
    private function render_student_table_cards(): string {
        $preferencerows = get_user_preferences('local_assessfreq_student_search_table_rows_preference', 20);
        $preferencehoursahead = get_user_preferences('local_assessfreq_student_search_table_hoursahead_preference', 4);
        $preferencehoursbehind = get_user_preferences('local_assessfreq_student_search_table_hoursbehind_preference', 1);
        $preferencerefresh = get_user_preferences('local_assessfreq_quiz_refresh_preference', 60);

        $refreshminutes = [
            60 => 'minuteone',
            120 => 'minutetwo',
            300 => 'minutefive',
            600 => 'minuteten',
        ];

        $rows = [
            20 => 'rows20',
            50 => 'rows50',
            100 => 'rows100',
        ];

        $hours = [
            0 => 'hours0',
            1 => 'hours1',
            4 => 'hours4',
            8 => 'hours8',
        ];

        $preferencerefresh = get_user_preferences('local_assessfreq_quiz_refresh_preference', 60);
        $refreshminutes = [
            60 => 'minuteone',
            120 => 'minutetwo',
            300 => 'minutefive',
            600 => 'minuteten',
        ];

        $context = [
            'rows' => [$rows[$preferencerows] => 'true'],
            'hoursahead' => [$hours[$preferencehoursahead] => 'true'],
            'hoursbehind' => [$hours[$preferencehoursbehind] => 'true'],
            'refreshinitial' => get_string($refreshminutes[$preferencerefresh], 'local_assessfreq'),
            'refresh' => [$refreshminutes[$preferencerefresh] => 'true'],
        ];

        return $this->render_from_template('local_assessfreq/student-search', $context);
    }

    /**
     * Get the html to render the quiz dashboard.
     *
     * @param string $baseurl the base url to render this report on.
     * @return string $html the html to display.
     */
    public function render_dashboard_quiz(string $baseurl): string {
        $html = '';
        $html .= $this->header();
        $html .= $this->render_quiz_select_refresh_button();
        $html .= $this->render_quiz_dashboard_cards();
        $html .= $this->footer();

        return $html;
    }

    /**
     * Get the html to render the quizzes in porgress dashboard.
     *
     * @param string $baseurl the base url to render this report on.
     * @return string $html the html to display.
     */
    public function render_dashboard_quiz_inprogress(string $baseurl): string {
        $html = '';
        $html .= $this->header();
        $html .= $this->render_quiz_refresh_button();
        $html .= $this->render_quiz_dashboard_inprogress_cards();
        $html .= $this->footer();

        return $html;
    }

    /**
     * Get the html to render the student search.
     *
     * @return string $html the html to display.
     */
    public function render_student_search(): string {
        $html = '';
        $html .= $this->header();
        $html .= $this->render_student_table_cards();
        $html .= $this->footer();

        return $html;
    }
}
