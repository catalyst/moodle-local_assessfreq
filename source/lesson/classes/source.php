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
 * Main source file.
 *
 * @package   assessfreqsource_lesson
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_lesson;

use local_assessfreq\source_base;

class source extends source_base {

    /**
     * @inheritDoc
     */
    public function get_module() : string {
        return 'lesson';
    }

    /**
     * @inheritDoc
     */
    public function get_name(): string {
        return get_string("source:name", "assessfreqsource_lesson");
    }

    /**
     * @inheritDoc
     */
    public function get_timelimit_field() : string {
        return 'timelimit';
    }

    /**
     * @inheritDoc
     */
    public function get_open_field() : string {
        return 'available';
    }

    /**
     * @inheritDoc
     */
    public function get_close_field() : string {
        return 'deadline';
    }

    /**
     * @inheritDoc
     */
    public function get_user_capabilities() : array {
        return ['mod/lesson:view'];
    }
}
