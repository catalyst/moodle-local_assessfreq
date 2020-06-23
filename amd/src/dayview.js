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
 * Javascript for heatmap calendar generation and display.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/str', 'core/notification', 'core/modal_factory', 'local_assessfreq/modal_large', 'core/templates', 'core/ajax'],
function(Str, Notification, ModalFactory, ModalLarge, Templates, Ajax) {

    /**
     * Module level variables.
     */
    var Dayview = {};
    var modalObj;
    const spinner = '<p class="text-center">'
        + '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>'
        + '</p>';

    const formatData = function(response) {
        return new Promise((resolve) => {
                resolve(response);
        });
    };


    /**
     * Initialise the base modal to be used.
     *
     */
    Dayview.display = function(date) {
        let context = {};
        let args = {
                date: date,
                modules: ['all']
            };
            let jsonArgs = JSON.stringify(args);
        Ajax.call([{
            methodname: 'local_assessfreq_get_day_events',
            args: {jsondata: jsonArgs},
        }])[0]
        .then(formatData)
        .then((response) => {
            window.console.log(JSON.parse(response));
            let context = {rows: JSON.parse(response)};
            modalObj.setBody(Templates.render('local_assessfreq/dayview', context));
            modalObj.show();

        }).fail(() => {
            Notification.exception(new Error('Failed to get heat colors'));
        });

        //modalObj.setTitle(title);
        modalObj.setBody(Templates.render('local_assessfreq/dayview', context));
        modalObj.show();


    };

    /**
     * Initialise the base modal to be used.
     *
     * @param {integer} context The current context id.
     */
    Dayview.init = function() {

        Str.get_string('loading', 'core').then((title) => {
            // Create the Modal.
            ModalFactory.create({
                type: ModalLarge.TYPE,
                title: title,
                body: spinner
            })
            .done((modal) => {
                modalObj = modal;

            });
        }).catch(() => {
            Notification.exception(new Error('Failed to load string: loading'));
        });

    };

    return Dayview;
});
