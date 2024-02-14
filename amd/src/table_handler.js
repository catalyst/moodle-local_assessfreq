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
 * @module     local_assessfreq/table_handler
 * @package    local_assessfreq
 * @copyright  2020 Guillermo Gomez <guillermogomez@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Fragment from 'core/fragment';
import Notification from 'core/notification';
import Templates from 'core/templates';
import * as Debouncer from 'local_assessfreq/debouncer';
import OverrideModal from 'local_assessfreq/override_modal';
import * as UserPreference from 'local_assessfreq/user_preferences';

/**
 * Module level variables.
 */
let cardElement;
let contextId;
let elementId;
let fragmentValue;
let hoursFilter;
let quizId = 0;
let overridden = false;
let rowPreference;
let sortValue;
let searchElement;

/**
 * Table id variable.
 *
 * @type {string}
 */
let id;

/**
 * Table method name variable.
 *
 * @type {string}
 */
let methodName;

/**
 * Display the table that contains all the students in the exam as well as their attempts.
 *
 * @param {int} quiz The Quiz Id.
 * @param {array|null} hours Array with hour ahead or behind preference.
 * @param {string|null} sortValueTable Sort preference.
 * @param {int|string|null} page Page number.
 */
export const getTable = (quiz, hours = null, sortValueTable = null, page) => {
    if (typeof page === "undefined" || overridden === true) {
        page = 0;
    }

    overridden = false;

    let search = document.getElementById(searchElement).value.trim();
    let tableElement = document.getElementById(elementId);
    let spinner = tableElement.getElementsByClassName('overlay-icon-container')[0];
    let tableBody = tableElement.getElementsByClassName('table-body')[0];
    let values = {'search': search, 'page': page};

    // Add values to Object depending on dashboard type.
    if (quiz > 0) {
        quizId = quiz;
        values.quiz = quizId;
    }
    if (hours) {
        hoursFilter = hours;
        values.hoursahead = hoursFilter[0];
        values.hoursbehind = hoursFilter[1];
    }
    if (sortValueTable) {
        sortValue = sortValueTable;
        let sortArray = sortValue.split('_');
        let sortOn = sortArray[0];
        let direction = sortArray[1];
        values.sorton = sortOn;
        values.direction = direction;
    }

    let params = {'data': JSON.stringify(values)};

    spinner.classList.remove('hide'); // Show spinner if not already shown.
    Fragment.loadFragment('local_assessfreq', fragmentValue, contextId, params)
        .done((response, js) => {
            tableBody.innerHTML = response;
            if (js) {
                Templates.runTemplateJS(js); // Magic call the initialises JS from template included in response template HTML.
            }
            spinner.classList.add('hide');
            tableEventListeners(); // Re-add table event listeners.

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
    getTable(quizId, hoursFilter, sortValue);
}, 750);

/**
 * Process the sort click events from the student table.
 *
 * @param {Event} event The triggered event for the element.
 */
const tableSort = (event) => {
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
        methodname: methodName,
        args: {
            tableid: id,
            preference: 'sortby',
            values: JSON.stringify(sortArray)
        },
    }])[0].then(() => {
        getTable(quizId, hoursFilter, sortValue); // Reload the table.
    });

};

/**
 * Process the sort click events from the student table.
 *
 * @param {Event} event The triggered event for the element.
 */
const tableHide = (event) => {
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
        methodname: methodName,
        args: {
            tableid: id,
            preference: 'collapse',
            values: JSON.stringify(hideArray)
        },
    }])[0].then(() => {
        getTable(quizId, hoursFilter, sortValue); // Reload the table.
    });

};

/**
 * Process the reset click event from the table.
 *
 * @param {Event} event The triggered event for the element.
 */
const tableReset = (event) => {
    event.preventDefault();

    // Set option via ajax.
    Ajax.call([{
        methodname: methodName,
        args: {
            tableid: id,
            preference: 'reset',
            values: JSON.stringify({})
        },
    }])[0].then(() => {
        getTable(quizId, hoursFilter, sortValue); // Reload the table.
    });

};

/**
 * Process the search events from the student table.
 *
 */
export const tableSearch = (event) => {
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
export const tableSearchReset = () => {
    let tableSearchInputElement = document.getElementById(searchElement);
    tableSearchInputElement.value = '';
    tableSearchInputElement.focus();
    getTable(quizId, hoursFilter, sortValue);
};

/**
 * Process the row set event from the student table.
 *
 * @param {Event} event The triggered event for the element.
 */
export const tableSearchRowSet = (event) => {
    event.preventDefault();
    if (event.target.tagName.toLowerCase() === 'a') {
        let rows = event.target.dataset.metric;
        UserPreference.setUserPreference(rowPreference, rows)
            .then(() => {
                getTable(quizId, hoursFilter, sortValue); // Reload the table.
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
const tableNav = (event) => {
    event.preventDefault();

    const linkUrl = new URL(event.target.closest('a').href);
    const page = linkUrl.searchParams.get('page');

    if (page) {
        getTable(quizId, hoursFilter, sortValue, page);
    }
};

/**
 * Get and process the selected assessment metric from the dropdown for the heatmap display,
 * and update the corresponding user preference.
 *
 * @param {Event} event The triggered event for the element.
 */
export const tableSortButtonAction = (event) => {
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
const tableEventListeners = () => {
    const tableElement = document.getElementById(elementId);
    let tableNavElement;
    if (cardElement) {
        const tableCardElement = document.getElementById(cardElement);
        const links = tableElement.querySelectorAll('a');
        const resetLink = tableElement.getElementsByClassName('resettable');
        const overrideLinks = tableElement.getElementsByClassName('action-icon override');
        const disabledLinks = tableElement.getElementsByClassName('action-icon disabled');
        tableNavElement = tableCardElement.querySelectorAll('nav'); // There are two nav paging elements per table.

        for (let i = 0; i < links.length; i++) {
            let linkUrl = new URL(links[i].href);
            if (linkUrl.search.indexOf('thide') !== -1 || linkUrl.search.indexOf('tshow') !== -1) {
                links[i].addEventListener('click', tableHide);
            } else if (linkUrl.search.indexOf('tsort') !== -1) {
                links[i].addEventListener('click', tableSort);
            }
        }

        if (resetLink.length > 0) {
            resetLink[0].addEventListener('click', tableReset);
        }

        for (let i = 0; i < overrideLinks.length; i++) {
            overrideLinks[i].addEventListener('click', triggerOverrideModal);
        }

        for (let i = 0; i < disabledLinks.length; i++) {
            disabledLinks[i].addEventListener('click', (event) => {
                event.preventDefault();
            });
        }
    } else {
        tableNavElement = tableElement.querySelectorAll('nav');
    }

    tableNavElement.forEach((navElement) => {
        navElement.addEventListener('click', tableNav);
    });
};

/**
 * Trigger the override modal form. Thin wrapper to add extra data to click event.
 *
 * @param {Event} event The triggered event for the element.
 */
const triggerOverrideModal = (event) => {
    event.preventDefault();
    let userid = event.target.closest('a').id.substring(25);
    if (userid.includes('-')) {
        let elements = userid.split('-');
        quizId = elements.pop();
        userid = elements.pop();
    }

    OverrideModal.displayModalForm(quizId, userid, hoursFilter);
};

/**
 * Initialise method for table handler.
 *
 * @param {int} quiz The quiz id.
 * @param {int} context The context id.
 * @param {string} tableCardElement The table card element.
 * @param {string} tableElementId The table element id.
 * @param {string} tableFragmentValue The table fragment value.
 * @param {string} tableRowPreference The table row preference.
 * @param {string} tableSearchElement The table search element.
 * @param {string|null} tableId The table id.
 * @param {string|null} tableMethodName The table method name.
 */
export const init = (quiz,
                     context,
                     tableCardElement,
                     tableElementId,
                     tableFragmentValue,
                     tableRowPreference,
                     tableSearchElement,
                     tableId = null,
                     tableMethodName = null) => {
                            quizId = quiz;
                            contextId = context;
                            cardElement = tableCardElement;
                            elementId = tableElementId;
                            fragmentValue = tableFragmentValue;
                            rowPreference = tableRowPreference;
                            searchElement = tableSearchElement;
                            id = tableId;
                            methodName = tableMethodName;
                        };
