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
 * Form to add override for quiz.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/mod/quiz/override_form.php');

/**
 * Form to add override for quiz.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_override_form extends \quiz_override_form {
    /**
     * Constructor.
     * @param object $cm course module object.
     * @param object $quiz the quiz settings object.
     * @param \context $context the quiz context.
     * @param object $override the override being edited, if it already exists.
     * @param null|object $submitteddata The data submitted to the form via ajax.
     */
    public function __construct($cm, $quiz, $context, $override, $submitteddata = null) {

        $this->cm = $cm;
        $this->quiz = $quiz;
        $this->context = $context;
        $this->groupmode = false;
        $this->groupid = 0;
        $this->userid = empty($override->userid) ? 0 : $override->userid;

        \moodleform::__construct(null, null, 'post', '', ['class' => 'ignoredirty'], true, $submitteddata);
    }

    /**
     *
     * {@inheritDoc}
     * @see quiz_override_form::definition()
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
