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
 * Chart data JS module.
 *
 * @module     assessfreqreport/heatmap
 * @package
 * @copyright  Simon Thornett <simon.thornett@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Modal from 'core/modal';
import ModalLarge from 'local_assessfreq/modal_large';
import Notification from 'core/notification';
import Templates from 'core/templates';
import * as UserPreference from 'local_assessfreq/user_preferences';

/**
 * Init function.
 * @param {int} courseid the course id to get events for.
 */
export const init = (courseid) => {

    // Set up event listener and related actions for heatmap interactions.
    heatmapSelector(courseid);

    // Set up event listener and related actions for module dropdown on heatmp.
    moduleDropdown();

    // Set up event listener and related actions for year dropdown on heatmp.
    yearDropdown();

    // Set up event listener and related actions for metric dropdown on heatmp.
    metricDropdown();
};

/**
 * Add the event listeners to the heatmap items to trigger the dialog.
 * @param {int} courseid the course id to get events for.
 */
export const heatmapSelector = (courseid) => {
    const events = document.getElementsByClassName("show-dialog");

    events.forEach(el => el.addEventListener('click', event => {

        let addBlockModal = null;

        Modal.create({
            type: ModalLarge.TYPE,
            large: true,
            body: ''
        }).then(modal => {
            addBlockModal = modal;

            let args = {
                date: event.target.dataset.target,
                courseid: courseid
            };
            let jsonArgs = JSON.stringify(args);
            Ajax.call([{
                methodname: 'assessfreqreport_heatmap_external_get_day_events',
                args: {jsondata: jsonArgs},
            }])[0]
                // eslint-disable-next-line promise/always-return
                .then((responseArr) => {
                    let context = JSON.parse(responseArr);
                    modal.setBody(Templates.render('assessfreqreport_heatmap/dayview', {rows: context}));
                    modal.setTitle(
                        context[0].endday +
                        ' ' +
                        Intl.DateTimeFormat('en', {month: 'long'}).format(new Date(context[0].endmonth)) +
                        ' ' +
                        context[0].endyear
                    );
                }).fail(() => {
                    Notification.exception(new Error('Failed to load day view'));
            });
            modal.show();
        })
        .catch(() => {
            addBlockModal.destroy();
        });

        // Stop the event firing.
        event.preventDefault();
    }));
};

/**
 * Add the event listeners to the modules in the module select dropdown.
 */
const moduleDropdown = () => {
    let links = document.getElementsByClassName('local-assessfreq-report-heatmap-filter-type-option');
    let all = links[0];
    let modules = [];

    for (let i = 0; i < links.length; i++) {
        let module = links[i].dataset.module;

        if (module.toLowerCase() === 'all') {
            links[i].addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();
                // Remove active class from all other links.
                for (let j = 0; j < links.length; j++) {
                    links[j].classList.remove('active');
                }
                event.target.classList.toggle('active');
            });
        } else if (module.toLowerCase() === 'close') {
            links[i].addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                const dropdownmenu = document.getElementById('local-assessfreq-report-heatmap-filter-type-options');
                dropdownmenu.classList.remove('show');

                for (let i = 0; i < links.length; i++) {
                    if (links[i].classList.contains('active')) {
                        let module = links[i].dataset.module;
                        modules.push(module);
                    }
                }

                // Save selection as a user preference.
                UserPreference.setUserPreference('assessfreqreport_heatmap_modules_preference', JSON.stringify(modules));

                // Reload based on selected year.
                location.reload();
            });
        } else {
            links[i].addEventListener('click', event => {
                event.preventDefault();
                event.stopPropagation();

                all.classList.remove('active');

                event.target.classList.toggle('active');
            });
        }
    }
};

/**
 * Get and process the selected year from the dropdown and update the corresponding user perference.
 *
 */
const yearDropdown = () => {

    let targets = document.getElementsByClassName('local-assessfreq-report-heatmap-filter-year-option');
    targets.forEach(el => el.addEventListener('click', event => {
        event.preventDefault();
        let element = event.target;

         // Save selection as a user preference.
        UserPreference.setUserPreference('assessfreqreport_heatmap_year_preference', element.dataset.year);

        // Reload based on selected year.
        location.reload();
    }));
};

/**
 * Get and process the selected metric from the dropdown and update the corresponding user perference.
 *
 */
const metricDropdown = () => {

    let targets = document.getElementsByClassName('local-assessfreq-report-heatmap-filter-metric-option');
    targets.forEach(el => el.addEventListener('click', event => {
        event.preventDefault();
        let element = event.target;

        // Save selection as a user preference.
        UserPreference.setUserPreference('assessfreqreport_heatmap_metric_preference', element.dataset.metric);

        // Reload based on selected year.
        location.reload();
    }));
};
