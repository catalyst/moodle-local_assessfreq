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

define(['core/ajax', 'core/templates', 'core/notification'], function(Ajax, Templates, Notifcation) {

    /**
     * Module level variables.
     */
    var Reportcard = {};
    var contextid;

    function assessByMonth() {
        var cardid = 'local-assessfreq-assess-due-month';
        var cardElement = document.getElementById(cardid);
        var footer = cardElement.getElementsByClassName("footer")[0];
        window.console.log(footer);

        // Call an ajax method that returns all the info we need.
        // This includes:
        // Lang strings for the block.
        // Values for year selection.
        // Initial data set.
        
        var templateContext = {};
        Templates.render('local_assessfreq/nav-year-filter', templateContext)
        .then(

        )
        .fail(Notification.exception);
    }


    /**
     * Initialise method for report card rendering.
     *
     * @param {integer} context The current context id.
     */
    Reportcard.init = function(context) {
        contextid = context;

        assessByMonth(); // Process loading for the assessments by month card.

    };

    return Reportcard;
});
