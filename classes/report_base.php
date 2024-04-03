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
 * Base report class.
 *
 * @package   local_assessfreq
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq;

/**
 * Abstract class that each report subplugin primary class will extend from to determine consistent factors.
 */
abstract class report_base {

    private static array $instances = [];

    public function __construct() {
        $this->get_required_js();
        $this->get_required_css();
    }

    /**
     * Get the instance of the report class.
     *
     * @return report_base
     */
    public static function get_instance() : report_base {
        $class = static::class;
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new static();
        }

        return self::$instances[$class];
    }

    /**
     * Return the name of the tab being rendered.
     * @return string
     */
    abstract public function get_name() : string;

    /**
     * Return the weight of the tab which is used to determine the loading order with the highest first.
     * @return int
     */
    abstract public function get_tab_weight() : int;

    /**
     * Get the contents of the page as a string of HTML (template).
     *
     * @return object
     */

    abstract public function get_contents() : string;

    /**
     * Get the anchor link to use for the tabs.
     *
     * @return string
     */
    abstract public function get_tablink() : string;

    /**
     * Check if the report is visible to the user.
     *
     * @return bool
     */
    public function has_access() : bool {
        return false;
    }

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
}
