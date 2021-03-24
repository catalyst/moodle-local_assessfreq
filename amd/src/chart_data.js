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
 * @module     local_assessfreq/char_data
 * @package    local_assessfreq
 * @copyright  2020 Guillermo Gomez <guillermogomez@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Fragment from 'core/fragment';
import Notification from 'core/notification';
import * as Str from 'core/str';
import Templates from 'core/templates';

/**
 * Module level variables.
 */
let cards;
let contextId;
let fragment;
let template;

/**
 * For each of the cards on the dashboard get their corresponding chart data.
 * Data is based on the year variable from the corresponding dropdown.
 * Chart data is loaded via ajax.
 *
 * @param {int|null} quizId The quiz Id.
 * @param {array|null} hoursFilter Array with hour ahead or behind preference.
 * @param {int|null} yearSelect Year selected.
 */
export const getCardCharts = (quizId, hoursFilter, yearSelect) => {
    cards.forEach((cardData) => {
        let cardElement = document.getElementById(cardData.cardId);
        let spinner = cardElement.getElementsByClassName('overlay-icon-container')[0];
        let chartBody = cardElement.getElementsByClassName('chart-body')[0];
        let values = {'call': cardData.call};
        // Add values to Object depending on dashboard type.
        if (hoursFilter) {
            values.hoursahead = hoursFilter[0];
            values.hoursbehind = hoursFilter[1];
        }
        if (quizId) {
            values.quiz = quizId;
        }
        if (yearSelect) {
            values.year = yearSelect;
        }
        let params = {'data': JSON.stringify(values)};

        spinner.classList.remove('hide'); // Show sinner if not already shown.
        Fragment.loadFragment('local_assessfreq', fragment, contextId, params)
            .done((response) => {
                let resObj = JSON.parse(response);
                if (resObj.hasdata === true) {
                    let context = {
                        'withtable': true, 'chartdata': JSON.stringify(resObj.chart)
                    };
                    if (typeof cardData.aspect !== 'undefined') {
                        context.aspect = cardData.aspect;
                    }
                    Templates.render(template, context).done((html, js) => {
                        spinner.classList.add('hide'); // Hide spinner if not already hidden.
                        // Load card body.
                        Templates.replaceNodeContents(chartBody, html, js);
                    }).fail(() => {
                        Notification.exception(new Error('Failed to load chart template.'));
                        return;
                    });
                    return;
                } else {
                    Str.get_string('nodata', 'local_assessfreq').then((str) => {
                        const noDatastr = document.createElement('h3');
                        noDatastr.innerHTML = str;
                        chartBody.innerHTML = noDatastr.outerHTML;
                        spinner.classList.add('hide'); // Hide spinner if not already hidden.
                        return;
                    }).catch(() => {
                        Notification.exception(new Error('Failed to load string: nodata'));
                    });
                }
            }).fail(() => {
            Notification.exception(new Error('Failed to load card.'));
            return;
        });
    });
};

/**
 * Initialise method for table handler.
 *
 * @param {array} cardsArray Cards array.
 * @param {int} contextIdChart The context id.
 * @param {string} fragmentChart Fragment name.
 * @param {string} templateChart Template name.
 */
export const init = (cardsArray, contextIdChart, fragmentChart, templateChart) => {
    cards = cardsArray;
    contextId = contextIdChart;
    fragment = fragmentChart;
    template = templateChart;
};
