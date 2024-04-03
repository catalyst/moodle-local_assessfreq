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
 * User preferences JS module.
 *
 * @module     local_assessfreq/user_preferences
 * @package
 * @copyright  2020 Guillermo Gomez <guillermogomez@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Generic handler to persist user preferences.
 *
 * @method setUserPreference
 * @param {string} type The name of the attribute you're updating
 * @param {string} value The value of the attribute you're updating
 * @return {promise} jQuery promise
 */
export const setUserPreference = (type, value) => {
    const request = {
        methodname: 'core_user_update_user_preferences',
        args: {
            preferences: [{type: type, value: value}]
        }
    };

    return Ajax.call([request])[0]
    .fail(() => {
        Notification.exception(new Error('Failed to update user preference'));
    });
};

/**
 * Generic handler to get user preference.
 *
 * @method getUserPreference
 * @param {string} name The name of the attribute you're getting.
 * @return {promise} jQuery promise
 */
export const getUserPreference = (name) => {
    const request = {
        methodname: 'core_user_get_user_preferences',
        args: {
            'name': name
        }
    };

    return Ajax.call([request])[0];
};
