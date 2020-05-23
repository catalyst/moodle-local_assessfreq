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


    const createTables = (dateObj) => {
        return new Promise((resolve, reject) => {
            /*stuff using username, password*/
            window.console.log(dateObj);
            var foo = 1;
            if (foo == 1) {
                resolve('foobar');
            } else {
                reject(Error("It broke"));
            }
        });
    };

    /**
     * Initialise method for report card rendering.
     *
     * @param {integer} context The current context id.
     */
    Calendar.generate = (year) => {
        return new Promise((resolve, reject) => {
            const dateObj = new Date(year, 0, 1);
            var good = true;

            Str.get_strings(stringArr).catch(() => { // Get required strings.
                Notification.exception(new Error('Failed to load strings'));
                good = false;
                return;
            }).then(stringReturn => { // Save string to global to be used later.
                stringResult = stringReturn;
                window.console.log(stringResult);
                return dateObj;
            }).then(createTables) // Create tables for calendar.
            .then(tree => { // TODO: Fill tables with calendar data.
                window.console.log(tree);
                return;
            }).then(() => { // Return the result of the generate function.
                if (good == true) {
                    resolve('Calender generate done');
                } else {
                    reject(Error('Could not generate calendar'));
                }
            });
        });

    };

    return Calendar;
});
