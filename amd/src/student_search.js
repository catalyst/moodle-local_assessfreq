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
 * Javascript for student search display and processing.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/fragment', 'core/notification', 'local_assessfreq/override_modal'],
function(Ajax, Fragment, Notification, OverrideModal) {

    /**
     * Module level variables.
     */
    var StudentSearch = {};
    var contextid;
    var overridden = false;
    var hoursAhead = 4;
    var hoursBehind = 1;
    var refreshPeriod = 60;
    var counterid;

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
                getStudentTable();
                refreshCounter();
            }
        }, (1000));
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
                tableid: 'local_assessfreq_student_search_table',
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
        const tableElement = document.getElementById('local-assessfreq-student-search');
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
                tableid: 'local_assessfreq_student_search_table',
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
                tableid: 'local_assessfreq_student_search_table',
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
            setUserPreference('local_assessfreq_student_search_table_rows_preference', rows)
            .then(() => {
                getStudentTable(); // Reload the table.
            })
            .fail(() => {
                Notification.exception(new Error('Failed to update user preference: rows'));
            });
        }
    };

    /**
     * Process the hours ahead event from the student table.
     */
    const tableSearchAheadSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let hours = event.target.dataset.metric;
            setUserPreference('local_assessfreq_student_search_table_hoursahead_preference', hours)
            .then(() => {
                hoursAhead = hours;
                getStudentTable(); // Reload the table.
            })
            .fail(() => {
                Notification.exception(new Error('Failed to update user preference: hours ahead'));
            });
        }
    };

    /**
     * Process the hours behind event from the student table.
     */
    const tableSearchBehindSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let hours = event.target.dataset.metric;
            setUserPreference('local_assessfreq_student_search_table_hoursbehind_preference', hours)
            .then(() => {
                hoursBehind = hours;
                getStudentTable(); // Reload the table.
            })
            .fail(() => {
                Notification.exception(new Error('Failed to update user preference: hours behind'));
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
        const tableElement = document.getElementById('local-assessfreq-student-search');
        const tableCardElement = document.getElementById('local-assessfreq-student-search-table');
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
     * Display the table that contains all the students that have exams.
     */
    const getStudentTable = function(page) {
        if (typeof page === "undefined" || overridden == true) {
            page = 0;
        }

        overridden = false;

        let search = document.getElementById('local-assessfreq-quiz-student-table-search').value.trim();
        let tableElement = document.getElementById('local-assessfreq-student-search-table');
        let spinner = tableElement.getElementsByClassName('overlay-icon-container')[0];
        let tableBody = tableElement.getElementsByClassName('table-body')[0];


        let params = {'data': JSON.stringify(
                    {'search': search, 'page': page, 'hoursahead': hoursAhead, 'hoursbehind': hoursBehind}
                )};

        spinner.classList.remove('hide'); // Show spinner if not already shown.

        Fragment.loadFragment('local_assessfreq', 'get_student_search_table', contextid, params)
        .done((response) => {
            tableBody.innerHTML = response;
            spinner.classList.add('hide');
            refreshCounter();
            tableEventListeners(); // Re-add table event listeners.

        }).fail(() => {
            Notification.exception(new Error('Failed to update table.'));
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
            getStudentTable();
        } else if (element.tagName.toLowerCase() === 'a') {
            refreshPeriod = element.dataset.period;
            refreshCounter(true);
            setUserPreference('local_assessfreq_quiz_refresh_preference', refreshPeriod);
        }
    };

    /**
     * Trigger the override modal form. Thin wrapper to add extra data to click event.
     */
    const triggerOverrideModal = function(event) {
        event.preventDefault();
        let elements = event.target.closest('a').id.split('-');
        const quizId = elements.pop();
        const userid = elements.pop();
        overridden = true;

        OverrideModal.displayModalForm(quizId, userid);
    };

    /**
     * Initialise method for student search.
     *
     * @param {integer} context The current context id.
     */
    StudentSearch.init = function(context) {
        contextid = context;
        OverrideModal.init(context, getStudentTable);

        // Add required initial event listeners.
        let tableSearchInputElement = document.getElementById('local-assessfreq-quiz-student-table-search');
        let tableSearchResetElement = document.getElementById('local-assessfreq-quiz-student-table-search-reset');
        let tableSearchRowsElement = document.getElementById('local-assessfreq-quiz-student-table-rows');
        let tableSearchAheadElement = document.getElementById('local-assessfreq-quiz-student-table-hoursbehind');
        let tableSearchBehindElement = document.getElementById('local-assessfreq-quiz-student-table-hoursahead');
        let refreshElement = document.getElementById('local-assessfreq-period-container');

        tableSearchInputElement.addEventListener('keyup', tableSearch);
        tableSearchInputElement.addEventListener('paste', tableSearch);
        tableSearchResetElement.addEventListener('click', tableSearchReset);
        tableSearchRowsElement.addEventListener('click', tableSearchRowSet);
        tableSearchAheadElement.addEventListener('click', tableSearchAheadSet);
        tableSearchBehindElement.addEventListener('click', tableSearchBehindSet);
        refreshElement.addEventListener('click', refreshAction);

        // Render the student search table.
        getStudentTable();
    };

    return StudentSearch;
});
