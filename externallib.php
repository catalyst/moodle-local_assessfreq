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
 * calendarContainer, spinner
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
    public static function get_frequency_parameters()
    {
        return new external_function_parameters(array(
            'jsondata' => new external_value(PARAM_RAW, 'The data encoded as a json array')
        ));
    }

    /**
     * Returns event frequency map for all users in site.
     *
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

}