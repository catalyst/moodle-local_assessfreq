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
 * Javascript for quizzes in progress display and processing.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax'],
function(Ajax) {

    /**
     * Module level variables.
     */
    var DashboardQuizInprogress = {};
    var contextid;

    /**
     * Starts the processing of the dashboard.
     */
    const processDashboard = function() {
        // Get summary quiz data.
        Ajax.call([{
            methodname: 'local_assessfreq_get_quiz_summaries',
            args: {},
        }])[0].then((response) => {
            let quizSummary = JSON.parse(response);
            let titleElement = document.getElementById('local-assessfreq-quiz-inprogress-title');
            let controlsElement = document.getElementById('local-assessfreq-period-container');
            let summaryElement = document.getElementById('local-assessfreq-quiz-dashboard-inprogress-summary');
            window.console.log(quizSummary);
            window.console.log(summaryElement);

            // Show the cards.
            controlsElement.classList.remove('hide');
            summaryElement.classList.remove('hide');
            titleElement.classList.add('hide');

            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get quiz summary data'));
        });
    };

    /**
     * Initialise method for quizzes in progress dashboard rendering.
     */
    DashboardQuizInprogress.init = function(context) {
        contextid = context;
        window.console.log(contextid);

        processDashboard();

    };

    return DashboardQuizInprogress;
});
