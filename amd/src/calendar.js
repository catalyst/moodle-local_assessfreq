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

define(['core/ajax', 'core/notification'], function(Ajax, Notification) {

    /**
     * Module level variables.
     */
    var Calendar = {};
    var stringArray = [];

    function createTables(dateObj) {
        window.console.log(dateObj);
        window.console.log(stringArray);
    }

    /**
     * Initialise method for report card rendering.
     *
     * @param {integer} context The current context id.
     */
    Calendar.generate = function(year) {
        const dateObj = new Date(year, 0, 1);

        // Make ajax call to get all the strings we'll need.
        // This is more efficient than making an ajax call per string.
        Ajax.call([{
            methodname: 'local_assessfreq_get_strings',
            args: {},
        }])[0].done(function(response) {
            stringArray = JSON.parse(response);
        }).fail(function() {
            Notification.exception(new Error('Failed to calendar strings'));
            return;
        }).then(
            createTables(dateObj)
        );

        return dateObj;

    };

    return Calendar;
});
