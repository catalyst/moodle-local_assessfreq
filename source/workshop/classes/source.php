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
 * Main source class.
 *
 * @package   assessfreqsource_workshop
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_workshop;

use local_assessfreq\source_base;

class source extends source_base {

    /**
     * @inheritDoc
     */
    public function get_module() : string {
        return 'workshop';
    }

    /**
     * @inheritDoc
     */
    public function get_name() : string {
        return get_string("source:name", "assessfreqsource_workshop");
    }

    /**
     * @inheritDoc
     */
    public function get_open_field() : string {
        return 'submissionstart';
    }

    /**
     * @inheritDoc
     */
    public function get_close_field() : string {
        return 'submissionend';
    }

    /**
     * @inheritDoc
     */
    public function get_user_capabilities() : array {
        return ['mod/workshop:submit', 'mod/workshop:view'];
    }
}
