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
 * @package
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

export default class TableHandler {

    constructor(activity,
                context,
                tableElementId,
                tableFragmentComponent,
                tableFragmentValue,
                tableRowPreference,
                tableSortPreference,
                tableSearchElement,
                tableId = null,
                tableMethodName = null) {
        this.activityId = activity;
        this.contextId = context;
        this.elementId = tableElementId;
        this.fragmentComponent = tableFragmentComponent;
        this.fragmentValue = tableFragmentValue;
        this.rowPreference = tableRowPreference;
        this.sortPreference = tableSortPreference;
        this.searchElement = tableSearchElement;
        this.id = tableId;
        this.methodName = tableMethodName;
        this.overridden = false;
    }

    /**
     * Display the table that contains all the students in the exam as well as their attempts.
     *
     * @param {int|string|null} page Page number.
     */
    getTable = (page = 0) => {
        this.overridden = false;

        let search = document.getElementById(this.searchElement).value.trim();
        let tableElement = document.getElementById(this.elementId);
        let spinner = tableElement.getElementsByClassName('overlay-icon-container')[0];
        let tableBody = tableElement.getElementsByClassName('table-body')[0];
        let values = {'search': search, 'page': page};

        // Add values to Object depending on dashboard type.
        if (this.activityId > 0) {
            values.activityid = this.activityId;
        }

        let params = {'data': JSON.stringify(values)};

        spinner.classList.remove('hide'); // Show spinner if not already shown.
        Fragment.loadFragment(this.fragmentComponent, this.fragmentValue, this.contextId, params)
            .done((response, js) => {
                tableBody.innerHTML = response;
                if (js) {
                    Templates.runTemplateJS(js); // Magic call the initialises JS from template included in response template HTML.
                }
                spinner.classList.add('hide');
                this.tableEventListeners(); // Re-add table event listeners.

            }).fail(() => {
                Notification.exception(new Error('Failed to update table.'));
        });
    };

    /**
     * This stops the ajax method that updates the table from being updated
     * while the user is still checking options.
     *
     */
    debounceTable = Debouncer.debouncer(() => {
        this.getTable();
    }, 750);

    /**
     * Process the sort click events from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    tableSort = (event) => {
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
        // eslint-disable-next-line promise/catch-or-return
        Ajax.call([{
            methodname: this.methodName,
            args: {
                tableid: this.id,
                preference: 'sortby',
                values: JSON.stringify(sortArray)
            },
            // eslint-disable-next-line promise/always-return
        }])[0].then(() => {
            this.getTable(); // Reload the table.
        });

    };

    /**
     * Process the sort click events from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    tableHide = (event) => {
        event.preventDefault();

        let hideArray = {};
        const linkUrl = new URL(event.target.closest('a').href);
        const tableElement = document.getElementById(this.elementId);
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
        // eslint-disable-next-line promise/catch-or-return
        Ajax.call([{
            methodname: this.methodName,
            args: {
                tableid: this.id,
                preference: 'collapse',
                values: JSON.stringify(hideArray)
            },
            // eslint-disable-next-line promise/always-return
        }])[0].then(() => {
            this.getTable(); // Reload the table.
        });

    };

    /**
     * Process the reset click event from the table.
     *
     * @param {Event} event The triggered event for the element.
     */
    tableReset = (event) => {
        event.preventDefault();

        // Set option via ajax.
        // eslint-disable-next-line promise/catch-or-return
        Ajax.call([{
            methodname: this.methodName,
            args: {
                tableid: this.id,
                preference: 'reset',
                values: JSON.stringify({})
            },
            // eslint-disable-next-line promise/always-return
        }])[0].then(() => {
            this.getTable(); // Reload the table.
        });

    };

    /**
     * Process the search events from the student table.
     *
     * @param {Event} event
     * @return {Boolean}
     */
    tableSearch = (event) => {
        if (event.key === 'Meta' || event.ctrlKey) {
            return false;
        }

        if (event.target.value.length === 0 || event.target.value.length > 2) {
            this.debounceTable();
        }
        return true;
    };

    /**
     * Process the search reset click event from the student table.
     *
     */
    tableSearchReset = () => {
        let tableSearchInputElement = document.getElementById(this.searchElement);
        tableSearchInputElement.value = '';
        tableSearchInputElement.focus();
        this.getTable();
    };

    /**
     * Process the row set event from the student table.
     *
     * @param {Event} event The triggered event for the element.
     */
    tableSearchRowSet = (event) => {
        event.preventDefault();
        if (event.target.tagName.toLowerCase() === 'a') {
            let rows = event.target.dataset.metric;
            UserPreference.setUserPreference(this.rowPreference, rows)
                // eslint-disable-next-line promise/always-return
                .then(() => {
                    this.getTable(); // Reload the table.
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
    tableNav = (event) => {
        event.preventDefault();

        const linkUrl = new URL(event.target.closest('a').href);
        const page = linkUrl.searchParams.get('page');

        if (page) {
            this.getTable(page);
        }
    };

    /**
     * Get and process the selected assessment metric from the dropdown for the heatmap display,
     * and update the corresponding user preference.
     *
     * @param {Event} event The triggered event for the element.
     */
    tableSortButtonAction = (event) => {
        event.preventDefault();
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.sort !== this.sortValue) {
            this.sortValue = element.dataset.sort;

            let links = element.parentNode.getElementsByTagName('a');
            for (let i = 0; i < links.length; i++) {
                links[i].classList.remove('active');
            }

            element.classList.add('active');

            // Save selection as a user preference.
            UserPreference.setUserPreference(this.sortPreference, this.sortValue);

            this.debounceTable(); // Call function to update table.
        }
    };

    /**
     * Re-add event listeners when the student table is updated.
     */
    tableEventListeners = () => {
        const tableElement = document.getElementById(this.elementId);
        const links = tableElement.querySelectorAll('a');
        const resetLink = tableElement.getElementsByClassName('resettable');
        const overrideLinks = tableElement.getElementsByClassName('action-icon override');
        const disabledLinks = tableElement.getElementsByClassName('action-icon disabled');
        const tableNavElement = tableElement.querySelectorAll('nav'); // There are two nav paging elements per table.

        for (let i = 0; i < links.length; i++) {
            let linkUrl = new URL(links[i].href);
            if (linkUrl.search.indexOf('thide') !== -1 || linkUrl.search.indexOf('tshow') !== -1) {
                links[i].addEventListener('click', this.tableHide);
            } else if (linkUrl.search.indexOf('tsort') !== -1) {
                links[i].addEventListener('click', this.tableSort);
            }
        }

        if (resetLink.length > 0) {
            resetLink[0].addEventListener('click', this.tableReset);
        }

        for (let i = 0; i < overrideLinks.length; i++) {
            overrideLinks[i].addEventListener('click', this.triggerOverrideModal);
        }

        for (let i = 0; i < disabledLinks.length; i++) {
            disabledLinks[i].addEventListener('click', (event) => {
                event.preventDefault();
            });
        }

        tableNavElement.forEach((navElement) => {
            navElement.addEventListener('click', this.tableNav);
        });
    };

    /**
     * Trigger the override modal form. Thin wrapper to add extra data to click event.
     *
     * @param {Event} event The triggered event for the element.
     */
    triggerOverrideModal = (event) => {
        event.preventDefault();
        let userid = event.target.closest('a').id.substring(25);
        if (userid.includes('-')) {
            let elements = userid.split('-');
            this.activityId = elements.pop();
            userid = elements.pop();
        }

        OverrideModal.displayModalForm(this.activityId, userid, this.hoursFilter);
    };
}
