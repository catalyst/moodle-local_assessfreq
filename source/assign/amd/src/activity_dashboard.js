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
 * Javascript for table display and processing.
 *
 * @module     assessfreqsource_assign/activty_dashboard
 * @package
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import TableHandler from 'local_assessfreq/table_handler';
import OverrideModal from 'local_assessfreq/override_modal';

/**
 * Initialise method for assign dashboard rendering.
 *
 * @param {int} context The context id.
 * @param {int} assign The assign id.
 */
export const init = (context, assign) => {
    let table = new TableHandler(
        assign,
        context,
        'assessfreqsource-assign-student-table',
        'assessfreqsource_assign',
        'get_user_table',
        'assessfreqsource_assign_table_rows_preference',
        'assessfreqsource_assign_table_sort_preference',
        'assessfreqsource-assign-student-table-search',
        'assessfreqsource-assign-student-table',
        'local_assessfreq_set_table_preference'
    );

    OverrideModal.init(
        context,
        'assign',
        table
    );

    table.getTable();

    let tableSearchInputElement = document.getElementById('assessfreqsource-assign-student-table-search');
    let tableSearchResetElement = document.getElementById('assessfreqsource-assign-student-table-search-reset');
    let tableSearchRowsElement = document.getElementById('assessfreqsource-assign-student-table-rows');

    tableSearchInputElement.addEventListener('keyup', table.tableSearch);
    tableSearchInputElement.addEventListener('paste', table.tableSearch);
    tableSearchResetElement.addEventListener('click', table.tableSearchReset);
    tableSearchRowsElement.addEventListener('click', table.tableSearchRowSet);
};
