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
            let responseArr = JSON.parse(response);

            // We are displaying the event as a bar whose width represents the start and end time of the event.
            // We need to scale the width of the bar to match the width of the container. Therefore 100% width of the container
            // equals 24 hours (one day).
            // There are 1440 mins per day. 1440 mins equals 100%, therefore 1 min = (100/1440)%. 5/72 == 100/1440.
            let scaler = 5/72;

            for (let i=0; i<responseArr.length; i++) {
                const year = responseArr[i].endyear;
                const month = (responseArr[i].endmonth) - 1; // Minus 1 for difference between months in PHP and JS.
                const day = responseArr[i].endday;
                const dayStart = (new Date(year, month, day).getTime()) / 1000;
                let secondsSinceDayStart = responseArr[i].timestart - dayStart;
                let leftMargin = 0;
                let width = 0;

                if (secondsSinceDayStart <= 0) {
                    secondsSinceDayStart = 0;
                    width = ((responseArr[i].timeend - dayStart) / 60) * scaler;
                } else {
                    leftMargin = (secondsSinceDayStart / 60) * scaler;
                    width = ((responseArr[i].timeend - responseArr[i].timestart) / 60) * scaler;
                }

                if (leftMargin + width > 100) {
                    width = 100 - leftMargin;
                }

                responseArr[i].leftmargin = leftMargin;
                responseArr[i].width = width;
                window.console.log(responseArr[i]);
            }

            resolve(responseArr);
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
        .then((responseArr) => {

            let context = {rows: responseArr};
            modalObj.setBody(Templates.render('local_assessfreq/dayview', context));
            modalObj.show();

        }).fail(() => {
            Notification.exception(new Error('Failed to load day view'));
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
