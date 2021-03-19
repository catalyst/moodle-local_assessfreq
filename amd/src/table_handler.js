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
 * Table handler JS module.
 *
 * @package    local_assessfreq
 * @copyright  2020 Guillermo Gomez <guillermogomez@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @module     local_assessfreq/table_handler
 */
define([
    'core/ajax',
    'core/fragment',
    'core/notification',
    'core/templates',
    'local_assessfreq/user_preferences',
    'local_assessfreq/override_modal',
    'local_assessfreq/debouncer',
], function(Ajax, Fragment, Notification, Templates, UserPreference, OverrideModal, Debouncer) {

    /**
     * Module level variables.
     */
    var TableHandler = {};
    var quizId;
    var contextId;
    var elementId;
    var cardElement;
    var SearchElement;
    var rowPreference;
    var dashboardType;
    var fragmentValue;
    var hoursFilter;
    var sortValue;

    /**
     * Display the table that contains all the students in the exam as well as their attempts.
     *
     */
    TableHandler.getTable = function(page) {
        if (typeof page === "undefined") {
            page = 0;
        }

        let search = document.getElementById(SearchElement).value.trim();
        let tableElement = document.getElementById(elementId);
        let spinner = tableElement.getElementsByClassName('overlay-icon-container')[0];
        let tableBody = tableElement.getElementsByClassName('table-body')[0];
        let values = {'search': search, 'page': page};

        // Add values to Object depending on dashboard type.
        if (dashboardType === 'inprogress') {
            let sortarray = sortValue.split('_');
            let sorton = sortarray[0];
            let direction = sortarray[1];
            values.sorton = sorton;
            values.direction = direction;
            values.hoursahead = hoursFilter[0];
            values.hoursbehind = hoursFilter[1];
        } else if (dashboardType === 'quiz') {
            values.quiz = quizId;
        } else if (dashboardType === 'student') {
            values.hoursahead = hoursFilter[0];
            values.hoursbehind = hoursFilter[1];
        }
        let params = {'data': JSON.stringify(values)};

        spinner.classList.remove('hide'); // Show spinner if not already shown.
        Fragment.loadFragment('local_assessfreq', fragmentValue, contextId, params)
            .done((response, js) => {
                tableBody.innerHTML = response;
                if (dashboardType === 'inprogress') {
                    Templates.runTemplateJS(js); // Magic call the initialises JS from template included in response template HTML.
                }
                spinner.classList.add('hide');
                TableHandler.tableEventListeners(); // Re-add table event listeners.

            }).fail(() => {
            Notification.exception(new Error('Failed to update table.'));
        });
    };

    /**
     * This stops the ajax method that updates the table from being updated
     * while the user is still checking options.
     *
     */
    const debounceTable = Debouncer.debouncer(() => {
        TableHandler.getTable();
    }, 750);

    /**
     * Process the sort click events from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    TableHandler.tableSort = function(event) {
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
            TableHandler.getTable(); // Reload the table.
        });

    };

    /**
     * Process the sort click events from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    TableHandler.tableHide = function(event) {
        event.preventDefault();

        let hideArray = {};
        const linkUrl = new URL(event.target.closest('a').href);
        const tableElement = document.getElementById(elementId);
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
            TableHandler.getTable(); // Reload the table.
        });

    };

    /**
     * Process the reset click event from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    TableHandler.tableReset = function(event) {
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
            TableHandler.getTable(); // Reload the table.
        });

    };

    /**
     * Process the search events from the student table.
     *
     */
    TableHandler.tableSearch = function(event) {
        if (event.key === 'Meta' || event.ctrlKey) {
            return false;
        }

        if (event.target.value.length === 0 || event.target.value.length > 2) {
            debounceTable();
        }
    };

    /**
     * Process the search reset click event from the student table.
     *
     */
    TableHandler.tableSearchReset = function() {
        let tableSearchInputElement = document.getElementById(SearchElement);
        tableSearchInputElement.value = '';
        tableSearchInputElement.focus();
        TableHandler.getTable();
    };

    /**
     * Process the row set event from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    TableHandler.tableSearchRowSet = function(event) {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let rows = event.target.dataset.metric;
            UserPreference.setUserPreference(rowPreference, rows)
                .then(() => {
                    TableHandler.getTable(); // Reload the table.
                })
                .fail(() => {
                    Notification.exception(new Error('Failed to update user preference: rows'));
                });
        }
    };

    /**
     * Process the nav event from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    TableHandler.tableNav = function(event) {
        event.preventDefault();

        const linkUrl = new URL(event.target.closest('a').href);
        const page = linkUrl.searchParams.get('page');

        if (page) {
            TableHandler.getTable(page);
        }
    };

    /**
     * Get and process the selected assessment metric from the dropdown for the heatmap display,
     * and update the corresponding user preference.
     *
     * @param {Event} event The triggered event for the element.
     */
    TableHandler.tableSortButtonAction = function(event) {
        event.preventDefault();
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.sort !== sortValue) {
            sortValue = element.dataset.sort;

            let links = element.parentNode.getElementsByTagName('a');
            for (let i = 0; i < links.length; i++) {
                links[i].classList.remove('active');
            }

            element.classList.add('active');

            // Save selection as a user preference.
            UserPreference.setUserPreference('local_assessfreq_quiz_table_inprogress_sort_preference', sortValue);

            debounceTable(); // Call function to update table.

        }
    };

    /**
     * Re-add event listeners when the student table is updated.
     */
    TableHandler.tableEventListeners = function() {
        const tableElement = document.getElementById(elementId);
        let tableNavElement;

        if (dashboardType === 'inprogress') {
            // Quiz in progress dashboard only requires to get the navigation element.
            tableNavElement = tableElement.querySelectorAll('nav'); // There are two nav paging elements per table.
        } else if (dashboardType === 'quiz' || dashboardType === 'student') {
            const tableCardElement = document.getElementById(cardElement);
            const links = tableElement.querySelectorAll('a');
            const resetlink = tableElement.getElementsByClassName('resettable');
            const overrideLinks = tableElement.getElementsByClassName('action-icon override');
            const disabledLinks = tableElement.getElementsByClassName('action-icon disabled');
            tableNavElement = tableCardElement.querySelectorAll('nav'); // There are two nav paging elements per table.

            for (let i = 0; i < links.length; i++) {
                let linkUrl = new URL(links[i].href);
                if (linkUrl.search.indexOf('thide') !== -1 || linkUrl.search.indexOf('tshow') !== -1) {
                    links[i].addEventListener('click', TableHandler.tableHide);
                } else if (linkUrl.search.indexOf('tsort') !== -1) {
                    links[i].addEventListener('click', TableHandler.tableSort);
                }

            }

            if (resetlink.length > 0) {
                resetlink[0].addEventListener('click', TableHandler.tableReset);
            }

            for (let i = 0; i < overrideLinks.length; i++) {
                overrideLinks[i].addEventListener('click', TableHandler.triggerOverrideModal);
            }

            for (let i = 0; i < disabledLinks.length; i++) {
                disabledLinks[i].addEventListener('click', (event) => {
                    event.preventDefault();
                });
            }
        }

        tableNavElement.forEach((navElement) => {
            navElement.addEventListener('click', TableHandler.tableNav);
        });
    };

    /**
     * Trigger the override modal form. Thin wrapper to add extra data to click event.
     *
     * @param {Event} event The triggered event for the element.
     */
    TableHandler.triggerOverrideModal = function(event) {
        event.preventDefault();
        const userid = event.target.closest('a').id.substring(25);

        OverrideModal.displayModalForm(quizId, userid);
    };

    /**
     * Initialise method for table handler.
     *
     * @param {int} quiz The quiz id.
     * @param {int} context The context id.
     * @param {string} tableDashboardType The dashboard type to handle (inprogress, quiz or student).
     * @param {string} tableElementId The table element id.
     * @param {string} tableCardElement The table card element.
     * @param {string} tableSearchElement The table search element.
     * @param {string} tableRowPreference The table row preference.
     * @param {string} tableFragmentValue The table fragment value.
     * @param {array|null} tableHoursFilter Array with hour ahead or behind preference.
     * @param {string|null} tableSortValue The table sort preference.
     */
    TableHandler.init = function(quiz,
                                 context,
                                 tableDashboardType,
                                 tableElementId,
                                 tableCardElement,
                                 tableSearchElement,
                                 tableRowPreference,
                                 tableFragmentValue,
                                 tableHoursFilter = null,
                                 tableSortValue = null) {
        quizId = quiz;
        contextId = context;
        dashboardType = tableDashboardType;
        elementId = tableElementId;
        cardElement = tableCardElement;
        SearchElement = tableSearchElement;
        rowPreference = tableRowPreference;
        fragmentValue = tableFragmentValue;
        hoursFilter = tableHoursFilter;
        sortValue = tableSortValue;
    };

    return TableHandler;
});
