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

use local_assessfreq\frequency;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . "/externallib.php");
require_once('lib.php');

/**
 * Local assessfreq Web Service.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessfreqreport_heatmap_external extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function get_day_events_parameters() : external_function_parameters {
        return new external_function_parameters([
            'jsondata' => new external_value(PARAM_RAW, 'The data encoded as a json array'),
        ]);
    }

    /**
     * Returns event frequency map for all users in site.
     *
     * @param string $jsondata JSON data.
     * @return string JSON response.
     */
    public static function get_day_events(string $jsondata) : string {
        global $PAGE;
        // Parameter validation.
        self::validate_parameters(
            self::get_day_events_parameters(),
            ['jsondata' => $jsondata]
        );

        // Execute API call.
        $data = json_decode($jsondata, true);
        $frequency = new frequency();
        $freqarr = $frequency->get_day_events(
            (int) $data['courseid'],
            $data['date'],
            json_decode(get_user_preferences('assessfreqreport_heatmap_modules_preference', '["all"]'), true)
        );

        foreach ($freqarr as &$freq) {

            // Set default to end of day in case of empty end time.
            $timeend = new DateTime("{$data['date']} 23:59:59");
            if ($freq->timeend) {
                $timeend->setTimestamp($freq->timeend);
            }

            // Set default to start of day in case of empty start time.
            $timestart = new DateTime("{$data['date']} 00:00:00");
            if ($freq->timestart) {
                $timestart->setTimestamp($freq->timestart);
            }
            $timediff = $timestart->diff(new DateTime($timestart->format('Y-m-d')));
            $minutessincestart = $timediff->h * 60 + $timediff->i;
            // If the event started on a different day, set the timestart to the begining of the day.
            if ($timestart->format('d') != $timeend->format('d')) {
                $timestart = new DateTime($data['date']);
                $minutessincestart = 0;
            }
            $length = ($timeend->getTimestamp() - $timestart->getTimestamp()) / 60;
            $freq->width = round($length / 14.4, 2);
            $freq->date = $timeend->format('d-m');
            $freq->start = $timestart->format('d-m H:i');
            $freq->end = $timeend->format('d-m H:i');
            $freq->leftmargin = round($minutessincestart / 14.4, 2);
        }

        return json_encode($freqarr);
    }

    /**
     * Returns description of method result value
     * @return external_value
     */
    public static function get_day_events_returns() : external_value {
        return new external_value(PARAM_RAW, 'Event JSON');
    }
}
