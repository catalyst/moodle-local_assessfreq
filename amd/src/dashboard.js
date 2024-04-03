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
 * @module     local_assessfreq/dashboard
 * @package
 * @copyright  Simon Thornett <simon.thornett@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {

    // Load the tab cuntionality.
    tabs();

};

const tabs = () => {

    const tabcontent = document.getElementsByClassName("tablinks");

    tabcontent.forEach(el => el.addEventListener('click', event => {
        let target = event.target.dataset.target;

        let tabcontent = document.getElementsByClassName("tabcontent");
        for (let i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }

        // Get all elements with class="tablinks" and remove the class "active"
        let tablinks = document.getElementsByClassName("tablinks");
        for (let i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }

        // Show the current tab, and add an "active" class to the button that opened the tab
        document.getElementById(target).style.display = "block";
        event.currentTarget.className += " active";
    }));

    const currentUrl = document.URL;
    const urlParts = currentUrl.split('#');

    const anchor = (urlParts.length > 1) ? urlParts[1] : null;
    // First tab should be open by default unless we have an anchor.
    if (!anchor || document.querySelector('[data-target="tab-' + anchor + '"]') === null) {
        document.querySelector('[data-target="tab-heatmap"]').click();
    } else {
        document.querySelector('[data-target="tab-' + anchor + '"]').click();
    }
}
