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
     * @param string $query The search query.
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
        $sql = 'SELECT id, fullname FROM {course} WHERE ' . $DB->sql_like('fullname', ':fullname', false) . ' AND id <> 1';
        $params = array('fullname' => '%' . $DB->sql_like_escape($query) . '%');
        $courses = $DB->get_records_sql($sql, $params, 0, 11);

        $data = [];
        foreach ($courses as $course) {
            $data[$course->id] = ["id" => $course->id, "fullname" => format_string($course->fullname,
                true, ["context" => $context, "escape" => false])];
        }

        return json_encode(array_values($data));
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
     * @param string $query The search query.
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

        $data = [];
        foreach ($quizzes as $quiz) {
            $data[$quiz->id] = ["id" => $quiz->id, "name" => format_string($quiz->name,
                true, ["context" => $context, "escape" => false])];
        }

        return json_encode(array_values($data));
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_quizzes_returns() {
        return new external_value(PARAM_RAW, 'Quiz result JSON');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_quiz_data_parameters() {
        return new external_function_parameters(array(
            'quizid' => new external_value(PARAM_INT, 'The quiz id to get data for')
        ));
    }

    /**
     * Returns quiz data.
     *
     * @param string $quizid The quiz id to get data for.
     * @return string JSON response.
     */
    public static function get_quiz_data($quizid) {
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Parameter validation.
        self::validate_parameters(
            self::get_quiz_data_parameters(),
            array('quizid' => $quizid)
            );

        // Context validation and permission check.
        $context = context_system::instance();
        self::validate_context($context);
        has_capability('moodle/site:config', $context);

        // Execute API call.
        $quiz = new \local_assessfreq\quiz();
        $quizdata = $quiz->get_quiz_data($quizid);

        return json_encode($quizdata);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_quiz_data_returns() {
        return new external_value(PARAM_RAW, 'Quiz data result JSON');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function set_table_preference_parameters() {
        return new external_function_parameters(array(
            'tableid' => new external_value(PARAM_ALPHANUMEXT, 'The table id to set the preference for'),
            'preference' => new external_value(PARAM_ALPHAEXT, 'The table preference to set'),
            'values' => new external_value(PARAM_RAW, 'The values to set as JSON'),
        ));
    }

    /**
     * Returns quiz data.
     *
     * @param string $tableid The table id to set the preference for.
     * @param string $preference The name of the preference to set.
     * @param string $values The values to set for the preference, encoded as JSON.
     * @return string JSON response.
     */
    public static function set_table_preference($tableid, $preference, $values) {
        global $SESSION;

        // Parameter validation.
        self::validate_parameters(
            self::set_table_preference_parameters(),
            array('tableid' => $tableid, 'preference' => $preference, 'values' => $values)
            );

        // Context validation and permission check.
        $context = context_system::instance();
        self::validate_context($context);
        has_capability('moodle/site:config', $context);

        // Set up the initial preference template.
        if (isset($SESSION->flextable[$tableid])) {
            $prefs = $SESSION->flextable[$tableid];
        } else {
            $prefs = array(
                'collapse' => array(),
                'sortby'   => array(),
                'i_first'  => '',
                'i_last'   => '',
                'textsort' => array(),
            );
        }

        // Set or reset the preferences.
        if ($preference == 'reset') {
            $prefs = array(
                'collapse' => array(),
                'sortby'   => array(),
                'i_first'  => '',
                'i_last'   => '',
                'textsort' => array(),
            );
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
    public static function process_override_form_parameters() {
        return new external_function_parameters(
            array(
                'jsonformdata' => new external_value(PARAM_RAW, 'The data from the create copy form, encoded as a json array'),
                'quizid' => new external_value(PARAM_INT, 'The quiz id to processs the override for')
            )
            );
    }

    /**
     * Submit the quiz override form.
     *
     * @param string $jsonformdata The data from the form, encoded as a json array.
     * @param int $quizid The quiz id to add an override for.
     * @throws moodle_exception
     * @return string
     */
    public static function process_override_form($jsonformdata, $quizid) {
        global $DB;

        // Release session lock.
        \core\session\manager::write_close();

        // We always must pass webservice params through validate_parameters.
        $params = self::validate_parameters(
            self::process_override_form_parameters(),
            array('jsonformdata' => $jsonformdata, 'quizid' => $quizid)
            );

        $formdata = json_decode($params['jsonformdata']);

        $submitteddata = array();
        parse_str($formdata, $submitteddata);

        // Check access.
        $quizdata = new \local_assessfreq\quiz();
        $context = $quizdata->get_quiz_context($quizid);
        self::validate_context($context);
        has_capability('mod/quiz:manageoverrides', $context);

        // Check if we have an existing override for this user.
        $override = $DB->get_record('quiz_overrides', array('quiz' => $quizid, 'userid' => $submitteddata['userid']));

        // Submit the form data.
        $quiz = $DB->get_record('quiz', array('id' => $quizid), '*', MUST_EXIST);
        $cm = get_course_and_cm_from_cmid($context->instanceid, 'quiz')[1];
        $mform = new \local_assessfreq\form\quiz_override_form($cm, $quiz, $context, $override, $submitteddata);

        $mdata = $mform->get_data();

        if ($mdata) {
            $params = array(
                'context' => $context,
                'other' => array(
                    'quizid' => $quizid
                ),
                'relateduserid' => $mdata->userid
            );
            $mdata->quiz = $quizid;

            if (!empty($override->id)) {
                $mdata->id = $override->id;
                $DB->update_record('quiz_overrides', $mdata);

                // Determine which override updated event to fire.
                $params['objectid'] = $override->id;
                $event = \mod_quiz\event\user_override_updated::create($params);
                // Trigger the override updated event.
                $event->trigger();
            } else {
                unset($mdata->id);
                $mdata->id = $DB->insert_record('quiz_overrides', $mdata);

                // Determine which override created event to fire.
                $params['objectid'] = $mdata->id;
                $event = \mod_quiz\event\user_override_created::create($params);
                // Trigger the override created event.
                $event->trigger();
            }

        } else {
            throw new moodle_exception('submitoverridefail', 'local_assessfreq');
        }

        return json_encode(array('overrideid' => $mdata->id));
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function process_override_form_returns() {
        return new external_value(PARAM_RAW, 'JSON response.');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_system_timezone_parameters() {
        return new external_function_parameters(array(
            // If I had params they'd be here, but I don't, so they're not.
        ));
    }

    /**
     * Returns system timezone.
     * This method doesn't require login or user session update.
     * It also doesn't need any capability check.
     *
     * @return string Timezone.
     */
    public static function get_system_timezone() {
        \core\session\manager::write_close(); // Close session early this is a read op.
        global $DB;

        // Execute API call.
        $timezone = $DB->get_field('config', 'value', array('name' => 'timezone'), MUST_EXIST);

        return $timezone;
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function get_system_timezone_returns() {
        return new external_value(PARAM_TEXT, 'Timezone');
    }

    /**
     * Returns description of method parameters.
     *
     * @return void
     */
    public static function get_inprogress_counts_parameters() {
        return new external_function_parameters(array(
            // If I had params they'd be here, but I don't, so they're not.
        ));
    }

    /**
     * Returns quiz summary data for upcomming and inprogress quizzes.
     *
     * @return string JSON response.
     */
    public static function get_inprogress_counts() {
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Context validation and permission check.
        $context = context_system::instance();
        self::validate_context($context);
        has_capability('moodle/site:config', $context);

        // Execute API call.
        $quiz = new \local_assessfreq\quiz();
        $now = time();
        $quizdata = $quiz->get_inprogress_counts($now);

        return json_encode($quizdata);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_description
     */
    public static function get_inprogress_counts_returns() {
        return new external_value(PARAM_RAW, 'JSON quiz count data');
    }
}
