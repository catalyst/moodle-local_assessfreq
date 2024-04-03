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
 * @module     assessfreqreport/summary_graphs
 * @package
 * @copyright  Simon Thornett <simon.thornett@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as UserPreference from 'local_assessfreq/user_preferences';

/**
 * Init function.
 */
export const init = () => {

    // Set up event listener and related actions for year dropdown on report cards.
    yearDropdown();

};

/**
 * Get and process the selected year from the dropdown,
 * and update the corresponding user perference.
 *
 */
const yearDropdown = () => {

    let targets = document.getElementsByClassName('local-assessfreq-report-summary-graphs-year-filters');
    targets.forEach(el => el.addEventListener('click', event => {
        event.preventDefault();
        let element = event.target;

        if (element.tagName.toLowerCase() === 'a') { // Only act on certain elements.
            let yearselect = element.dataset.year;

            // Save selection as a user preference.
            UserPreference.setUserPreference('assessfreqreport_summary_graphs_year_preference', yearselect);

            // Reload based on selected year.
            location.reload();
        }
    }));
};
