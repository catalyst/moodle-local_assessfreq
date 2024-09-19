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
 * Base source class.
 *
 * @package   local_assessfreq
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq;

/**
 * Abstract class that each source subplugin primary class will extend from to determine consistent factors.
 */
abstract class source_base {

    private static array $instances = [];

    public function __construct() {
        $this->get_required_js();
        $this->get_required_css();
    }

    /**
     * Get the instance of the source class.
     *
     * @return source_base
     */
    public static function get_instance() : source_base {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }

        return self::$instances[$class];
    }

    /**
     * Return the name of the module the source refers to.
     * @return string
     */
    abstract public function get_module() : string;

    /**
     * Return the module table. By default, this is the module name, however some mods use a different table.
     * @return string
     */
    public function get_module_table() : string {
        return $this->get_module();
    }

    /**
     * Return the timelimit field used in the module table.
     * @return string
     */
    public function get_timelimit_field() : string {
        return '';
    }

    /**
     * Return the available/timeopen field used in the module table.
     * @return string
     */
    public function get_open_field() : string {
        return '';
    }

    /**
     * Return the duedate/timeclose field used in the module table.
     * @return string
     */
    public function get_close_field() : string {
        return '';
    }

    /**
     * Return the capability map for the module that users must have before the activity applies to them.
     * @return array
     */
    public function get_user_capabilities() : array {
        return [];
    }

    /**
     * Return the name of the source being rendered.
     * @return string
     */
    abstract public function get_name() : string;

    /**
     * Set up the required JS in the global $PAGE object.
     * @return void
     */
    protected function get_required_js() {
    }

    /**
     * Set up the required CSS in the global $PAGE object.
     * @return void
     */
    protected function get_required_css() {
    }

    /**
     * Given an assess ID and module get its tracking information.
     *
     * @param int $assessid The ID of the assessment.
     * @param bool $limited If limited, only return a subset of data. Otherwise reports can try and render thousands of data points.
     * @return array $tracking Tracking reocrds for the quiz.
     */
    protected function get_tracking(int $assessid, bool $limited = false) : array {
        global $DB;

        $trendlimit = get_config('assessfreqreport_activity_dashboard', 'trendcount');
        $return = [];

        $trends = $DB->get_records(
            'local_assessfreq_trend',
            ['assessid' => $assessid, 'module' => $this->get_module()],
            'timecreated ASC'
        );
        if (!$limited) {
            return $trends;
        }
        $modulus = round(count($trends) / $trendlimit);
        $i = 0;
        if (count($trends) < $trendlimit) {
            return $trends;
        }
        foreach ($trends as $trend) {
            if ($i % $modulus == 0) {
                $return[] = $trend;
            }
            $i++;
        }

        return $return;
    }

    /**
     * Given an assess ID and module get its most recent tracking information.
     *
     * @param int $assessid The ID of the assessment.
     * @return mixed $tracking Tracking reocrds for the quiz.
     */
    protected function get_recent_tracking(int $assessid) {
        global $DB;

        return $DB->get_record_sql("
                SELECT *
                FROM {local_assessfreq_trend}
                WHERE assessid = ?
                AND module = ?
                ORDER BY id DESC
                LIMIT 1
            ",
            [$assessid, $this->get_module()]
        );
    }

}
