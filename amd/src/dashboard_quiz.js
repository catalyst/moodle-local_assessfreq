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

define(['local_assessfreq/form_modal', 'core/ajax', 'core/notification', 'core/str', 'core/fragment', 'core/templates',
    'local_assessfreq/zoom_modal', 'local_assessfreq/override_modal'],
function(FormModal, Ajax, Notification, Str, Fragment, Templates, ZoomModal, OverrideModal) {

    /**
     * Module level variables.
     */
    var DashboardQuiz = {};
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
                processDashboard(quizId);
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
            let params = {'data': JSON.stringify({'quiz' : quizId, 'call': cardData.call})};

            spinner.classList.remove('hide'); // Show sinner if not already shown.
            Fragment.loadFragment('local_assessfreq', 'get_quiz_chart', contextid, params)
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
     * Process the sort click events from the student table.
     */
    const tableSort = function(event) {
        event.preventDefault();

        let sortArray = {};
        const linkUrl = new URL(event.target.closest('a').href);
        const targetSortBy = linkUrl.searchParams.get('tsort');
        let targetSortOrder = linkUrl.searchParams.get('tdir');

        // We want to flip the clicked column.
        if (targetSortOrder === '') {
            targetSortOrder = "4";
        }

        sortArray[targetSortBy] = targetSortOrder;

        // Set option via ajax.
        Ajax.call([{
            methodname: 'local_assessfreq_set_table_preference',
            args: {
                tableid: 'local_assessfreq_student_table',
                preference: 'sortby',
                values: JSON.stringify(sortArray)
            },
        }])[0].then(() => {
            getStudentTable(); // Reload the table.
        });

    };

    /**
     * Process the sort click events from the student table.
     */
    const tableHide = function(event) {
        event.preventDefault();

        let hideArray = {};
        const linkUrl = new URL(event.target.closest('a').href);
        const tableElement = document.getElementById('local-assessfreq-quiz-table');
        const links = tableElement.querySelectorAll('a');
        let targetAction;
        let targetColumn;
        let action;
        let column;

        if (linkUrl.search.indexOf('thide') !== -1) {
            targetAction = 'hide';
            targetColumn = linkUrl.searchParams.get('thide');
        } else {
            targetAction = 'show';
            targetColumn = linkUrl.searchParams.get('tshow');
        }

        for (let i = 0; i < links.length; i++) {
            let hideLinkUrl = new URL(links[i].href);
            if (hideLinkUrl.search.indexOf('thide') !== -1) {
                action = 'hide';
                column = hideLinkUrl.searchParams.get('thide');
            } else {
                action = 'show';
                column = hideLinkUrl.searchParams.get('tshow');
            }

            if (action === 'show') {
                hideArray[column] = 1;
            }

        }

        hideArray[targetColumn] = (targetAction === 'hide') ? 1 : 0; // We want to flip the clicked column.

        // Set option via ajax.
        Ajax.call([{
            methodname: 'local_assessfreq_set_table_preference',
            args: {
                tableid: 'local_assessfreq_student_table',
                preference: 'collapse',
                values: JSON.stringify(hideArray)
            },
        }])[0].then(() => {
            getStudentTable(); // Reload the table.
        });

    };

    /**
     * Process the reset click event from the student table.
     */
    const tableReset = function(event) {
        event.preventDefault();

        // Set option via ajax.
        Ajax.call([{
            methodname: 'local_assessfreq_set_table_preference',
            args: {
                tableid: 'local_assessfreq_student_table',
                preference: 'reset',
                values: JSON.stringify({})
            },
        }])[0].then(() => {
            getStudentTable(); // Reload the table.
        });

    };

    /**
     * Process the search events from the student table.
     */
    const tableSearch = function(event) {
        if (event.target.value.length > 2) {
            getStudentTable();
        }

        if (event.target.value.length == 0) {
            getStudentTable();
        }
    };

    /**
     * Process the search reset click event from the student table.
     */
    const tableSearchReset = function() {
        let tableSearchInputElement = document.getElementById('local-assessfreq-quiz-student-table-search');
        tableSearchInputElement.value = '';
        tableSearchInputElement.focus();
        getStudentTable();
    };

    /**
     * Process the row set event from the student table.
     */
    const tableSearchRowSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let rows = event.target.dataset.metric;
            setUserPreference('local_assessfreq_quiz_table_rows_preference', rows)
            .then(() => {
                getStudentTable(); // Reload the table.
            })
            .fail(() => {
                Notification.exception(new Error('Failed to update user preference: rows'));
            });
        }
    };

    /**
     * Process the nav event from the student table.
     */
    const tableNav = function(event) {
        event.preventDefault();

        const linkUrl = new URL(event.target.closest('a').href);
        const page = linkUrl.searchParams.get('page');

        if (page) {
            getStudentTable(page);
        }
    };

    /**
     * Re-add event listeners when the student table is updated.
     */
    const tableEventListeners = function() {
        const tableElement = document.getElementById('local-assessfreq-quiz-table');
        const tableCardElement = document.getElementById('local-assessfreq-quiz-student-table');
        const links = tableElement.querySelectorAll('a');
        const resetlink = tableElement.getElementsByClassName('resettable');
        const overrideLinks = tableElement.getElementsByClassName('action-icon override');
        const disabledLinks = tableElement.getElementsByClassName('action-icon disabled');
        const tableNavElement = tableCardElement.querySelectorAll('nav'); // There are two nav paging elements per table.

        for (let i = 0; i < links.length; i++) {
            let linkUrl = new URL(links[i].href);
            if (linkUrl.search.indexOf('thide') !== -1 || linkUrl.search.indexOf('tshow') !== -1) {
                links[i].addEventListener('click', tableHide);
            } else if (linkUrl.search.indexOf('tsort') !== -1) {
                links[i].addEventListener('click', tableSort);
            }

        }

        if (resetlink.length > 0) {
            resetlink[0].addEventListener('click', tableReset);
        }

        for (let i = 0; i < overrideLinks.length; i++) {
            overrideLinks[i].addEventListener('click', triggerOverrideModal);
        }

        for (let i = 0; i < disabledLinks.length; i++) {
            disabledLinks[i].addEventListener('click', (event) => {
                event.preventDefault();
            });
        }

        tableNavElement.forEach((navElement) => {
            navElement.addEventListener('click', tableNav);
        });
    };

    /**
     * Display the table that contains all the students in the exam as well as their attempts.
     */
    const getStudentTable = function(page) {
        if (typeof page === "undefined") {
            page = 0;
        }

        let search = document.getElementById('local-assessfreq-quiz-student-table-search').value;
        let tableElement = document.getElementById('local-assessfreq-quiz-table');
        let spinner = tableElement.getElementsByClassName('overlay-icon-container')[0];
        let tableBody = tableElement.getElementsByClassName('table-body')[0];
        let params = {'data': JSON.stringify({'quiz' : quizId, 'search': search, 'page': page})};

        spinner.classList.remove('hide'); // Show spinner if not already shown.
        Fragment.loadFragment('local_assessfreq', 'get_student_table', contextid, params)
        .done((response) => {
            tableBody.innerHTML = response;
            spinner.classList.add('hide');
            tableEventListeners(); // Re-add table event listeners.

        }).fail(() => {
            Notification.exception(new Error('Failed to update table.'));
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
            Str.get_string('dashboard:quiztitle', 'local_assessfreq', quizArray.name)
            .then((str) => {
                document.title = str;
            }).catch(() => {
                Notification.exception(new Error('Failed to load string: dashboard:quiztitle'));
            });

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
            document.getElementById('quiz-time-results').href = quizArray.resultlink;

            // Show the cards.
            cardsElement.classList.remove('hide');
            trendElement.classList.remove('hide');
            tableElement.classList.remove('hide');
            periodElement.classList.remove('hide');
            summarySpinner.classList.add('hide');
            getCardCharts();
            getStudentTable();
            refreshCounter();

            tableSearchInputElement.addEventListener('keyup', tableSearch);
            tableSearchInputElement.addEventListener('paste', tableSearch);
            tableSearchResetElement.addEventListener('click', tableSearchReset);
            tableSearchRowsElement.addEventListener('click', tableSearchRowSet);

            // TODO: Cancel autorefresh of cards while quiz in changing.

            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get quiz data'));
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
            processDashboard(quizId);
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
        let params = {'data': JSON.stringify({'quiz' : quizId, 'call': call})};
        let method = 'get_quiz_chart';

        ZoomModal.zoomGraph(event, params, method);
    };

    /**
     * Trigger the override modal form. Thin wrapper to add extra data to click event.
     */
    const triggerOverrideModal = function(event) {
        event.preventDefault();
        const userid = event.target.closest('a').id.substring(25);

        OverrideModal.displayModalForm(quizId, userid);
    };

    /**
     * Initialise method for quiz dashboard rendering.
     */
    DashboardQuiz.init = function(context, quiz) {
        contextid = context;
        FormModal.init(context, processDashboard); // Create modal for quiz selection modal.
        ZoomModal.init(context); // Create the zoom modal.
        OverrideModal.init(context, processDashboard);

        Str.get_string('loadingquiztitle', 'local_assessfreq').then((str) => {
            selectQuizStr = str;
        }).catch(() => {
            Notification.exception(new Error('Failed to load string: loadingquiz'));
        }).then(() => {
            if (quiz > 0) {
                quizId = quiz;
                processDashboard(quiz);
            }
        });

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
        let summaryZoom = document.getElementById('local-assessfreq-quiz-summary-graph-zoom');
        summaryZoom.addEventListener('click', triggerZoomGraph);

        let trendZoom = document.getElementById('local-assessfreq-quiz-summary-trend-zoom');
        trendZoom.addEventListener('click', triggerZoomGraph);

    };

    return DashboardQuiz;
});
