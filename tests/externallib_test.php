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
 * This file contains the class that handles testing of the local assess webservice class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->dirroot . '/local/assessfreq/externallib.php');

use local_assessfreq\frequency;

/**
 * This file contains the class that handles testing of the local assess webservice class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */
class local_assessfreq_external_testcase extends advanced_testcase {

    /**
     * Set up conditions for tests.
     */
    public function setUp() {
        $this->resetAfterTest();
    }

    /**
     * Test ajax getting of event data..
     */
    public function test_get_frequency() {
        global $DB;
        $this->setAdminUser();

        $data = new \stdClass;
        $data->year  = 2020;
        $data->metric = 'assess'; // Can be assess or students.
        $data->modules = 'all';

        $jsondata = json_encode($data);
        $returnvalue = local_assessfreq_external::get_frequency($jsondata);

        $returnjson = external_api::clean_returnvalue(core_backup_external::submit_copy_form_returns(), $returnvalue);
        $eventarr = json_decode($returnjson, true);

        error_log(print_r($eventarr, true));

    }
}
