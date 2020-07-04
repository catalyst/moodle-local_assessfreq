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
 * Quiz data class.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq;

defined('MOODLE_INTERNAL') || die();

/**
 * Quiz data class.
 *
 * This class handles data processing to get quiz data.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz {

    /**
     * Get override info for a paricular quiz.
     * Data returned is:
     * Number of users with overrides in Quiz,
     * Ealiest override start,
     * Latest override end.
     *
     * @param int $quizid The ID of the quiz to get override data for.
     * @param \context_module $context The context object of the quiz.
     * @return \stdClass $overrideinfo Information about quiz overrides.
     */
    private function get_quiz_override_info(int $quizid, \context_module $context): \stdClass {
        global $DB;

        $capabilities = array('mod/quiz:attempt', 'mod/quiz:view');
        $overrideinfo = new \stdClass();
        $users = array();
        $start = 0;
        $end = 0;

        $overrides = $DB->get_records('mdl_quiz_overrides', array(), '', 'id, userid, timeopen, timeclose');

        foreach ($overrides as $override) {

            if (!has_all_capabilities($capabilities, $context, $override->userid)) {
                continue; // Don't count users who can't access the quiz.
            }

            $users[] = $override->userid;

            if ($override->timeclose > $end) {
                $end = $override->timeclose;
            }

            if ($start == 0) {
                $start = $override->timeopen;
            } else if ($override->timeopen < $start) {
                $start = $override->timeopen;
            }
        }

        $users = count(array_unique($users));

        return $overrideinfo;
    }

    /**
     * Method returns data about a quiz.
     * Data returned is:
     * Quiz name,
     * Quiz start time,
     * Quiz end time,
     * Earliest participant start time (override),
     * Latest participant end time (override),
     * Total participants taking the quiz,
     * Number participants with overrides in quiz,
     * Quiz link.
     *
     * @param int $quizid ID of the quiz to get data for.
     * @return \stdClass $quizdata The retrieved quiz data.
     */
    public function get_quiz_data(int $quizid): \stdClass {
        global $DB;
        $quizdata = new \stdClass();

        $params = array('module' => 'quiz', 'quiz' => $quizid);
        $sql = 'SELECT cm.id
                  FROM {course_modules} cm
            INNER JOIN {modules} m ON cm.module = m.id
            INNER JOIN {quiz} q ON cm.instance = q.id AND cm.course = q.course
                 WHERE m.name = :module
                       AND q.id = :quiz';
        $cmid = $DB->get_field_sql($sql, $params);
        $context = \context_module::instance($cmid);

        error_log(print_r($context, true));

        return $quizdata;

    }

    public function get_quiz_question_types(int $quizid): array {
        $questiontypes = array();

        return $questiontypes;
    }
}
