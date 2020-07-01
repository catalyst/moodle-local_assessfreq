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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

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
     * @return void
     */
    public static function get_frequency_parameters() {
        return new external_function_parameters(array(
            'jsondata' => new external_value(PARAM_RAW, 'The data encoded as a json array')
        ));
    }

    /**
     * Returns event frequency map for all users in site.
     *
     * @param string $jsondata JSON data.
     * @return string JSON response.
     */
    public static function get_frequency($jsondata) {
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Parameter validation.
        self::validate_parameters(
            self::get_frequency_parameters(),
            array('jsondata' => $jsondata)
            );

        // Context validation and permission check.
        $context = context_system::instance();
        self::validate_context($context);
        has_capability('moodle/site:config', $context);

        // Execute API call.
        $data = json_decode($jsondata, true);
        $frequency = new \local_assessfreq\frequency();
        $freqarr = $frequency->get_frequency_array($data['year'], $data['metric'], $data['modules']);


        return json_encode($freqarr);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_frequency_returns() {
        return new external_value(PARAM_RAW, 'Event JSON');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_heat_colors_parameters() {
        return new external_function_parameters(array(
            // If I had params they'd be here, but I don't, so they're not.
        ));
    }

    /**
     * Returns heat map colors.
     * This method doesn't require login or user session update.
     * It also doesn't need any capability check.
     *
     * @return string JSON response.
     */
    public static function get_heat_colors() {
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Execute API call.
        $frequency = new \local_assessfreq\frequency();
        $heatarray = $frequency->get_heat_colors();

        return json_encode($heatarray);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_heat_colors_returns() {
        return new external_value(PARAM_RAW, 'Event JSON');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_process_modules_parameters() {
        return new external_function_parameters(array(
            // If I had params they'd be here, but I don't, so they're not.
        ));
    }

    /**
     * Returns modules enabled for processing along with their module name string.
     *
     * @return string JSON response.
     */
    public static function get_process_modules() {
        \core\session\manager::write_close(); // Close session early this is a read op.

        $modulesandstrings = array('number' => get_string('numberevents', 'local_assessfreq'));

        // Execute API call.
        $frequency = new \local_assessfreq\frequency();
        $processmodules = $frequency->get_process_modules();

        foreach ($processmodules as $module) {
            $modulesandstrings[$module] = get_string('modulename', $module);
        }

        return json_encode($modulesandstrings);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_process_modules_returns() {
        return new external_value(PARAM_RAW, 'Event JSON');
    }


    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_day_events_parameters() {
        return new external_function_parameters(array(
            'jsondata' => new external_value(PARAM_RAW, 'The data encoded as a json array')
        ));
    }

    /**
     * Returns event frequency map for all users in site.
     *
     * @param string $jsondata JSON data.
     * @return string JSON response.
     */
    public static function get_day_events($jsondata) {
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Parameter validation.
        self::validate_parameters(
            self::get_day_events_parameters(),
            array('jsondata' => $jsondata)
            );

        // Context validation and permission check.
        $context = context_system::instance();
        self::validate_context($context);
        has_capability('moodle/site:config', $context);

        // Execute API call.
        $data = json_decode($jsondata, true);
        $frequency = new \local_assessfreq\frequency();
        $freqarr = $frequency->get_day_events($data['date'], $data['modules']);

        return json_encode($freqarr);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_day_events_returns() {
        return new external_value(PARAM_RAW, 'Event JSON');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(array(
            'query' => new external_value(PARAM_TEXT, 'The query to find')
        ));
    }

    /**
     * Returns courses and quizzes in that course that match search data.
     *
     * @param string $jsondata JSON data.
     * @return string JSON response.
     */
    public static function get_courses($query) {
        global $DB;
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Parameter validation.
        self::validate_parameters(
            self::get_courses_parameters(),
            array('query' => $query)
            );

        // Context validation and permission check.
        $context = context_system::instance();
        self::validate_context($context);
        has_capability('moodle/site:config', $context);

        // Execute API call.
        $sql = 'SELECT id, fullname FROM {course} WHERE ' . $DB->sql_like('fullname', ':fullname') . ' AND id <> 1';
        $params = array('fullname' => '%' . $DB->sql_like_escape($query) . '%');
        $courses = $DB->get_records_sql($sql, $params, 0, 11);

        return json_encode(array_values($courses));
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_courses_returns() {
        return new external_value(PARAM_RAW, 'Course result JSON');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_quizzes_parameters() {
        return new external_function_parameters(array(
            'query' => new external_value(PARAM_INT, 'The query to find')
        ));
    }

    /**
     * Returns courses and quizzes in that course that match search data.
     *
     * @param string $jsondata JSON data.
     * @return string JSON response.
     */
    public static function get_quizzes($query) {
        global $DB;
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Parameter validation.
        self::validate_parameters(
            self::get_quizzes_parameters(),
            array('query' => $query)
            );

        // Context validation and permission check.
        $context = context_system::instance();
        self::validate_context($context);
        has_capability('moodle/site:config', $context);

        // Execute API call.
        $params = array('course' => $query);
        $quizzes = $DB->get_records('quiz', $params, 'name ASC', 'id, name');

        return json_encode(array_values($quizzes));
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_quizzes_returns() {
        return new external_value(PARAM_RAW, 'Course result JSON');
    }
}