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
 * @package
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['core/str', 'core/modal_factory', 'local_assessfreq/modal_large', 'core/fragment', 'core/ajax', 'core/templates'],
    function(Str, ModalFactory, ModalLarge, Fragment, Ajax, Templates) {

        /**
         * Module level variables.
         */
        let FormModal = {};
        let contextid;
        let iscourse;
        let modalObj;
        let resetOptions = [];

        const spinner = '<p class="text-center">'
            + '<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>'
            + '</p>';

        const observerConfig = {attributes: true, childList: false, subtree: true};

        /**
         * Initialise method for activity dashboard rendering.
         * @param {int} context
         * @param {boolean} course
         */
        FormModal.init = function(context, course) {
            contextid = context;
            iscourse = course;

            createModal();
            document.getElementById('local-assessfreq-find-activity').addEventListener('click', displayModalForm);
        };

        /**
         * Create the modal window.
         *
         * @private
         */
        const createModal = function() {
            // eslint-disable-next-line promise/catch-or-return,promise/always-return
            Str.get_string('modal:loading', 'assessfreqreport_activity_dashboard', '', '').then((title) => {
                // Create the Modal.
                ModalFactory.create({
                    type: ModalLarge.TYPE,
                    title: title,
                    body: spinner,
                    large: true
                }).done((modal) => {
                    modalObj = modal;

                    // Explicitly handle form click events.
                    modalObj.getRoot().on('click', '#id_submitbutton', processModalForm);
                    modalObj.getRoot().on('click', '#id_cancel', (e) => {
                        e.preventDefault();
                        modalObj.setBody(spinner);
                        modalObj.hide();
                    });
                });
            });
        };

        /**
         * Display the Modal form.
         */
        const displayModalForm = function() {
            updateModalBody();
            modalObj.show();
        };

        /**
         * Updates the body of the modal window.
         *
         * @param {Object} formdata
         * @private
         */
        const updateModalBody = function(formdata = {}) {

            let params = {
                'jsonformdata': JSON.stringify(formdata)
            };

            // eslint-disable-next-line promise/catch-or-return
            getOptionPlaceholders()
                // eslint-disable-next-line promise/always-return
            .then(() => {
                // eslint-disable-next-line promise/always-return
                Str.get_string('modal:searchactivity', 'assessfreqreport_activity_dashboard', '', '').then((title) => {
                    modalObj.setTitle(title);
                    Fragment.loadFragment('assessfreqreport_activity_dashboard', 'search_form', contextid, params)
                        .done((response, js) => {
                            modalObj.setBody(response);
                            if (js) {
                                Templates.runTemplateJS(js);
                            }
                            if (iscourse) {
                                updateActivities(document.getElementsByName("coursechoice")[0].value);
                            }
                        });
                    let modalContainer = document.querySelectorAll('[data-region*="modal-container"]')[0];
                    observer.observe(modalContainer, observerConfig);
                });
            });
        };

        const updateActivities = function(courseid) {
            Ajax.call([{
                methodname: 'local_assessfreq_get_activities',
                args: {
                    courseid: courseid
                },
            }])[0].done((response) => {
                let activityArray = JSON.parse(response);
                let selectElement = document.getElementById('id_activity');
                let selectElementLength = selectElement.options.length;
                if (document.getElementById('noactivitywarning') !== null) {
                    document.getElementById('noactivitywarning').remove();
                }
                // Clear exisitng options.
                for (let j = selectElementLength - 1; j >= 0; j--) {
                    selectElement.options[j] = null;
                }

                if (activityArray.length > 0) {
                    // Add new options.
                    for (let k = 0; k < activityArray.length; k++) {
                        let opt = activityArray[k];
                        let el = document.createElement('option');
                        el.textContent = opt.name;
                        el.value = opt.id;
                        selectElement.appendChild(el);
                    }
                    selectElement.removeAttribute('disabled');
                    if (document.getElementById('noactivitywarning') !== null) {
                        document.getElementById('noactivitywarning').remove();
                    }
                } else {
                    resetOptions.forEach((option) => {
                        selectElement.appendChild(option);
                    });
                    document.getElementById('id_activity').value = 0;
                    selectElement.disabled = true;
                }
            });
        };

        const ObserverCallback = function(mutationsList) {
            for (let i = 0; i < mutationsList.length; i++) {
                let element = mutationsList[i].target;
                if (element.tagName.toLowerCase() === 'span' && element.classList.contains('badge')) {
                    element.addEventListener('click', updateModalBody);
                    updateActivities(mutationsList[i].target.dataset.value);
                    break;
                }
            }
        };

        const observer = new MutationObserver(ObserverCallback);

        const getOptionPlaceholders = function() {
            return new Promise((resolve) => {
                const stringArr = [
                    {key: 'modal:selectcourse', component: 'assessfreqreport_activity_dashboard'},
                    {key: 'modal:loadingactivity', component: 'assessfreqreport_activity_dashboard'},
                ];

                Str.get_strings(stringArr).then(stringReturn => { // Save string to global to be used later.
                    // eslint-disable-next-line promise/always-return
                    for (let i = 0; i < stringReturn.length; i++) {
                        let el = document.createElement('option');
                        el.textContent = stringReturn[i];
                        el.value = 0 - i;
                        resetOptions.push(el);
                    }
                    resolve();
                });
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

            let activityElement = document.getElementById('id_activity');
            let activityId = activityElement.options[activityElement.selectedIndex].value;
            let courseId = document.getElementsByName("coursechoice")[0].value;

            if (courseId === undefined || activityId < 1) {
                if (document.getElementById('noactivitywarning') === null) {
                    // eslint-disable-next-line promise/always-return
                    Str.get_string('modal:noactivityselected', 'assessfreqreport_activity_dashboard', '', '').then((warning) => {
                        let element = document.createElement('div');
                        element.innerHTML = warning;
                        element.id = 'noactivitywarning';
                        element.classList.add('alert', 'alert-danger');
                        modalObj.getBody().prepend(element);
                    });
                }
            } else {
                modalObj.hide(); // Close modal.
                modalObj.setBody(''); // Cleaer form.
                observer.disconnect(); // Remove observer.

                // Trigger redirect with activityid.
                let params = new URLSearchParams(location.search);
                params.set('activityid', activityId);
                window.location.search = params.toString();
            }

        };

        return FormModal;
    }
);
