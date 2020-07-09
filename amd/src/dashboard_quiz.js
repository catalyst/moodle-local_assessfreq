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

define(['local_assessfreq/form_modal', 'core/ajax', 'core/notification'],
function(FormModal, Ajax, Notification) {

    /**
     * Module level variables.
     */
    var DashboardQuiz = {};

    /**
     * Callback function that is called when a quiz is selected from the form.
     * Starts the processing of the dashbaord.
     */
    const processDashboard = function(quiz) {
        // Get quiz data.
        Ajax.call([{
            methodname: 'local_assessfreq_get_quiz_data',
            args: {
                quizid: quiz
            },
        }])[0].then((response) => {
            // TODO: Display quiz loading title while getting ajax data.

            let quizArray = JSON.parse(response);
            let titleElement = document.getElementById('local-assessfreq-quiz-title');
            let cardsElement = document.getElementById('local-assessfreq-quiz-dashboard-cards-deck');
            let trendElement = document.getElementById('local-assessfreq-quiz-dashboard-participant-trend-deck');
            let summaryElement = document.getElementById("local-assessfreq-quiz-summary-card");
            let summarySpinner = summaryElement.getElementsByClassName('overlay-icon-container')[0];

            titleElement.innerHTML = quizArray.name;

            // Populate quiz summary card with details.
            document.getElementById('quiz-time-open').innerHTML = quizArray.timeopen;
            document.getElementById('quiz-time-close').innerHTML = quizArray.timeclose;
            document.getElementById('quiz-time-limit').innerHTML = quizArray.timelimit;
            document.getElementById('quiz-time-earlyopen').innerHTML = quizArray.earlyopen;
            document.getElementById('quiz-time-lateclose').innerHTML = quizArray.lateclose;
            document.getElementById('quiz-participants').innerHTML = quizArray.participants;
            document.getElementById('quiz-participants-override').innerHTML = quizArray.overrideparticipants;
            document.getElementById('quiz-question-number').innerHTML = quizArray.questioncount;
            document.getElementById('quiz-question-types').innerHTML = quizArray.typecount;

            // Show the cards.
            cardsElement.classList.remove('hide');
            trendElement.classList.remove('hide');
            summarySpinner.classList.add('hide');

            window.console.log(quizArray);
            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get quiz data'));
        });
    };

    /**
     * Initialise method for quiz dashboard rendering.
     */
    DashboardQuiz.init = function(contextid) {
        FormModal.init(contextid, processDashboard);
    };

    return DashboardQuiz;
});
