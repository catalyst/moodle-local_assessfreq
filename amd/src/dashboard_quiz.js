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

define(['local_assessfreq/form_modal', 'core/ajax', 'core/notification', 'core/str', 'core/fragment', 'core/templates'],
function(FormModal, Ajax, Notification, Str, Fragment, Templates) {

    /**
     * Module level variables.
     */
    var DashboardQuiz = {};
    var selectQuizStr = '';
    var contextid;
    var quizId = 0;

    const cards = [
        {cardId: 'local-assessfreq-quiz-summary-graph', call: 'participant_summary'},
        {cardId: 'local-assessfreq-quiz-summary-trend', call: 'participant_trend'}
    ];

    /**
     * For each of the cards on the dashbaord get their corresponding chart data.
     * Data is based on the year variable from the corresponding dropdown.
     * Chart data is loaded via ajax.
     *
     */
    const getCardCharts = function() {
        cards.forEach((cardData) => {
            let cardElement = document.getElementById(cardData.cardId);
            let spinner = cardElement.getElementsByClassName('overlay-icon-container')[0];
            let chartbody = cardElement.getElementsByClassName('chart-body')[0];
            let params = {'data': JSON.stringify({'quiz' : quizId, 'call': cardData.call})};

            spinner.classList.remove('hide'); // Show sinner if not already shown.
            Fragment.loadFragment('local_assessfreq', 'get_quiz_chart', contextid, params)
            .done((response) => {
                var context = { 'withtable' : true, 'chartdata' : response };
                Templates.render('core/chart', context).done((html, js) => {
                    spinner.classList.add('hide'); // Hide spinner if not already hidden.
                    // Load card body.
                    Templates.replaceNodeContents(chartbody, html, js);
                }).fail(() => {
                    Notification.exception(new Error('Failed to load chart template.'));
                    return;
                });
                return;
            }).fail(() => {
                Notification.exception(new Error('Failed to load card.'));
                return;
            });
        });
    };

    /**
     * Callback function that is called when a quiz is selected from the form.
     * Starts the processing of the dashbaord.
     */
    const processDashboard = function(quiz) {
        quizId = quiz;
        let titleElement = document.getElementById('local-assessfreq-quiz-title');
        titleElement.innerHTML = selectQuizStr;
        // Get quiz data.
        Ajax.call([{
            methodname: 'local_assessfreq_get_quiz_data',
            args: {
                quizid: quiz
            },
        }])[0].then((response) => {

            let quizArray = JSON.parse(response);
            let cardsElement = document.getElementById('local-assessfreq-quiz-dashboard-cards-deck');
            let trendElement = document.getElementById('local-assessfreq-quiz-dashboard-participant-trend-deck');
            let summaryElement = document.getElementById("local-assessfreq-quiz-summary-card");
            let summarySpinner = summaryElement.getElementsByClassName('overlay-icon-container')[0];

            titleElement.innerHTML = quizArray.name;

            // Update page URL with quiz ID, without reloading page so that page navigation and bookmarking works.
            const currentdUrl = new URL(window.location.href);
            const newUrl = currentdUrl.origin + currentdUrl.pathname + '?id=' + quizId;
            history.pushState({}, '', newUrl);

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
            getCardCharts();
            // TODO: Set up auto refresh of cards.
            // TODO: Cancel autorefresh of cards while quiz in changing.

            window.console.log(quizArray);
            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get quiz data'));
        });
    };

    /**
     * Initialise method for quiz dashboard rendering.
     */
    DashboardQuiz.init = function(context) {
        contextid = context;
        FormModal.init(context, processDashboard);

        Str.get_string('loadingquiztitle', 'local_assessfreq').then((str) => {
            selectQuizStr = str;
        }).catch(() => {
            Notification.exception(new Error('Failed to load string: loadingquiz'));
        });
    };

    return DashboardQuiz;
});
