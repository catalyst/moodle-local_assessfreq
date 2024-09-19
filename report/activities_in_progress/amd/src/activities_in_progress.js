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
 * @module     assessfreqreport/activities_in_progress
 * @package
 * @copyright  Simon Thornett <simon.thornett@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import TableHandler from 'local_assessfreq/table_handler';
import * as UserPreference from 'local_assessfreq/user_preferences';

/**
 * Init function.
 * @param {Integer} context
 */
export const init = (context) => {

    // Set up event listener and related actions for module dropdown on heatmp.
    moduleDropdown();

    let table = new TableHandler(
        0,
        context,
        'assessfreqreport-activities-in-progress-table',
        'assessfreqreport_activities_in_progress',
        'get_in_progress_table',
        'assessfreqreport_activities_in_progress_table_rows_preference',
        'assessfreqreport_activities_in_progress_table_sort_preference',
        'assessfreqreport-activities-in-progress-table-search',
        'assessfreqreport-activities-in-progress-table',
        'local_assessfreq_set_table_preference'
    );

    table.getTable();

    let tableSearchInputElement = document.getElementById('assessfreqreport-activities-in-progress-table-search');
    let tableSearchResetElement = document.getElementById('assessfreqreport-activities-in-progress-table-search-reset');
    let tableSearchRowsElement = document.getElementById('assessfreqreport-activities-in-progress-table-rows');
    let tableSearchAheadElement = document.getElementById('assessfreqreport-activities-in-progress-hoursahead');
    let tableSearchBehindElement = document.getElementById('assessfreqreport-activities-in-progress-hoursbehind');

    tableSearchInputElement.addEventListener('keyup', table.tableSearch);
    tableSearchInputElement.addEventListener('paste', table.tableSearch);
    tableSearchResetElement.addEventListener('click', table.tableSearchReset);
    tableSearchRowsElement.addEventListener('click', table.tableSearchRowSet);
    tableSearchAheadElement.addEventListener('click', tableSearchAheadSet);
    tableSearchBehindElement.addEventListener('click', tableSearchBehindSet);

};

/**
 * Add the event listeners to the modules in the module select dropdown.
 */
const moduleDropdown = () => {
    let links = document.getElementById('local-assessfreq-report-activities-in-progress-filter-type').getElementsByTagName('a');
    let all = links[0];
    let modules = [];

    for (let i = 0; i < links.length; i++) {
        let module = links[i].dataset.module;

        if (module.toLowerCase() === 'all') {
            links[i].addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                // Remove active class from all other links.
                for (let j = 0; j < links.length; j++) {
                    links[j].classList.remove('active');
                }
                event.target.classList.toggle('active');
            });
        } else if (module.toLowerCase() === 'close') {
            links[i].addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                const dropdownmenu = document.getElementById('local-assessfreq-report-activities-in-progress-filter-type-filters');
                dropdownmenu.classList.remove('show');

                for (let i = 0; i < links.length; i++) {
                    if (links[i].classList.contains('active')) {
                        let module = links[i].dataset.module;
                        modules.push(module);
                    }
                }

                // Save selection as a user preference.
                UserPreference.setUserPreference(
                    'assessfreqreport_activities_in_progress_modules_preference',
                    JSON.stringify(modules)
                );

                // Reload based on selected year.
                location.reload();
            });
        } else {
            links[i].addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                all.classList.remove('active');

                event.target.classList.toggle('active');
            });
        }
    }
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
        UserPreference.setUserPreference('assessfreqreport_activities_in_progress_hoursahead_preference', hours);
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
        UserPreference.setUserPreference('assessfreqreport_activities_in_progress_hoursbehind_preference', hours);
        // Reload based on selected year.
        location.reload();
    }
};
