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

define(['jquery', 'core/str', 'core/modal_factory', 'core/modal_events', 'core/fragment', 'core/ajax'],
function($,Str, ModalFactory, ModalEvents, Fragment, Ajax) {

    /**
     * Module level variables.
     */
    var OverrideModal = {};
    var contextid;
    var modalObj;
    var callback;
    var quizid;
    var userid;

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
                // Explicitly handle form click events.
                modalObj.getRoot().on('click', '#id_submitbutton', processModalForm);
                modalObj.getRoot().on('click', '#id_cancel', function(e) {
                    e.preventDefault();
                    modalObj.setBody(spinner);
                    modalObj.hide();
                });
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
    const updateModalBody = function(quiz, user, formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }

        let params = {
            'jsonformdata': JSON.stringify(formdata),
            'quizid': quiz,
            'userid': user
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
     * Updates Moodle form with selected information.
     *
     * @param {Object} e
     * @private
     */
    function processModalForm(e) {
        e.preventDefault(); // Stop modal from closing.

        // Form data.
        let overrideform = modalObj.getRoot().find('form').serialize();
        let formjson = JSON.stringify(overrideform);

        // Handle invalid form fields for better UX.
        // I hate that I had to use JQuery for this.
        var invalid = $.merge(
                modalObj.getRoot().find('[aria-invalid="true"]'),
                modalObj.getRoot().find('.error')
        );

        if (invalid.length) {
            invalid.first().focus();
            return;
        }

        // Submit form via ajax.
        Ajax.call([{
            methodname: 'local_assessfreq_process_override_form',
            args: {
                'jsonformdata': formjson,
                'quizid': quizid
            },
        }])[0].done(() => {
            // For submission succeeded.
            modalObj.setBody(spinner);
            modalObj.hide();
            callback(quizid);
        }).fail(() => {
            // Form submission failed server side, redisplay with errors.
            updateModalBody(quizid, userid, overrideform);
        });
    }

    /**
     * Display the Modal form.
     */
    OverrideModal.displayModalForm = function(quiz, user) {
        quizid = quiz;
        userid = user;
        updateModalBody(quiz, user);
        modalObj.show();
    };

    /**
     * Initialise method for quiz dashboard rendering.
     */
    OverrideModal.init = function(context, callbackFunction) {
        contextid = context;
        callback = callbackFunction;
        createModal();
    };

    return OverrideModal;
});
