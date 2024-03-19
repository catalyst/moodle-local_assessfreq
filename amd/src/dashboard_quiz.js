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
 * @module     local_assessfreq/dashboard_quiz
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';
import * as Str from 'core/str';
import Templates from 'core/templates';
import * as ChartData from 'local_assessfreq/chart_data';
import * as FormModal from 'local_assessfreq/form_modal';
import OverrideModal from 'local_assessfreq/override_modal';
import * as TableHandler from 'local_assessfreq/table_handler';
import * as UserPreference from 'local_assessfreq/user_preferences';
import * as ZoomModal from 'local_assessfreq/zoom_modal';

// Module level variables.

var selectQuizStr = '';
var contextid;
var quizId = 0;
var refreshPeriod = 60;
var counterid;

const cards = [
    {cardId: 'local-assessfreq-quiz-summary-graph', call: 'participant_summary', aspect: true},
    {cardId: 'local-assessfreq-quiz-summary-trend', call: 'participant_trend', aspect: false}
];

/**
 * Function for refreshing the counter.
 *
 * @param {boolean} reset the current count process.
 */
const refreshCounter = (reset = true) => {
    let progressElement = document.getElementById('local-assessfreq-period-progress');

    // Reset the current count process.
    if (reset === true) {
        clearInterval(counterid);
        counterid = null;
        progressElement.setAttribute('style', 'width: 100%');
        progressElement.setAttribute('aria-valuenow', 100);
    }

    // Exit early if there is already a counter running.
    if (counterid) {
        return;
    }

    counterid = setInterval(() => {
        let progressWidthAria = progressElement.getAttribute('aria-valuenow');
        const progressStep = 100 / refreshPeriod;

        if ((progressWidthAria - progressStep) > 0) {
            progressElement.setAttribute('style', 'width: ' + (progressWidthAria - progressStep) + '%');
            progressElement.setAttribute('aria-valuenow', (progressWidthAria - progressStep));
        } else {
            clearInterval(counterid);
            counterid = null;
            progressElement.setAttribute('style', 'width: 100%');
            progressElement.setAttribute('aria-valuenow', 100);
            processDashboard(quizId);
            refreshCounter();
        }
    }, (1000));
};

/**
 * Callback function that is called when a quiz is selected from the form.
 * Starts the processing of the dashboard.
 *
 * @param {int} quiz The quiz Id.
 */
const processDashboard = (quiz) => {
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
        let summaryElement = document.getElementById('local-assessfreq-quiz-summary-card');
        let summarySpinner = summaryElement.getElementsByClassName('overlay-icon-container')[0];
        let tableElement = document.getElementById('local-assessfreq-quiz-table');
        let periodElement = document.getElementById('local-assessfreq-period-container');
        let tableSearchInputElement = document.getElementById('local-assessfreq-quiz-student-table-search');
        let tableSearchResetElement = document.getElementById('local-assessfreq-quiz-student-table-search-reset');
        let tableSearchRowsElement = document.getElementById('local-assessfreq-quiz-student-table-rows');

        let quizLink = document.createElement('a');
        quizLink.href = quizArray.url;
        quizLink.innerHTML = '<i class="fa fa-link fa-flip-vertical fa-fw"></i>';
        titleElement.innerHTML = quizArray.name + '&nbsp;';
        titleElement.appendChild(quizLink);

        // Update page URL with quiz ID, without reloading page so that page navigation and bookmarking works.
        const currentdUrl = new URL(window.location.href);
        const newUrl = currentdUrl.origin + currentdUrl.pathname + '?id=' + quizId;
        history.pushState({}, '', newUrl);

        // Update page title with quiz name.
        Str.get_string('dashboard:quiztitle', 'local_assessfreq', {'quiz': quizArray.name, 'course': quizArray.courseshortname})
        .then((str) => {
            document.title = str;
        }).catch(() => {
            return;
        });

        // Populate quiz summary card with details.
        Templates.render('local_assessfreq/quiz-summary-card-content', quizArray).done((html) => {
            summarySpinner.classList.add('hide');
            let contentcontainer = document.getElementById('local-assessfreq-quiz-summary-card-content');
            Templates.replaceNodeContents(contentcontainer, html, '');
        }).fail(() => {
            Notification.exception(new Error('Failed to load quiz summary template.'));
            return;
        });

        // Show the cards.
        cardsElement.classList.remove('hide');
        trendElement.classList.remove('hide');
        tableElement.classList.remove('hide');
        periodElement.classList.remove('hide');

        ChartData.getCardCharts(quizId);
        TableHandler.getTable(quizId);
        refreshCounter();

        tableSearchInputElement.addEventListener('keyup', TableHandler.tableSearch);
        tableSearchInputElement.addEventListener('paste', TableHandler.tableSearch);
        tableSearchResetElement.addEventListener('click', TableHandler.tableSearchReset);
        tableSearchRowsElement.addEventListener('click', TableHandler.tableSearchRowSet);

        return;
    }).fail(() => {
        Notification.exception(new Error('Failed to get quiz data'));
    });
};

/**
 * Handle processing of refresh and period button actions.
 *
 * @param {Event} event The triggered event for the element.
 */
const refreshAction = (event) => {
    event.preventDefault();
    var element = event.target;

    if (element.closest('button') !== null && element.closest('button').id === 'local-assessfreq-refresh-quiz-dashboard') {
        refreshCounter(true);
        processDashboard(quizId);
    } else if (element.tagName.toLowerCase() === 'a') {
        refreshPeriod = element.dataset.period;
        refreshCounter(true);
        UserPreference.setUserPreference('local_assessfreq_quiz_refresh_preference', refreshPeriod);
    }
};

/**
 * Trigger the zoom graph. Thin wrapper to add extra data to click event.
 *
 * @param {Event} event The triggered event for the element.
 */
const triggerZoomGraph = (event) => {
    let call = event.target.closest('div').dataset.call;
    let params = {'data': JSON.stringify({'quiz': quizId, 'call': call})};
    let method = 'get_quiz_chart';

    ZoomModal.zoomGraph(event, params, method);
};

/**
 * Initialise method for quiz dashboard rendering.
 *
 * @param {int} context The context id.
 * @param {int} quiz The quiz id.
 */
export const init = (context, quiz) => {
    contextid = context;
    FormModal.init(context, processDashboard); // Create modal for quiz selection modal.
    ZoomModal.init(context); // Create the zoom modal.
    OverrideModal.init(context, processDashboard);
    TableHandler.init(
        quizId,
        contextid,
        'local-assessfreq-quiz-student-table',
        'local-assessfreq-quiz-table',
        'get_student_table',
        'local_assessfreq_quiz_table_rows_preference',
        'local-assessfreq-quiz-student-table-search',
        'local_assessfreq_student_table',
        'local_assessfreq_set_table_preference'
    );
    ChartData.init(cards, context, 'get_quiz_chart', 'local_assessfreq/chart');
    Str.get_string('loadingquiztitle', 'local_assessfreq').then((str) => {
        selectQuizStr = str;
    }).catch(() => {
        return;
    }).then(() => {
        if (quiz > 0) {
            quizId = quiz;
            processDashboard(quiz);
        }
    });

    UserPreference.getUserPreference('local_assessfreq_quiz_refresh_preference')
    .then((response) => {
        refreshPeriod = response.preferences[0].value ? response.preferences[0].value : 60;
    })
    .fail(() => {
        Notification.exception(new Error('Failed to get use preference: refresh'));
    });

    // Event handling for refresh and period buttons.
    let refreshElement = document.getElementById('local-assessfreq-period-container');
    refreshElement.addEventListener('click', refreshAction);

    // Set up zoom event listeners.
    let summaryZoom = document.getElementById('local-assessfreq-quiz-summary-graph-zoom');
    summaryZoom.addEventListener('click', triggerZoomGraph);

    let trendZoom = document.getElementById('local-assessfreq-quiz-summary-trend-zoom');
    trendZoom.addEventListener('click', triggerZoomGraph);

};
