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
 * Javascript for report card display and processing.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['local_assessfreq/form_modal'],
function(FormModal) {

    /**
     * Module level variables.
     */
    var DashboardQuiz = {};

    const processDashboard = function(course, quiz) {
        window.console.log(course);
        window.console.log(quiz);
    };

    /**
     * Initialise method for quiz dashboard rendering.
     */
    DashboardQuiz.init = function(contextid) {
        FormModal.init(contextid, processDashboard);
    };

    return DashboardQuiz;
});
