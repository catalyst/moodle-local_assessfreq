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
 * @package    assessfreqreport_summary_graphs
 * @copyright  2024 Simon Thornett <simon.thornett@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Returns the name of the user preferences as well as the details this plugin uses.
 *
 * @return array
 */
function assessfreqreport_summary_graphs_user_preferences() : array {

    $preferences['assessfreqreport_summary_graphs_year_preference'] = [
        'null' => NULL_NOT_ALLOWED,
        'default' => date('Y'),
        'type' => PARAM_INT,
    ];

    return $preferences;
}
