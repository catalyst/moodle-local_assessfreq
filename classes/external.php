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
 * Local assessfreq Web Service.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\session\manager;
use local_assessfreq\source_base;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once(dirname(__FILE__, 2) . '/lib.php');

/**
 * Local assessfreq Web Service.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_assessfreq_external extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_courses_parameters() : external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_TEXT, 'The query to find'),
        ]);
    }

    /**
     * Returns courses that match search data.
     *
     * @param string $query The search query.
     * @return string JSON response.
     */
    public static function get_courses(string $query) : string {
        global $DB;
        manager::write_close(); // Close session early this is a read op.

        // Parameter validation.
        self::validate_parameters(
            self::get_courses_parameters(),
            ['query' => $query]
        );

        // Execute API call.
        $sql = 'SELECT id, fullname FROM {course} WHERE ' . $DB->sql_like('fullname', ':fullname', false) . ' AND id <> 1';
        $params = ['fullname' => '%' . $DB->sql_like_escape($query) . '%'];
        $courses = $DB->get_records_sql($sql, $params, 0, 11);

        $data = [];
        foreach ($courses as $course) {
            $data[$course->id] = [
                "id" => $course->id,
                "fullname" => external_format_string($course->fullname, true, ["escape" => false])
            ];
        }

        return json_encode(array_values($data));
    }

    /**
     * Returns description of method result value
     * @return external_value
     */
    public static function get_courses_returns() : external_value {
        return new external_value(PARAM_RAW, 'Course result JSON');
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_activities_parameters() : external_function_parameters {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'The courseid to find'),
        ]);
    }

    /**
     * Returns activities in the course that match search data.
     *
     * @param $courseid
     * @return string JSON response.
     */
    public static function get_activities($courseid) : string {
        global $DB;
        manager::write_close(); // Close session early this is a read op.

        // Parameter validation.
        self::validate_parameters(
            self::get_activities_parameters(),
            ['courseid' => $courseid]
        );

        // Execute API call.
        $modules = $DB->get_records('course_modules', ['course' => $courseid]);

        $sources = get_sources();

        $data = [];
        foreach ($modules as $module) {
            $modinfo = get_fast_modinfo($courseid);
            $cm = $modinfo->get_cm($module->id);
            // Skip over if source is not enabled or if the source doesn't have an activity dashboard.
            $moduletype = $cm->modname;
            if (!isset($sources[$moduletype]) || !method_exists($sources[$moduletype], 'get_activity_dashboard')) {
                continue;
            }

            $data[$module->id] = [
                "id" => $module->id,
                "name" => $cm->get_module_type_name() . " - " . $cm->get_name()
            ];
        }

        usort($data, fn($a, $b) => $a['name'] <=> $b['name']);

        return json_encode(array_values($data));
    }

    /**
     * Returns description of method result value
     * @return external_value
     */
    public static function get_activities_returns() : external_value {
        return new external_value(PARAM_RAW, 'Result JSON');
    }


    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function set_table_preference_parameters() : external_function_parameters {
        return new external_function_parameters([
            'tableid' => new external_value(PARAM_ALPHANUMEXT, 'The table id to set the preference for'),
            'preference' => new external_value(PARAM_ALPHAEXT, 'The table preference to set'),
            'values' => new external_value(PARAM_RAW, 'The values to set as JSON'),
        ]);
    }

    /**
     * Set table preferences.
     *
     * @param string $tableid The table id to set the preference for.
     * @param string $preference The name of the preference to set.
     * @param string $values The values to set for the preference, encoded as JSON.
     * @return string JSON response.
     */
    public static function set_table_preference(string $tableid, string $preference, string $values) : string {
        global $SESSION, $PAGE;

        // Parameter validation.
        self::validate_parameters(
            self::set_table_preference_parameters(),
            ['tableid' => $tableid, 'preference' => $preference, 'values' => $values]
        );

        // Set up the initial preference template.
        $prefs = $SESSION->flextable[$tableid] ?? [
            'collapse' => [],
            'sortby' => [],
            'i_first' => '',
            'i_last' => '',
            'textsort' => [],
        ];

        // Set or reset the preferences.
        if ($preference == 'reset') {
            $prefs = [
                'collapse' => [],
                'sortby'   => [],
                'i_first'  => '',
                'i_last'   => '',
                'textsort' => [],
            ];
        } else {
            $prefs[$preference] = json_decode($values, true);
        }

        $SESSION->flextable[$tableid] = $prefs;

        return $preference;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function set_table_preference_returns() {
        return new external_value(PARAM_ALPHAEXT, 'Name of the updated preference');
    }


    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function process_override_form_parameters() : external_function_parameters {
        return new external_function_parameters(
            [
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create copy form, encoded as a json array'),
                'activitytype' => new external_value(PARAM_ALPHANUMEXT, 'The activity to processs the override for'),
                'activityid' => new external_value(PARAM_INT, 'The activity id to processs the override for'),
            ]
        );
    }

    /**
     * Submit the override form.
     *
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @param string $activitytype The activity to add an override for.
     * @param int $activityid The activity id to add an override for.
     * @return string
     */
    public static function process_override_form(string $jsonformdata, string $activitytype, int $activityid) : string {
        global $DB;

        // Release session lock.
        manager::write_close();

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::process_override_form_parameters(),
            ['jsonformdata' => $jsonformdata, 'activitytype' => $activitytype, 'activityid' => $activityid]
        );

        $formdata = json_decode($params['jsonformdata']);

        $submitteddata = [];
        parse_str($formdata, $submitteddata);

        $processid = 0;
        $sources = get_sources();
        $source = $sources[$activitytype];
        /* @var $source source_base */
        if (method_exists($source, 'process_override_form')) {
            $processid = $source->process_override_form($activityid, $submitteddata);
        }

        return json_encode(['overrideid' => $processid]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function process_override_form_returns() {
        return new external_value(PARAM_RAW, 'JSON response.');
    }
}
