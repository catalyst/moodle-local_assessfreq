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

define(['core/ajax', 'core/templates', 'core/fragment', 'local_assessfreq/zoom_modal', 'core/str', 'core/notification'],
function(Ajax, Templates, Fragment, ZoomModal, Str, Notification) {

    /**
     * Module level variables.
     */
    var DashboardQuizInprogress = {};
    var contextid;
    var refreshPeriod = 60;
    var counterid;

    const cards = [
        {cardId: 'local-assessfreq-quiz-summary-upcomming-graph', call: 'upcomming_quizzes', aspect: true},
        {cardId: 'local-assessfreq-quiz-summary-inprogress-graph', call: 'all_participants_inprogress', aspect: true}
    ];

    /**
     * Generic handler to persist user preferences.
     *
     * @param {string} type The name of the attribute you're updating
     * @param {string} value The value of the attribute you're updating
     * @return {object} jQuery promise
     */
    const setUserPreference = function(type, value) {
        var request = {
            methodname: 'core_user_update_user_preferences',
            args: {
                preferences: [{type: type, value: value}]
            }
        };

        return Ajax.call([request])[0];
    };

    /**
     * Generic handler to get user preference.
     *
     * @param {string} name The name of the attribute you're getting.
     * @return {object} jQuery promise
     */
    const getUserPreference = function(name) {
        var request = {
            methodname: 'core_user_get_user_preferences',
            args: {
                'name': name
            }
        };

        return Ajax.call([request])[0];
    };

    /**
    *
    */
   const refreshCounter = function(reset) {
       let progressElement = document.getElementById('local-assessfreq-period-progress');

       // Reset the current count process.
       if (reset == true) {
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
            let params = {'data': JSON.stringify({'call': cardData.call})};

            spinner.classList.remove('hide'); // Show sinner if not already shown.
            Fragment.loadFragment('local_assessfreq', 'get_quiz_inprogress_chart', contextid, params)
            .done((response) => {
                let resObj = JSON.parse(response);
                if (resObj.hasdata == true) {
                    let context = { 'withtable' : true, 'chartdata' : JSON.stringify(resObj.chart), 'aspect' :  cardData.aspect};
                    Templates.render('local_assessfreq/chart', context).done((html, js) => {
                        spinner.classList.add('hide'); // Hide spinner if not already hidden.
                        // Load card body.
                        Templates.replaceNodeContents(chartbody, html, js);
                    }).fail(() => {
                        Notification.exception(new Error('Failed to load chart template.'));
                        return;
                    });
                    return;
                } else {
                    Str.get_string('nodata', 'local_assessfreq').then((str) => {
                        const noDatastr = document.createElement('h3');
                        noDatastr.innerHTML = str;
                        chartbody.innerHTML = noDatastr.outerHTML;
                        spinner.classList.add('hide'); // Hide spinner if not already hidden.
                        return;
                    }).catch(() => {
                        Notification.exception(new Error('Failed to load string: nodata'));
                    });
                }
            }).fail(() => {
                Notification.exception(new Error('Failed to load card.'));
                return;
            });
        });
    };

    /**
     * Display the table that contains all in progress quiz summaries.
     */
    const getSummaryTable = function() {
        let tableElement = document.getElementById('local-assessfreq-quiz-inprogress-table');
        let spinner = tableElement.getElementsByClassName('overlay-icon-container')[0];
        let tableBody = tableElement.getElementsByClassName('table-body')[0];

        spinner.classList.remove('hide'); // Show sinner if not already shown.
        Fragment.loadFragment('local_assessfreq', 'get_quizzes_inprogress_table', contextid)
        .done((response) => {
            tableBody.innerHTML = response;
            spinner.classList.add('hide'); // Hide spinner if not already hidden.
        }).fail(() => {
            Notification.exception(new Error('Failed to update table.'));
            return;
        });
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

            summaryElement.classList.remove('hide'); // Show the card.

            // Populate summary card with details.
            Templates.render('local_assessfreq/quiz-dashboard-inprogress-summary-card-content', quizSummary)
            .done((html) => {
                summarySpinner.classList.add('hide');

                let contentcontainer = document.getElementById('local-assessfreq-quiz-dashboard-inprogress-summary-card-content');
                Templates.replaceNodeContents(contentcontainer, html);
            }).fail(() => {
                Notification.exception(new Error('Failed to load quiz counts template.'));
                return;
            });

            getCardCharts();
            getSummaryTable();
            refreshCounter();

            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get quiz summary counts'));
        });
    };

    /**
     * Handle processing of refresh and period button actions.
     */
    const refreshAction = function(event) {
        event.preventDefault();
        var element = event.target;

        if (element.closest('button') !== null && element.closest('button').id == 'local-assessfreq-refresh-quiz-dashboard') {
            refreshCounter(true);
            processDashboard();
        } else if (element.tagName.toLowerCase() === 'a') {
            refreshPeriod = element.dataset.period;
            refreshCounter(true);
            setUserPreference('local_assessfreq_quiz_refresh_preference', refreshPeriod);
        }
    };

    /**
     * Trigger the zoom graph. Thin wrapper to add extra data to click event.
     */
    const triggerZoomGraph = function(event) {
        let call = event.target.closest('div').dataset.call;
        let params = {'data': JSON.stringify({'call': call})};
        let method = 'get_quiz_inprogress_chart';

        ZoomModal.zoomGraph(event, params, method);
    };

    /**
     * Initialise method for quizzes in progress dashboard rendering.
     */
    DashboardQuizInprogress.init = function(context) {
        contextid = context;
        ZoomModal.init(context); // Create the zoom modal.

        getUserPreference('local_assessfreq_quiz_refresh_preference')
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
        let summaryZoom = document.getElementById('local-assessfreq-quiz-summary-inprogress-graph-zoom');
        summaryZoom.addEventListener('click', triggerZoomGraph);

        let upcommingZoom = document.getElementById('local-assessfreq-quiz-summary-upcomming-graph-zoom');
        upcommingZoom.addEventListener('click', triggerZoomGraph);

        processDashboard();

    };

    return DashboardQuizInprogress;
});
