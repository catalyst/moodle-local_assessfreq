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

define(['core/str', 'core/modal_factory', 'core/fragment'],
function(Str, ModalFactory, Fragment) {

    /**
     * Module level variables.
     */
    var OverrideModal = {};
    var contextid;
    var modalObj;
    var callback;

    const spinner = '<p class="text-center">'
        + '<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>'
        + '</p>';

    /**
     * Create the modal window.
     *
     * @private
     */
    const createModal = function() {
        Str.get_string('loading', 'local_assessfreq').then((title) => {
            // Create the Modal.
            ModalFactory.create({
                type: ModalFactory.types.DEFAULT,
                title: title,
                body: spinner,
                large: true
            })
            .done((modal) => {
                modalObj = modal;

                // TODO: Explicitly handle form click events.
            });
            return;
        }).catch(() => {
            Notification.exception(new Error('Failed to load string: loading'));
        });
    };

    /**
     * Updates the body of the modal window.
     *
     * @param {Object} formdata
     * @private
     */
    const updateModalBody = function(quiz, userid, formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }

        let params = {
            'jsonformdata': JSON.stringify(formdata),
            'quizid': quiz,
            'userid': userid
        };

        modalObj.setBody(spinner);
        Str.get_string('useroverride', 'local_assessfreq').then((title) => {
            modalObj.setTitle(title);
            modalObj.setBody(Fragment.loadFragment('local_assessfreq', 'new_override_form', contextid, params));
            return;
        }).catch(() => {
            Notification.exception(new Error('Failed to load string: useroverride'));
        });
    };

    /**
     * Display the Modal form.
     */
    OverrideModal.displayModalForm = function(quiz, userid) {
        updateModalBody(quiz, userid);
        modalObj.show();
    };

    /**
     * Initialise method for quiz dashboard rendering.
     */
    OverrideModal.init = function(context, callbackFunction) {
        contextid = context;
        callback = callbackFunction;
        createModal();
        window.console.log(callback);

    };

    return OverrideModal;
});
