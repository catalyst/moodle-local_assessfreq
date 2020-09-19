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

define(['jquery', 'core/str', 'core/notification', 'core/modal_factory', 'local_assessfreq/modal_large', 'core/templates',
    'core/ajax'],
function($, Str, Notification, ModalFactory, ModalLarge, Templates, Ajax) {

    /**
     * Module level variables.
     */
    var Dayview = {};
    var modalObj;
    const spinner = '<p class="text-center">'
        + '<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>'
        + '</p>';

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
    var systemTimezone = 'Australia/Melbourne';
    var dayViewTitle = '';

    const getUserDate = function(timestamp, format) {
        return new Promise((resolve) => {
            const systemTimezoneTime = new Date(timestamp * 1000).toLocaleString('en-US', {timeZone: systemTimezone});
            let date = new Date(systemTimezoneTime);
            const year = date.getFullYear();
            const month = stringResult[(7 + date.getMonth())];
            const day = date.getDate();
            const hours = date.getHours();
            const minutes = '0' + date.getMinutes();

            const strftimetime = hours + ':' + minutes.substr(-2); // Will display time in 10:30 format.
            const strftimedatetime = day + ' ' + month + ' ' + year + ', ' + strftimetime;

            if (format === 'strftimetime') {
                resolve(strftimetime);
            } else {
                resolve(strftimedatetime);
            }

        });
    };

    const formatData = function(response) {
        let responseArr = JSON.parse(response);
        return new Promise((resolve) => {
            let promiseAllArr = [];

            // We are displaying the event as a bar whose width represents the start and end time of the event.
            // We need to scale the width of the bar to match the width of the container. Therefore 100% width of the container
            // equals 24 hours (one day).
            // There are 1440 mins per day. 1440 mins equals 100%, therefore 1 min = (100/1440)%. 5/72 == 100/1440.
            let scaler = 5 / 72;

            for (let i = 0; i < responseArr.length; i++) {
                let promiseArr = [];
                const year = responseArr[i].endyear;
                const month = (responseArr[i].endmonth) - 1; // Minus 1 for difference between months in PHP and JS.
                const day = responseArr[i].endday;
                const dayStart = (new Date(year, month, day).getTime()) / 1000;
                const timeStart = new Date(responseArr[i].timestart * 1000).toLocaleString('en-US', {timeZone: systemTimezone});
                const timeStartTimestamp = (new Date(timeStart).getTime()) / 1000;
                const timeEnd = new Date(responseArr[i].timeend * 1000).toLocaleString('en-US', {timeZone: systemTimezone});
                const timeEndTimestamp = (new Date(timeEnd).getTime()) / 1000;
                let secondsSinceDayStart = timeStartTimestamp - dayStart;
                let leftMargin = 0;
                let width = 0;

                if (secondsSinceDayStart <= 0) {
                    secondsSinceDayStart = 0;
                    width = ((timeEndTimestamp - dayStart) / 60) * scaler;
                    let startPromise = getUserDate(responseArr[i].timestart, 'strftimedatetime');
                    promiseAllArr.push(startPromise);
                    promiseArr.push(startPromise);
                } else {
                    leftMargin = (secondsSinceDayStart / 60) * scaler;
                    width = ((timeEndTimestamp - timeStartTimestamp) / 60) * scaler;
                    let startPromise = getUserDate(responseArr[i].timestart, 'strftimetime');
                    promiseAllArr.push(startPromise);
                    promiseArr.push(startPromise);
                }

                if (leftMargin + width > 100) {
                    width = 100 - leftMargin;
                }

                responseArr[i].leftmargin = leftMargin;
                responseArr[i].width = width;
                let endPromise = getUserDate(responseArr[i].timeend, 'strftimetime');
                promiseAllArr.push(endPromise);
                promiseArr.push(endPromise);

                Promise.all(promiseArr).then((values) => {
                    responseArr[i].start = values[0];
                    responseArr[i].end = values[1];
                });

            }

            Promise.all(promiseAllArr).then(() => {
                resolve(responseArr);
            });

        });
    };

    /**
     * Initialise the base modal to be used.
     *
     */
    Dayview.display = function(date) {
        modalObj.setBody(spinner);
        modalObj.show();
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
            const year = responseArr[0].endyear;
            const day = responseArr[0].endday;
            const month = stringResult[(6 + parseInt(responseArr[0].endmonth))];
            const dayDate = day + ' ' + month + ' ' + year;

            modalObj.setTitle(dayViewTitle + ' ' + dayDate);
            modalObj.setBody(Templates.render('local_assessfreq/dayview', context));

        }).fail(() => {
            Notification.exception(new Error('Failed to load day view'));
        });

        $('[data-toggle="tooltip"]').tooltip();
    };

    /**
     * Initialise the base modal to be used.
     *
     * @param {integer} context The current context id.
     */
    Dayview.init = function() {
        // Load the strings we'll need later.
        Str.get_strings(stringArr).catch(() => { // Get required strings.
            Notification.exception(new Error('Failed to load strings'));
            return;
        }).then(stringReturn => { // Save string to global to be used later.
            stringResult = stringReturn;
        });

        // Get the system timzone.
        Ajax.call([{
            methodname: 'local_assessfreq_get_system_timezone',
            args: {},
        }], true, false)[0].then((response) => {
            systemTimezone = response;
            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get system timezone'));
        });

        Str.get_string('schedule', 'local_assessfreq').then((title) => {
            dayViewTitle = title;

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
