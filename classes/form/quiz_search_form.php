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
 * Form to search for quizzes.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Form to search for quizzes.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_search_form extends \moodleform {

    /**
     * Build form for the broadcast message.
     *
     * {@inheritDoc}
     * @see \moodleform::definition()
     */
    public function definition() {
        $mform = $this->_form;

        // Form heading.
        $mform->addElement('html',
            \html_writer::div(get_string('searchquizform', 'local_assessfreq'), 'form-description mb-3'));

        $courseoptions = array(
            'multiple' => false,
            'placeholder' =>  get_string('entercourse', 'local_assessfreq'),
            'noselectionstring' => get_string('nocourse', 'local_assessfreq'),
            'ajax' => 'local_assessfreq/course_selector',
        );
        $mform->addElement('autocomplete', 'courses', get_string('course', 'local_assessfreq'), array(), $courseoptions);

        $mform->addElement('hidden', 'coursechoice', '0');
        $mform->setType('coursechoice', PARAM_INT);

        $selectoptions = array(
            1 => get_string('selectcourse', 'local_assessfreq'),
            2 => get_string('loadingquiz', 'local_assessfreq'),
        );
        $mform->addElement('select', 'quiz',
            get_string('quiz', 'local_assessfreq'),
            $selectoptions);
        $mform->disabledIf('quiz', 'coursechoice', 'eq', '0');

        $btnstring = get_string('selectquiz', 'local_assessfreq');
        $this->add_action_buttons(true, $btnstring);

    }
}
