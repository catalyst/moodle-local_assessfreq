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
 * Chart data JS module.
 *
 * @module     assessfreqreport/student_search
 * @package
 * @copyright  Simon Thornett <simon.thornett@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import OverrideModal from 'local_assessfreq/override_modal';
import TableHandler from 'local_assessfreq/table_handler';
import * as UserPreference from 'local_assessfreq/user_preferences';

/**
 * Module level variables.
 */
let contextid;

/**
 * Init function.
 * @param {int} context
 */
export const init = (context) => {

    contextid = context;

    let table = new TableHandler(
        0,
        contextid,
        'assessfreqreport-student-search-table',
        'assessfreqreport_student_search',
        'get_student_search_table',
        'assessfreqreport_student_search_table_rows_preference',
        'assessfreqreport_student_search_table_sort_preference',
        'assessfreqreport-student-search-table-search',
        'assessfreqreport-student-search-table',
        'local_assessfreq_set_table_preference'
    );

    OverrideModal.init(
        context,
        'quiz',
        table
    );

    table.getTable();

    // Add required initial event listeners.
    let tableSearchInputElement = document.getElementById('assessfreqreport-student-search-table-search');
    let tableSearchResetElement = document.getElementById('assessfreqreport-student-search-table-search-reset');
    let tableSearchRowsElement = document.getElementById('assessfreqreport-student-search-table-rows');
    let tableSearchAheadElement = document.getElementById('assessfreqreport-student-search-hoursahead');
    let tableSearchBehindElement = document.getElementById('assessfreqreport-student-search-hoursbehind');

    tableSearchInputElement.addEventListener('keyup', table.tableSearch);
    tableSearchInputElement.addEventListener('paste', table.tableSearch);
    tableSearchResetElement.addEventListener('click', table.tableSearchReset);
    tableSearchRowsElement.addEventListener('click', table.tableSearchRowSet);
    tableSearchAheadElement.addEventListener('click', tableSearchAheadSet);
    tableSearchBehindElement.addEventListener('click', tableSearchBehindSet);
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
        UserPreference.setUserPreference('assessfreqreport_student_search_hoursahead_preference', hours);
        // Reload based on selected year.
        location.reload();
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
        UserPreference.setUserPreference('assessfreqreport_student_search_hoursbehind_preference', hours);
        // Reload based on selected year.
        location.reload();
    }
};
