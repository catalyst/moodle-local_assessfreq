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
 * Frequency class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

namespace local_assessfreq;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/calendar/lib.php');

/**
 * Frequency class.
 *
 * This class handles data processing to get assessment frequency data
 * used in generating the heat map display for the plugin.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class frequency {

    /**
     * Mapp of event count to heat setting.
     * TODO: Create array from plugin settings.
     *
     * @var array
     */
    private $map = array (
        0 => 0,
        2 => 1,
        4 => 2,
        6 => 3,
        8 => 4,
        10 => 5
    );

    /**
     * Given a count value get the corresponding heat setting.
     *
     * @param int $count The event count.
     * @return int $result The heat setting that relates to the given count.
     */
    private function get_map(int $count) : int {
        $result = 0;

        foreach($this->map as $key => $value) {
            if($count > $key) {
                $result = $value;
            }
        }

        return $result;
    }

    /**
     * Get the raw events for the current user.
     *
     * @return array $events An array of the raw events.
     */
    private function get_events() : array {

        $vault = \core_calendar\local\event\container::get_event_vault();

        // TODO: Get events has a limit of 20 by default need to work around this.
        // TODO: need to filter to a particular user.
        $events = $vault->get_events();

        // get_times
        return $events;

    }

    /**
     * Generate a frequency array of the events.
     * The form of the array is:
     * [yyyy][mm][dd]['number'] = number of events that day.
     *
     * @return array $freqarray The array of even frequencies.
     */
    public function get_frequency_array() : array {
        $freqarray = array();

        // Get the raw events.
        $events = $this->get_events();

        // Iterate through the events, building the frequency array.
        foreach ($events as $event) {
            $eventtimes = $event->get_times();
            $endtime = $eventtimes->get_end_time();
            $year = $endtime->format('Y');
            $month = $endtime->format('n');
            $day = $endtime->format('j');

            // Construct the multidimensional array.
            if (empty($freqarray[$year][$month][$day])) {
                $freqarray[$year][$month][$day] = array('number' => 1, 'heat' => $this->get_map(1));
            } else {
                $freqarray[$year][$month][$day]['number']++;
                $freqarray[$year][$month][$day]['heat'] = $this->get_map($freqarray[$year][$month][$day]['number']);
            }
        }

        return $freqarray;
    }

    /**
     * Get the modules to use in data collection.
     * This is based on plugin configuration.
     *
     * @return array $modules The enabled modules.
     */
    private function get_modules() : array {
        // TODO: Get these from plugin config rather than hard code.
        return array ('assign', 'book', 'choice', 'data', 'feedback', 'forum', 'lesson', 'quiz', 'scorm', 'workshop');
    }

    /**
     * Generate SQL to use to get activity info.
     *
     * @param string $module Activity module to get data for.
     * @return string $sql The generated SQL.
     */
    private function get_sql_query(string $module) : string {
        $sql = 'SELECT cm.id, cm.course, m.name, cm.instance, c.id as contextid, a.duedate
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON cm.module = m.id
            INNER JOIN {context} c ON cm.id = c.instanceid
            INNER JOIN {' . $module . '} a ON cm.instance = a.id
            INNER JOIN {course} course ON cm.course = course.id
                 WHERE m.name = ?
                       AND c.contextlevel = ?
                       AND a.duedate > ?
                       AND cm.visible = ?
                       AND course.visible = ?';

        return $sql;
    }

    /**
     *
     * @param string $sql
     * @param array $params
     * @return \moodle_recordset
     */
    private function get_module_events(string $sql, array $params) : \moodle_recordset {
        global $DB;

        $recordset = $DB->get_recordset_sql($sql, $params);

        return $recordset;
    }

    private function process_module_events(\moodle_recordset $recordset) {

    }

    /**
     *
     */
    private function process_site_events($module) {
        global $DB;

        //

        $enabledmods = $this->get_modules();
        $queries = array();
        $params = array(
            $module,
            CONTEXT_MODULE,
            0,
            1,
            1
        );

        // Itterate through modules and generate sql.
        foreach ($enabledmods as $module) {
            $queries[] = $this->get_sql_query($module);
        }



    }

}
