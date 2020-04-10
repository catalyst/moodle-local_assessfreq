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
 * Block assessfreq trigger Web Service.
 *
 * calendarContainer, spinner
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");

/**
 * Block assessfreq trigger Web Service
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_assessfreq_external extends external_api {


    /**
     * Returns description of method parameters.
     * @return void
     */
    public static function get_frequency_parameters() {
        return new external_function_parameters(
            array(
                // If I had any parameters, they would be described here. But I don't have any, so this array is empty.
            )
        );
    }

    /**
     * Returns event frequency map.
     *
     */
    public static function get_frequency() {
        \core\session\manager::write_close(); // Close session early this is a read op.

        // Execute API call.
        $frequency = new \local_assessfreq\frequency();
        $freqarr = $frequency->get_frequency_array();

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
     * @return void
     */
    public static function get_strings_parameters() {
        return new external_function_parameters(
            array(
                // If I had any parameters, they would be described here. But I don't have any, so this array is empty.
            )
        );
    }

    /**
     * Returns strings used in heat map display.
     * Sending an array of all the required strings
     * is much more efficent that making an AJAX call
     * per string.
     *
     */
    public static function get_strings() {
        \core\session\manager::write_close(); // Close session early this is a read op.

        $stringarr = array(
            'days' => array(
                '0' => get_string('sun', 'calendar'),
                '1' => get_string('mon', 'calendar'),
                '2' => get_string('tue', 'calendar'),
                '3' => get_string('wed', 'calendar'),
                '4' => get_string('thu', 'calendar'),
                '5' => get_string('fri', 'calendar'),
                '6' => get_string('sat', 'calendar'),
            ),
            'months' => array(
                '0' => get_string('jan', 'local_assessfreq'),
                '1' => get_string('feb', 'local_assessfreq'),
                '2' => get_string('mar', 'local_assessfreq'),
                '3' => get_string('apr', 'local_assessfreq'),
                '4' => get_string('may', 'local_assessfreq'),
                '5' => get_string('jun', 'local_assessfreq'),
                '6' => get_string('jul', 'local_assessfreq'),
                '7' => get_string('aug', 'local_assessfreq'),
                '8' => get_string('sep', 'local_assessfreq'),
                '9' => get_string('oct', 'local_assessfreq'),
                '10' => get_string('nov', 'local_assessfreq'),
                '11' => get_string('dec', 'local_assessfreq'),
            )
        );

        return json_encode($stringarr);
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_strings_returns() {
        return new external_value(PARAM_RAW, 'Language string JSON');
    }
}