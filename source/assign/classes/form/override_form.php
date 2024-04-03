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
 * Form to add override for assignment.
 *
 * @package   assessfreqsource_assign
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_assign\form;

use context;
use moodleform;
use assign_override_form;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->dirroot . '/mod/assign/override_form.php');

class override_form extends assign_override_form {

    /**
     * Constructor.
     * @param object $cm course module object.
     * @param object $assign the quiz settings object.
     * @param context $context the quiz context.
     * @param object $override the override being edited, if it already exists.
     * @param null|object $submitteddata The data submitted to the form via ajax.
     */
    public function __construct($cm, $assign, $context, $override, $submitteddata = null) {

        $this->cm = $cm;
        $this->assign = $assign;
        $this->context = $context;
        $this->groupmode = false;
        $this->groupid = 0;
        $this->userid = empty($override->userid) ? 0 : $override->userid;

        moodleform::__construct(null, null, 'post', '', ['class' => 'ignoredirty'], true, $submitteddata);
    }

    /**
     *
     * {@inheritDoc}
     * @see assign_override_form::definition()
     */
    protected function definition() {
        parent::definition();
        $mform = $this->_form;
        $mform->freeze('userid');
        $mform->removeElement('resetbutton');
        $mform->removeElement('buttonbar');
        $this->add_action_buttons();
    }
}
