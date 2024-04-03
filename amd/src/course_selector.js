// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.If not, see <http://www.gnu.org/licenses/>.

/**
 * Frameworks datasource.
 *
 * This module is compatible with core/form-autocomplete.
 *
 * @packagetool_lpmigrate
 * @copyright2016 Frédéric Massart - FMCorz.net
 * @licensehttp://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/notification'], function (Ajax, Notification) {

    /**
     * Module level variables.
     */
    let CourseSelector = {};

    /**
     * Source of data for Ajax element.
     *
     * @param {String} selector The selector of the auto complete element.
     * @param {String} query The query string.
     * @param {Function} callback A callback function receiving an array of results.
     */
    CourseSelector.transport = function(selector, query, callback) {
        Ajax.call([{
            methodname: 'local_assessfreq_get_courses',
            args: {
                query: query
            },
        }])[0].then((response) => {
            let courseArray = JSON.parse(response);
            // eslint-disable-next-line promise/no-callback-in-promise
            callback(courseArray);
        }).fail(() => {
            Notification.exception(new Error('Failed to get events'));
        });
    };

    /**
     * Process the results for auto complete elements.
     *
     * @param {String} selector The selector of the auto complete element.
     * @param {Array} results An array or results.
     * @return {Array} New array of results.
     */
    CourseSelector.processResults = function (selector, results) {
        let options = [];
        results.forEach((element) => {
            options.push({
                value: element.id,
                label: element.fullname
            });
        });

        return options;
    };

    return CourseSelector;
});
