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
    var FormModal = {};
    var contextid;
    var modalObj;
    var spinner = '<p class="text-center">'
        + '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>'
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
                body: spinner
            })
            .done((modal) => {
                modalObj = modal;
                // Explicitly handle form click events.
                modalObj.getRoot().on('click', '#id_submitbutton', processModalForm);
                modalObj.getRoot().on('click', '#id_cancel', (e) => {
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
    const updateModalBody = function(formdata) {
        if (typeof formdata === "undefined") {
            formdata = {};
        }

        let params = {
            'jsonformdata': JSON.stringify(formdata)
        };

        Str.get_string('searchquiz', 'local_assessfreq').then((title) => {
            modalObj.setTitle(title);
            modalObj.setBody(Fragment.loadFragment('local_assessfreq', 'new_base_form', contextid, params));
            return;
        }).catch(() => {
            Notification.exception(new Error('Failed to load string: searchquiz'));
        });
    };

    /**
     * Updates Moodle form with selected information.
     *
     * @param {Object} e
     * @private
     */
    const processModalForm = function(e) {
        e.preventDefault(); // Stop modal from closing.

        let formData = modalObj.getRoot().find('form'); // Form data.

        window.console.log(formData);
    };

    /**
     * Display the Modal form.
     */
    const displayModalForm = function() {
        updateModalBody();
        modalObj.show();
    };

    /**
     * Initialise method for quiz dashboard rendering.
     */
    FormModal.init = function(context) {
        contextid = context;
        createModal();

        let createBroadcastButton = document.getElementById('local-assessfreq-find-quiz');
        createBroadcastButton.addEventListener('click', displayModalForm);
    };

    return FormModal;
});
