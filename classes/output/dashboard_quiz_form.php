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
 * Form to select quiz instance in quiz dashboard.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\output;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

/**
 * Form to select quiz instance in quiz dashboard.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dashboard_quiz_form extends \moodleform {

    /**
     * Build form for the quiz selection.
     *
     * {@inheritDoc}
     * @see \moodleform::definition()
     */
    public function definition() {

        $mform = $this->_form;

        if (!empty($this->_customdata['quizid'])) {
            $quizid = $this->_customdata['quizid'];
        } else {
            $quizid = 0;
        }

        // Quiz selector.
        $quizzes = array(0 => get_string('selectquiz', 'local_assessfreq'));

        $options = array('onchange' => 'javascript:this.form.submit();');

        $mform->addElement('select', 'quizid', get_string('selectquiz', 'local_assessfreq'), $quizzes, $options);
        $mform->setDefault('quizid', $quizid);

    }
}
