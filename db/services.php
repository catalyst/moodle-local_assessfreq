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
 * Block Assessment frequency web service external functions and service definitions.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Define the web service functions to install.
$functions = [
    'local_assessfreq_get_frequency' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_frequency',
        'classpath' => '',
        'description' => 'Returns event frequency map.',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_assessfreq_get_heat_colors' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_heat_colors',
        'classpath' => '',
        'description' => 'Returns event heat map colors.',
        'type' => 'read',
        'loginrequired' => false,
        'ajax' => true,
    ],
    'local_assessfreq_get_process_modules' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_process_modules',
        'classpath' => '',
        'description' => 'Returns modules we are processing .',
        'type' => 'read',
        'loginrequired' => false,
        'ajax' => true,
    ],
    'local_assessfreq_get_day_events' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_day_events',
        'classpath' => '',
        'description' => 'Gets day event info for use in heatmap.',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_assessfreq_get_courses' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_courses',
        'classpath' => '',
        'description' => 'Gets courses.',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_assessfreq_get_quizzes' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_quizzes',
        'classpath' => '',
        'description' => 'Gets quizzes.',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_assessfreq_get_quiz_data' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_quiz_data',
        'classpath' => '',
        'description' => 'Gets quiz data.',
        'type' => 'read',
        'ajax' => true,
    ],
    'local_assessfreq_set_table_preference' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'set_table_preference',
        'classpath' => '',
        'description' => 'Sets a table preference.',
        'type' => 'write',
        'ajax' => true,
    ],
    'local_assessfreq_process_override_form' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'process_override_form',
        'classpath' => '',
        'description' => 'Process the override form.',
        'type' => 'write',
        'ajax' => true,
    ],
    'local_assessfreq_get_system_timezone' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_system_timezone',
        'classpath' => '',
        'description' => 'Returns system (not user) timezone.',
        'type' => 'read',
        'loginrequired' => false,
        'ajax' => true,
    ],
    'local_assessfreq_get_inprogress_counts' => [
        'classname' => 'local_assessfreq_external',
        'methodname' => 'get_inprogress_counts',
        'classpath' => '',
        'description' => 'Get counts for inprogress assessments.',
        'type' => 'read',
        'ajax' => true,
    ],
];
