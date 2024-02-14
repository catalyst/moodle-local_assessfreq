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
 * @module     local_assessfreq/student_search
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import Notification from 'core/notification';
import OverrideModal from 'local_assessfreq/override_modal';
import * as TableHandler from 'local_assessfreq/table_handler';
import * as UserPreference from 'local_assessfreq/user_preferences';

/**
 * Module level variables.
 */
var contextid;
var hoursAhead = 4;
var hoursBehind = 1;
var refreshPeriod = 60;
var counterid;

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
            TableHandler.getTable(0, [hoursAhead, hoursBehind], null);
            refreshCounter();
        }
    }, (1000));
};

/**
 * Process the hours ahead event from the student table.
 *
 * @param {Event} event The triggered event for the element.
 */
const tableSearchAheadSet = (event) => {
    event.preventDefault();
    if (event.target.tagName.toLowerCase() === 'a') {
        let hours = event.target.dataset.metric;
        UserPreference.setUserPreference('local_assessfreq_student_search_table_hoursahead_preference', hours)
        .then(() => {
            hoursAhead = hours;
            TableHandler.getTable(0, [hoursAhead, hoursBehind], null); // Reload the table. // Reload the table.
        })
        .fail(() => {
            Notification.exception(new Error('Failed to update user preference: hours ahead'));
        });
    }
};

/**
 * Process the hours behind event from the student table.
 *
 * @param {Event} event The triggered event for the element.
 */
const tableSearchBehindSet = (event) => {
    event.preventDefault();
    if (event.target.tagName.toLowerCase() === 'a') {
        let hours = event.target.dataset.metric;
        UserPreference.setUserPreference('local_assessfreq_student_search_table_hoursbehind_preference', hours)
        .then(() => {
            hoursBehind = hours;
            TableHandler.getTable(0, [hoursAhead, hoursBehind], null); // Reload the table. // Reload the table.
        })
        .fail(() => {
            Notification.exception(new Error('Failed to update user preference: hours behind'));
        });
    }
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
        TableHandler.getTable(0, [hoursAhead, hoursBehind], null);
    } else if (element.tagName.toLowerCase() === 'a') {
        refreshPeriod = element.dataset.period;
        refreshCounter(true);
        UserPreference.setUserPreference('local_assessfreq_quiz_refresh_preference', refreshPeriod);
    }
};

/**
 * Initialise method for student search.
 *
 * @param {integer} context The current context id.
 */
export const init = (context) => {
    contextid = context;
    TableHandler.init(
        0,
        contextid,
        'local-assessfreq-student-search-table',
        'local-assessfreq-student-search',
        'get_student_search_table',
        'local_assessfreq_student_search_table_rows_preference',
        'local-assessfreq-quiz-student-table-search',
        'local_assessfreq_student_search_table',
        'local_assessfreq_set_table_preference'
    );

    // Add required initial event listeners.
    let tableSearchInputElement = document.getElementById('local-assessfreq-quiz-student-table-search');
    let tableSearchResetElement = document.getElementById('local-assessfreq-quiz-student-table-search-reset');
    let tableSearchRowsElement = document.getElementById('local-assessfreq-quiz-student-table-rows');
    let tableSearchAheadElement = document.getElementById('local-assessfreq-quiz-student-table-hoursahead');
    let tableSearchBehindElement = document.getElementById('local-assessfreq-quiz-student-table-hoursbehind');
    let refreshElement = document.getElementById('local-assessfreq-period-container');

    tableSearchInputElement.addEventListener('keyup', TableHandler.tableSearch);
    tableSearchInputElement.addEventListener('paste', TableHandler.tableSearch);
    tableSearchResetElement.addEventListener('click', TableHandler.tableSearchReset);
    tableSearchRowsElement.addEventListener('click', TableHandler.tableSearchRowSet);
    tableSearchAheadElement.addEventListener('click', tableSearchAheadSet);
    tableSearchBehindElement.addEventListener('click', tableSearchBehindSet);
    refreshElement.addEventListener('click', refreshAction);

    $.when(
        UserPreference.getUserPreference('local_assessfreq_student_search_table_hoursahead_preference')
        .then((response) => {
            hoursAhead = response.preferences[0].value ? response.preferences[0].value : 4;
        })
        .fail(() => {
            Notification.exception(new Error('Failed to get use preference: hoursahead'));
        }),
        UserPreference.getUserPreference('local_assessfreq_student_search_table_hoursbehind_preference')
        .then((response) => {
            hoursBehind = response.preferences[0].value ? response.preferences[0].value : 1;
        })
        .fail(() => {
            Notification.exception(new Error('Failed to get use preference: hoursahead'));
        })
    ).done(function () {
        TableHandler.getTable(0, [hoursAhead, hoursBehind], null);
        OverrideModal.init(context, TableHandler.getTable, [hoursAhead, hoursBehind]);
    });
};
