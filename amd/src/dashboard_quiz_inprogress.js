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

define(['jquery', 'core/ajax', 'core/templates', 'core/fragment', 'local_assessfreq/zoom_modal', 'core/str', 'core/notification'],
function($, Ajax, Templates, Fragment, ZoomModal, Str, Notification) {

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
            methodname: 'local_assessfreq_get_user_preferences',
            args: {
                'name': name
            }
        };

        return Ajax.call([request])[0];
    };

    /**
     * Quick and dirty debounce method for the settings.
     * This stops the ajax method that updates the table from being updated
     * while the user is still checking options.
     *
     */
    const debouncer = function (func, wait) {
        let timeout;

        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };

            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };

    const debounceTable = debouncer(() => {
        getSummaryTable();
    }, 750);

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
     * Process the search events from the quiz table.
     */
    const tableSearch = function(event) {
        if (event.key === 'Meta' || event.ctrlKey) {
            return false;
        }

        if (event.target.value.length === 0 || event.target.value.length > 2) {
            debounceTable();
        }
    };

    /**
     * Process the search reset click event from the quiz table.
     */
    const tableSearchReset = function() {
        let tableSearchInputElement = document.getElementById('local-assessfreq-quiz-inprogress-table-search');
        tableSearchInputElement.value = '';
        tableSearchInputElement.focus();
        getSummaryTable();
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
            let params = {
                'data': JSON.stringify({'call': cardData.call, 'hoursahead': hoursAhead, 'hoursbehind': hoursBehind
            })};

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
     * Process the nav event from the quiz table.
     */
    const tableNav = function(event) {
        event.preventDefault();

        const linkUrl = new URL(event.target.closest('a').href);
        const page = linkUrl.searchParams.get('page');

        if (page) {
            getSummaryTable(page);
        }
    };

    /**
     * Process the row set event from the quiz table.
     */
    const tableSearchRowSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let rows = event.target.dataset.metric;
            let activeoptions = document.getElementById('local-assessfreq-quiz-inprogress-table-rows')
            .getElementsByClassName('active');

            // Fix active classes.
            for (var i = 0; i < activeoptions.length; i++) {
                activeoptions[i].classList.remove('active');
            }
            event.target.classList.add('active');

            setUserPreference('local_assessfreq_quiz_table_inprogress_preference', rows)
            .then(() => {
                getSummaryTable(); // Reload the table.
            })
            .fail(() => {
                Notification.exception(new Error('Failed to update user preference: rows'));
            });
        }
    };

    /**
     * Get and process the selected assessment metric from the dropdown for the heatmap display,
     * and update the corresponding user perference.
     *
     * @param {event} event The triggered event for the element.
     */
    const tableSortButtonAction = function(event) {
        event.preventDefault();
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.sort != tablesort) {
            tablesort = element.dataset.sort;

            let links = element.parentNode.getElementsByTagName('a');
            for (let i = 0; i < links.length; i++) {
                links[i].classList.remove('active');
            }

            element.classList.add('active');

            // Save selection as a user preference.
            setUserPreference('local_assessfreq_quiz_table_inprogress_sort_preference', tablesort);

            debounceTable(); // Call function to update table.

        }
    };

    /**
     * Re-add event listeners when the quiz table is updated.
     */
    const tableEventListeners = function() {
        const tableElement = document.getElementById('local-assessfreq-quiz-inprogress-table');
        const tableNavElement = tableElement.querySelectorAll('nav'); // There are two nav paging elements per table.

        tableNavElement.forEach((navElement) => {
            navElement.addEventListener('click', tableNav);
        });
    };

    /**
     * Display the table that contains all in progress quiz summaries.
     */
    const getSummaryTable = function(page) {
        if (typeof page === "undefined") {
            page = 0;
        }

        let tableElement = document.getElementById('local-assessfreq-quiz-inprogress-table');
        let spinner = tableElement.getElementsByClassName('overlay-icon-container')[0];
        let tableBody = tableElement.getElementsByClassName('table-body')[0];
        let search = document.getElementById('local-assessfreq-quiz-inprogress-table-search').value.trim();
        let sortarray = tablesort.split('_');
        let sorton = sortarray[0];
        let direction = sortarray[1];

        let params = {'data': JSON.stringify(
            {'search': search, 'page': page, 'sorton': sorton, 'direction': direction,
                'hoursahead': hoursAhead, 'hoursbehind': hoursBehind}
            )};

        spinner.classList.remove('hide'); // Show sinner if not already shown.

        // Load table content.
        Fragment.loadFragment('local_assessfreq', 'get_quizzes_inprogress_table', contextid, params)
        .done((response, js) => {
            tableBody.innerHTML = response;
            Templates.runTemplateJS(js); // Magic call the initialises JS from template included in response template HTML.
            spinner.classList.add('hide'); // Hide spinner if not already hidden.
            tableEventListeners(); // Re-add table event listeners.
            $('[data-toggle="tooltip"]').tooltip();

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
                Templates.replaceNodeContents(contentcontainer, html);
            }).fail(() => {
                Notification.exception(new Error('Failed to load quiz counts template.'));
                return;
            });

            getCardCharts();
            getSummaryTable();
            refreshCounter();

            // Table event listeners.
            tableSearchInputElement.addEventListener('keyup', tableSearch);
            tableSearchInputElement.addEventListener('paste', tableSearch);
            tableSearchResetElement.addEventListener('click', tableSearchReset);
            tableSearchRowsElement.addEventListener('click', tableSearchRowSet);
            tableSortElement.addEventListener('click', tableSortButtonAction);

            $('[data-toggle="tooltip"]').tooltip();

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
     * Process the hours ahead event from the in progress quizzes table.
     */
    const quizzesAheadSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let hours = event.target.dataset.metric;
            setUserPreference('local_assessfreq_quizzes_inprogress_table_hoursahead_preference', hours)
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
     */
    const quizzesBehindSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let hours = event.target.dataset.metric;
            setUserPreference('local_assessfreq_quizzes_inprogress_table_hoursbehind_preference', hours)
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

        getUserPreference('local_assessfreq_quiz_table_inprogress_sort_preference')
        .then((response) => {
            tablesort = response.preferences[0].value ? response.preferences[0].value : 'name_asc';
        })
        .fail(() => {
            Notification.exception(new Error('Failed to get use preference: tablesort'));
        });

        getUserPreference('local_assessfreq_quizzes_inprogress_table_hoursahead_preference')
            .then((response) => {
                hoursAhead = response.preferences[0].value ? response.preferences[0].value : 0;
            })
            .fail(() => {
                Notification.exception(new Error('Failed to get use preference: hoursahead'));
            });

        getUserPreference('local_assessfreq_quizzes_inprogress_table_hoursbehind_preference')
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
