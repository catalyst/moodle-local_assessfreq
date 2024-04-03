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
 * Form to search for activities.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqreport_activity_dashboard\form;

use html_writer;
use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Form to search for activities.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_form extends moodleform {

    /**
     * Build form for the broadcast message.
     *
     * {@inheritDoc}
     * @see moodleform::definition
     */
    public function definition() {
        global $PAGE;

        $mform = $this->_form;
        $mform->disable_form_change_checker();

        // Form heading.
        $mform->addElement(
            'html',
            html_writer::div(get_string('form:searchactivityform', 'assessfreqreport_activity_dashboard'), 'form-description mb-3')
        );

        if ($PAGE->course->id == SITEID) {
            $courseoptions = [
                'multiple' => false,
                'placeholder' => get_string('form:entercourse', 'assessfreqreport_activity_dashboard'),
                'noselectionstring' => get_string('form:nocourse', 'assessfreqreport_activity_dashboard'),
                'ajax' => 'local_assessfreq/course_selector',
                'casesensitive' => false,
            ];
            $mform->addElement('autocomplete', 'courses', get_string('course'), [], $courseoptions);

            $mform->addElement('hidden', 'coursechoice', '0');
            $selectoptions = [
                0 => get_string('form:selectcourse', 'assessfreqreport_activity_dashboard'),
                -1 => get_string('form:loadingactivity', 'assessfreqreport_activity_dashboard'),
            ];
        } else {
            $mform->addElement(
                'html',
                html_writer::div($PAGE->course->fullname, 'form-description mb-3')
            );
            $mform->addElement('hidden', 'coursechoice', $PAGE->course->id);

            $selectoptions = [
                -1 => get_string('form:loadingactivity', 'assessfreqreport_activity_dashboard'),
            ];
        }
        $mform->setType('coursechoice', PARAM_INT);

        $mform->addElement(
            'select',
            'activity',
            get_string('form:activity', 'assessfreqreport_activity_dashboard'),
            $selectoptions
        );
        $mform->disabledIf('activity', 'coursechoice', 'eq', '0');

        $btnstring = get_string('form:selectactivity', 'assessfreqreport_activity_dashboard');
        $this->add_action_buttons(true, $btnstring);
    }
}
