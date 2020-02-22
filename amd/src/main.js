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
 * Javascript to initialise the myoverview block.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax'], function(ajax) {

    /**
     * Module level variables.
     */
    var Main = {};
    var today = new Date();
    var eventArray = [];
    var stringArray = [];

    /**
     * Check how many days in a month code.
     * from https://dzone.com/articles/determining-number-days-month.
     */
    function daysInMonth(month, year) {
        return 32 - new Date(year, month, 32).getDate();
    }

    /**
     * Construct the tables.
     *
     */
    function createTables(calendarContainer, month, num) {
        // Itterate through and build are tables.
        for (let i = 0; i < num; i++) {
            // Setup some elements.
            let container = document.createElement("div");
            container.classList.add("block-assessfreq-month");
            let table = document.createElement("table");
            let thead = document.createElement("thead");
            let tbody = document.createElement("tbody");
            tbody.id = "calendar-body";
            let monthRow = document.createElement("tr");
            let dayrow = document.createElement("tr");
            let monthHeader = document.createElement("th");
            monthHeader.colSpan = 7;
            monthHeader.innerHTML = stringArray['months'][month];

            for (let j = 0; j < 7; j++) {
                let dayHeader = document.createElement("th");
                dayHeader.innerHTML = stringArray['days'][j];
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


    }

    /**
     * Generate calendar markup for the month.
     */
    function generateCalendar(month, year, containerdiv) {

        let firstDay = (new Date(year, month)).getDay();  // Get the starting day of the month.
        var tbl = containerdiv.getElementsByTagName("tbody")[0];
        var monthEvents = eventArray[year][(month + 1)];  // We add one due to month diferences between PHP and JS.

        // Clearing all previous cells.
        tbl.innerHTML = "";

        // Creating all cells.
        let date = 1;

        for (let i = 0; i < 6; i++) {
            // Creates a table row.
            let row = document.createElement("tr");

            // Creating individual cells, filing them up with data.
            for (let j = 0; j < 7; j++) {
                if (i === 0 && j < firstDay) {
                    var cell = document.createElement("td");
                    var cellText = document.createTextNode("");
                    cell.appendChild(cellText);
                    row.appendChild(cell);
                }
                else if (date > daysInMonth(month, year)) {
                    break;
                }

                else {
                    cell = document.createElement("td");
                    cellText = document.createTextNode(date);
                    if ((typeof monthEvents !== "undefined") && (monthEvents.hasOwnProperty(date))) {
                        var heatClass = "block-assessfreq-heat-" + monthEvents[date]['heat'];
                        cell.classList.add(heatClass);
                    }
                    if (date === today.getDate() && year === today.getFullYear() && month === today.getMonth()) {
                        cell.classList.add("bg-info");
                    } // Color today's date.
                    cell.appendChild(cellText);
                    row.appendChild(cell);
                    date++;
                }

            }

            tbl.appendChild(row); // Appending each row into calendar body.
        }
    }

    /**
     * Create calendars.
     */
    function createCalendars(containerdivs, month, year, calendarContainer, spinner) {
        // Get the events to use in the mapping.
        ajax.call([{
            methodname: 'local_assessfreq_get_frequency',
            args: {},
        }])[0].done(function(response) {
            eventArray = JSON.parse(response);
            // Generate calendar on response.
            for (let i = 0; i < containerdivs.length; i++) {
                generateCalendar(month, year, containerdivs[i]);
                month++;
            }
        }).fail(function(response) {
            // TODO: add an alert here like you did for the async backup stuff.
            window.console.log(response);
        }).then(function(){
            calendarContainer.classList.remove("block-assessfreq.block-assessfreq-row-hidden");
            calendarContainer.classList.add("block-assessfreq.block-assessfreq-row");
            spinner.remove();
        });
    }

    /**
     * Initialise all of the modules for the assessment frequency block.
     *
     * @param {object} root The root element for the assessment frequency block.
     */
    Main.init = function(root, spinner) {
        // Get the containers that will hold the months.
        var calendarContainer = root;
        var containerdivs = calendarContainer.children;

        // Start with current month and year.
        var month = today.getMonth();
        var year = today.getFullYear();

        // Make ajax call to get all the strings we'll need.
        // This is more efficient than making an ajax call per string.
        ajax.call([{
            methodname: 'local_assessfreq_get_strings',
            args: {},
        }])[0].done(function(response) {
            stringArray = JSON.parse(response);
            // Create the table shell.
            createTables(calendarContainer, month, 4);
        }).fail(function(response) {
            // TODO: add an alert here like you did for the async backup stuff.
            window.console.log(response);
        }).then(function() {
            createCalendars(containerdivs, month, year, calendarContainer, spinner);
        });

    };

    return Main;
});
