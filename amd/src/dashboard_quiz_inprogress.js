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

define([
    'jquery',
    'core/ajax',
    'core/fragment',
    'core/notification',
    'core/str',
    'core/templates',
    'local_assessfreq/chart_data',
    'local_assessfreq/table_handler',
    'local_assessfreq/user_preferences',
    'local_assessfreq/zoom_modal',
], function($,
            Ajax,
            Fragment,
            Notification,
            Str,
            Templates,
            ChartData,
            TableHandler,
            UserPreference,
            ZoomModal) {

    /**
     * Module level variables.
     */
    var DashboardQuizInprogress = {};
    var contextid;
    var refreshPeriod = 60;
    var counterid;
    var tablesort = 'name_asc';
    var hoursAhead = 0;
    var hoursBehind = 0;

    /**
     * Hours filter array.
     *
     * @type {array} Title to display on modal.
     */
    var hoursFilter;

    const cards = [
        {cardId: 'local-assessfreq-quiz-summary-upcomming-graph', call: 'upcomming_quizzes', aspect: true},
        {cardId: 'local-assessfreq-quiz-summary-inprogress-graph', call: 'all_participants_inprogress', aspect: true}
    ];

    /**
     * Function for refreshing the counter.
     *
     * @param {boolean} reset the current count process.
     */
    const refreshCounter = function(reset = true) {
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
                processDashboard();
                refreshCounter();
            }
        }, (1000));
    };

    /**
     * Starts the processing of the dashboard.
     */
    const processDashboard = function() {
        // Get summary quiz data.
        Ajax.call([{
            methodname: 'local_assessfreq_get_inprogress_counts',
            args: {},
        }])[0].then((response) => {
            let quizSummary = JSON.parse(response);
            let summaryElement = document.getElementById('local-assessfreq-quiz-dashboard-inprogress-summary-card');
            let summarySpinner = summaryElement.getElementsByClassName('overlay-icon-container')[0];
            let tableSearchInputElement = document.getElementById('local-assessfreq-quiz-inprogress-table-search');
            let tableSearchResetElement = document.getElementById('local-assessfreq-quiz-inprogress-table-search-reset');
            let tableSearchRowsElement = document.getElementById('local-assessfreq-quiz-inprogress-table-rows');
            let tableSortElement = document.getElementById('local-assessfreq-inprogress-table-sort');

            summaryElement.classList.remove('hide'); // Show the card.

            // Populate summary card with details.
            Templates.render('local_assessfreq/quiz-dashboard-inprogress-summary-card-content', quizSummary)
            .done((html) => {
                summarySpinner.classList.add('hide');

                let contentcontainer = document.getElementById('local-assessfreq-quiz-dashboard-inprogress-summary-card-content');
                Templates.replaceNodeContents(contentcontainer, html, '');
            }).fail(() => {
                Notification.exception(new Error('Failed to load quiz counts template.'));
                return;
            });

            hoursFilter = [hoursAhead, hoursBehind];
            ChartData.getCardCharts(0, hoursFilter);
            TableHandler.getTable(0, hoursFilter, tablesort);
            refreshCounter();

            // Table event listeners.
            tableSearchInputElement.addEventListener('keyup', TableHandler.tableSearch);
            tableSearchInputElement.addEventListener('paste', TableHandler.tableSearch);
            tableSearchResetElement.addEventListener('click', TableHandler.tableSearchReset);
            tableSearchRowsElement.addEventListener('click', TableHandler.tableSearchRowSet);
            tableSortElement.addEventListener('click', TableHandler.tableSortButtonAction);

            $('[data-toggle="tooltip"]').tooltip();

            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get quiz summary counts'));
        });
    };

    /**
     * Handle processing of refresh and period button actions.
     *
     * @param {Event} event The triggered event for the element.
     */
    const refreshAction = function(event) {
        event.preventDefault();
        var element = event.target;

        if (element.closest('button') !== null && element.closest('button').id === 'local-assessfreq-refresh-quiz-dashboard') {
            refreshCounter(true);
            processDashboard();
        } else if (element.tagName.toLowerCase() === 'a') {
            let refreshElement = document.getElementById('local-assessfreq-period-container');
            let actionButton = refreshElement.getElementsByClassName('dropdown-toggle')[0];
            actionButton.textContent = element.innerHTML;

            let activeoptions = refreshElement.getElementsByClassName('active');

            // Fix active classes.
            for (var i = 0; i < activeoptions.length; i++) {
                activeoptions[i].classList.remove('active');
            }
            element.classList.add('active');

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
    const triggerZoomGraph = function(event) {
        let call = event.target.closest('div').dataset.call;
        let params = {'data': JSON.stringify({'call': call, 'hoursahead': hoursAhead, 'hoursbehind': hoursBehind})};
        let method = 'get_quiz_inprogress_chart';

        ZoomModal.zoomGraph(event, params, method);
    };

    /**
     * Process the hours ahead event from the in progress quizzes table.
     *
     * @param {Event} event The triggered event for the element.
     */
    const quizzesAheadSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let hours = event.target.dataset.metric;
            let activeoptions = document.getElementById('local-assessfreq-quiz-student-table-hoursahead')
                .getElementsByClassName('active');

            // Fix active classes.
            for (var i = 0; i < activeoptions.length; i++) {
                activeoptions[i].classList.remove('active');
            }
            event.target.classList.add('active');
            UserPreference.setUserPreference('local_assessfreq_quizzes_inprogress_table_hoursahead_preference', hours)
                .then(() => {
                    hoursAhead = hours;
                    processDashboard(); // Reload the table.
                })
                .fail(() => {
                    Notification.exception(new Error('Failed to update user preference: hours ahead'));
                });
        }
    };

    /**
     * Process the hours behind event from the in progress quizzes table.
     *
     * @param {Event} event The triggered event for the element.
     */
    const quizzesBehindSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let hours = event.target.dataset.metric;
            let activeoptions = document.getElementById('local-assessfreq-quiz-student-table-hoursbehind')
                .getElementsByClassName('active');

            // Fix active classes.
            for (var i = 0; i < activeoptions.length; i++) {
                activeoptions[i].classList.remove('active');
            }
            event.target.classList.add('active');
            UserPreference.setUserPreference('local_assessfreq_quizzes_inprogress_table_hoursbehind_preference', hours)
                .then(() => {
                    hoursBehind = hours;
                    processDashboard(); // Reload the table.
                })
                .fail(() => {
                    Notification.exception(new Error('Failed to update user preference: hours behind'));
                });
        }
    };

    /**
     * Initialise method for quizzes in progress dashboard rendering.
     *
     * @param {int} context The context id.
     */
    DashboardQuizInprogress.init = function(context) {
        contextid = context;
        ZoomModal.init(context); // Create the zoom modal.
        TableHandler.init(
            0,
            contextid,
            null,
            'local-assessfreq-quiz-inprogress-table',
            'get_quizzes_inprogress_table',
            'local_assessfreq_quiz_table_inprogress_preference',
            'local-assessfreq-quiz-inprogress-table-search',
            null,
            null,
            'local-assessfreq-quiz-inprogress-table-rows'
        );
        ChartData.init(cards, context, 'get_quiz_inprogress_chart', 'local_assessfreq/chart');

        UserPreference.getUserPreference('local_assessfreq_quiz_refresh_preference')
        .then((response) => {
            refreshPeriod = response.preferences[0].value ? response.preferences[0].value : 60;
        })
        .fail(() => {
            Notification.exception(new Error('Failed to get use preference: refresh'));
        });

        UserPreference.getUserPreference('local_assessfreq_quiz_table_inprogress_sort_preference')
        .then((response) => {
            tablesort = response.preferences[0].value ? response.preferences[0].value : 'name_asc';
        })
        .fail(() => {
            Notification.exception(new Error('Failed to get use preference: tablesort'));
        });

        UserPreference.getUserPreference('local_assessfreq_quizzes_inprogress_table_hoursahead_preference')
            .then((response) => {
                hoursAhead = response.preferences[0].value ? response.preferences[0].value : 0;
            })
            .fail(() => {
                Notification.exception(new Error('Failed to get use preference: hoursahead'));
            });

        UserPreference.getUserPreference('local_assessfreq_quizzes_inprogress_table_hoursbehind_preference')
            .then((response) => {
                hoursBehind = response.preferences[0].value ? response.preferences[0].value : 0;
            })
            .fail(() => {
                Notification.exception(new Error('Failed to get use preference: hoursbehind'));
            });

        // Event handling for refresh and period buttons.
        let refreshElement = document.getElementById('local-assessfreq-period-container');
        refreshElement.addEventListener('click', refreshAction);

        // Set up zoom event listeners.
        let summaryZoom = document.getElementById('local-assessfreq-quiz-summary-inprogress-graph-zoom');
        summaryZoom.addEventListener('click', triggerZoomGraph);

        let upcommingZoom = document.getElementById('local-assessfreq-quiz-summary-upcomming-graph-zoom');
        upcommingZoom.addEventListener('click', triggerZoomGraph);

        // Set up behind and ahead quizzes event listeners.
        let quizzesAheadElement = document.getElementById('local-assessfreq-quiz-student-table-hoursahead');
        quizzesAheadElement.addEventListener('click', quizzesAheadSet);

        let quizzesBehindElement = document.getElementById('local-assessfreq-quiz-student-table-hoursbehind');
        quizzesBehindElement.addEventListener('click', quizzesBehindSet);

        processDashboard();

    };

    return DashboardQuizInprogress;
});
