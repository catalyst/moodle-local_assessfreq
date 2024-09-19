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
 * @package    assessfreqreport_student_search
 * @copyright  2024 Simon Thornett <simon.thornett@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use assessfreqreport_student_search\output\user_table;

/**
 * Returns the name of the user preferences as well as the details this plugin uses.
 *
 * @return array
 */
function assessfreqreport_student_search_user_preferences(): array {

    $preferences['assessfreqreport_student_search_modules_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => '[]',
        'type' => PARAM_RAW,
    ];

    $preferences['assessfreqreport_student_search_table_rows_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => 20,
        'type' => PARAM_INT,
    ];

    $preferences['assessfreqreport_student_search_table_sort_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => 'name_asc',
        'type' => PARAM_ALPHAEXT,
    ];

    $preferences['assessfreqreport_student_search_hoursahead_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => 8,
        'type' => PARAM_INT,
    ];

    $preferences['assessfreqreport_student_search_hoursbehind_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => 1,
        'type' => PARAM_INT,
    ];

    return $preferences;
}

/**
 * Renders the user table on the report screen.
 * We update the table via ajax.
 *
 * @param array $args
 * @return string $o Form HTML.
 */
function assessfreqreport_student_search_output_fragment_get_student_search_table($args): string {
    global $CFG, $PAGE;

    $context = $args['context'];
    $data = json_decode($args['data']);

    $renderable = new user_table('/local/assessfreq', $context->id, $data->search, $data->page);
    $perpage = get_user_preferences('assessfreqreport_student_search_table_rows_preference', 20);
    ob_start();
    $renderable->out($perpage, true);
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
}
