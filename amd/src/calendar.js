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

define(['core/str', 'core/notification'], function(Str, Notification) {

    /**
     * Module level variables.
     */
    var Calendar = {};
    const stringArr = [
        {key: 'sun', component: 'calendar'},
        {key: 'mon', component: 'calendar'},
        {key: 'tue', component: 'calendar'},
        {key: 'wed', component: 'calendar'},
        {key: 'thu', component: 'calendar'},
        {key: 'fri', component: 'calendar'},
        {key: 'sat', component: 'calendar'},
        {key: 'jan', component: 'local_assessfreq'},
        {key: 'feb', component: 'local_assessfreq'},
        {key: 'mar', component: 'local_assessfreq'},
        {key: 'apr', component: 'local_assessfreq'},
        {key: 'may', component: 'local_assessfreq'},
        {key: 'jun', component: 'local_assessfreq'},
        {key: 'jul', component: 'local_assessfreq'},
        {key: 'aug', component: 'local_assessfreq'},
        {key: 'sep', component: 'local_assessfreq'},
        {key: 'oct', component: 'local_assessfreq'},
        {key: 'nov', component: 'local_assessfreq'},
        {key: 'dec', component: 'local_assessfreq'},
    ];
    var stringResult;
    const today = new Date();

    /**
     * Check how many days in a month code.
     * from https://dzone.com/articles/determining-number-days-month.
     */
    const daysInMonth = (month, year) => {
        return 32 - new Date(year, month, 32).getDate();
    };

    /**
     *
     */
    const createTables = ({year, startMonth, endMonth}) => {
        return new Promise((resolve, reject) => {
            let calendarContainer = document.createElement('div');
            let month = startMonth;

            // Itterate through and build are tables.
            for (let i = startMonth; i <= endMonth; i++) {
                // Setup some elements.
                let container = document.createElement('div');
                container.classList.add('local-assessfreq-month');
                let table = document.createElement('table');
                let thead = document.createElement('thead');
                let tbody = document.createElement('tbody');
                tbody.id = 'calendar-body-' + i;
                let monthRow = document.createElement('tr');
                let dayrow = document.createElement('tr');
                let monthHeader = document.createElement('th');
                monthHeader.colSpan = 7;
                monthHeader.innerHTML = stringResult[(7 + month)];

                for (let j = 0; j < 7; j++) {
                    let dayHeader = document.createElement('th');
                    dayHeader.innerHTML = stringResult[j];
                    dayrow.appendChild(dayHeader);
                }

                // Construct the table.
                monthRow.appendChild(monthHeader);

                thead.appendChild(monthRow);
                thead.appendChild(dayrow);

                table.appendChild(thead);
                table.appendChild(tbody);

                container.appendChild(table);

                // Add to parent.
                calendarContainer.appendChild(container);

                // Increment variables.
                month++;
            }

            if ((typeof year === 'undefined') || (typeof startMonth === 'undefined') || (typeof endMonth === 'undefined')) {
                reject(Error('Failed to create calendar tables.'));
            } else {
                const resultObj = {
                        calendarContainer : calendarContainer,
                        year : year,
                        startMonth : startMonth
                };
                resolve(resultObj);
            }
        });
    };

    /**
     * Generate calendar markup for the month.
     */
    const populateCalendarDays = (table, year, month) => {
        let firstDay = (new Date(year, month)).getDay();  // Get the starting day of the month.
        let date = 1;  // Creating all cells.

        for (let i = 0; i < 6; i++) {
            let row = document.createElement("tr"); // Creates a table row.

            // Creating individual cells, filing them up with data.
            for (let j = 0; j < 7; j++) {
                if (i === 0 && j < firstDay) {
                    var cell = document.createElement("td");
                    var cellText = document.createTextNode("");
                    cell.appendChild(cellText);
                    row.appendChild(cell);
                }
                else if (date > daysInMonth(month, year)) { // Break if we have generated all the days for this month.
                    break;
                }
                else {
                    cell = document.createElement("td");
                    cellText = document.createTextNode(date);
                    if (date === today.getDate()
                            && parseInt(year) === today.getFullYear() && parseInt(month) === today.getMonth()) {

                        cell.classList.add("bg-info");
                    } // Color today's date.
                    cell.appendChild(cellText);
                    row.appendChild(cell);
                    date++;
                }

            }
            table.appendChild(row); // Appending each row into calendar body.
        }
    };

    /**
     *
     */
    const populateCalendar = ({calendarContainer, year, startMonth}) => {
        return new Promise((resolve, reject) => {
            // Get the table boodies.
            let tables = calendarContainer.getElementsByTagName("tbody");
            let month = startMonth;

            // For each table body populate with calendar.
            for (var i = 0; i < tables.length; i++) {
                let table = tables[i];
                populateCalendarDays(table, year, month);
                month++;
            }


            if (typeof calendarContainer === 'undefined') {
                reject(Error('Failed to populate calendar tables.'));
            } else {
                resolve(calendarContainer);
            }
        });
    };

    /**
     * Initialise method for report card rendering.
     *
     * @param {integer} context The current context id.
     */
    Calendar.generate = (year, startMonth, endMonth) => {
        return new Promise((resolve, reject) => {
            const dateObj = {
                    year : year,
                    startMonth : startMonth,
                    endMonth : endMonth
            };

            Str.get_strings(stringArr).catch(() => { // Get required strings.
                Notification.exception(new Error('Failed to load strings'));
                return;
            }).then(stringReturn => { // Save string to global to be used later.
                stringResult = stringReturn;
                return dateObj;
            }).then(createTables) // Create tables for calendar.
            .then(populateCalendar)
            .then((calendarHTML) => { // Return the result of the generate function.
                if (typeof calendarHTML !== 'undefined') {
                    resolve(calendarHTML);
                } else {
                    reject(Error('Could not generate calendar'));
                }
            });
        });

    };

    return Calendar;
});
