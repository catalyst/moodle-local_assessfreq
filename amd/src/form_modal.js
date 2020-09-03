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

define(['core/str', 'core/modal_factory', 'core/fragment', 'core/ajax'],
function(Str, ModalFactory, Fragment, Ajax) {

    /**
     * Module level variables.
     */
    var FormModal = {};
    var contextid;
    var modalObj;
    var resetOptions = [];
    var callback;

    const spinner = '<p class="text-center">'
        + '<i class="fa fa-circle-o-notch fa-spin fa-3x fa-fw"></i>'
        + '</p>';

    const observerConfig = { attributes: true, childList: false, subtree: true };

    const ObserverCallback = function(mutationsList) {
        for (let i=0; i<mutationsList.length; i++) {
            let element = mutationsList[i].target;
            if((element.tagName.toLowerCase() === 'li') && (element.getAttribute('role') == 'option')
                    && (element.getAttribute('aria-selected') == 'true')) {
                //element.addEventListener('click', updateModalBody);
                document.getElementById('id_courses').dataset.course = element.dataset.value;
                let selectElement = document.getElementById('id_quiz');

                selectElement.value = -1;
                Ajax.call([{
                    methodname: 'local_assessfreq_get_quizzes',
                    args: {
                        query: mutationsList[i].target.dataset.value
                    },
                }])[0].done((response) => {
                    let quizArray = JSON.parse(response);
                    let selectElementLength = selectElement.options.length;
                    if (document.getElementById('noquizwarning') !== null) {
                        document.getElementById('noquizwarning').remove();
                    }
                    // Clear exisitng options.
                    for (let j=selectElementLength-1; j>=0; j--) {
                        selectElement.options[j] = null;
                    }

                    if (quizArray.length > 0) {

                        // Add new options.
                        for (let k=0; k<quizArray.length; k++) {
                            let opt = quizArray[k];
                            let el = document.createElement('option');
                            el.textContent = opt.name;
                            el.value = opt.id;
                            selectElement.appendChild(el);
                        }
                        selectElement.removeAttribute('disabled');
                        if (document.getElementById('noquizwarning') !== null) {
                            document.getElementById('noquizwarning').remove();
                        }
                    } else {
                        resetOptions.forEach((option) => {
                            selectElement.appendChild(option);
                        });
                        document.getElementById('id_quiz').value = 0;
                        selectElement.disabled = true;
                    }

                }).fail(() => {
                    Notification.exception(new Error('Failed to get quizzes'));
                });

                break;
            }

          }
    };

    const observer = new MutationObserver(ObserverCallback);

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

    const getOptionPlaceholders = function() {
        return new Promise((resolve, reject) => {
            const stringArr = [
                {key: 'selectcourse', component: 'local_assessfreq'},
                {key: 'loadingquiz', component: 'local_assessfreq'},
            ];

            Str.get_strings(stringArr).catch(() => { // Get required strings.
                reject(new Error('Failed to load strings'));
                return;
            }).then(stringReturn => { // Save string to global to be used later.
                for (let i=0; i<stringReturn.length; i++) {
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

        getOptionPlaceholders()
        .then(() => {
            Str.get_string('searchquiz', 'local_assessfreq').then((title) => {
                modalObj.setTitle(title);
                modalObj.setBody(Fragment.loadFragment('local_assessfreq', 'new_base_form', contextid, params));
                let modalContainer = document.querySelectorAll(`[data-region*="modal-container"]`)[0];
                observer.observe(modalContainer, observerConfig);

                return;
            }).catch(() => {
                Notification.exception(new Error('Failed to load string: searchquiz'));
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

        let quizElement = document.getElementById('id_quiz');
        let quizId = quizElement.options[quizElement.selectedIndex].value;
        let courseId = document.getElementById('id_courses').dataset.course;

        if (courseId === undefined || quizId < 1) {
            if (document.getElementById('noquizwarning') === null) {
                Str.get_string('noquizselected', 'local_assessfreq').then((warning) => {
                    let element = document.createElement('div');
                    element.innerHTML = warning;
                    element.id = 'noquizwarning';
                    element.classList.add('alert', 'alert-danger');
                    modalObj.getBody().prepend(element);

                    return;
                }).catch(() => {
                    Notification.exception(new Error('Failed to load string: searchquiz'));
                });

            }

        } else {
            modalObj.hide(); // Close modal.
            modalObj.setBody(''); // Cleaer form.
            observer.disconnect(); // Remove observer.
            callback(quizId, courseId); // Trigger dashboard update.
        }

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
    FormModal.init = function(context, processDashboard) {
        contextid = context;
        callback = processDashboard;
        createModal();

        let createBroadcastButton = document.getElementById('local-assessfreq-find-quiz');
        createBroadcastButton.addEventListener('click', displayModalForm);
    };

    return FormModal;
});
