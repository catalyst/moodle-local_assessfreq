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
 * Javascript for large modal .
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['jquery', 'core/notification', 'core/custom_interaction_events', 'core/modal', 'core/modal_registry'],
    function ($, Notification, CustomEvents, Modal, ModalRegistry) {

        var registered = false;

        /**
         * Constructor for the Modal.
         *
         * @param {object} root The root jQuery element for the modal
         */
        var ModalLarge = function (root) {
            Modal.call(this, root);
        };

        ModalLarge.TYPE = 'local_assesfreq-large_modal';
        ModalLarge.prototype = Object.create(Modal.prototype);
        ModalLarge.prototype.constructor = ModalLarge;

        /**
         * Set up all of the event handling for the modal.
         *
         * @method registerEventListeners
         */
        ModalLarge.prototype.registerEventListeners = function () {
            // Apply parent event listeners.
            Modal.prototype.registerEventListeners.call(this);
        };

        // Automatically register with the modal registry the first time this module is imported so that you can create modals
        // of this type using the modal factory.
        if (!registered) {
            ModalRegistry.register(ModalLarge.TYPE, ModalLarge, 'local_assessfreq/modal_large');
            registered = true;
        }

        return ModalLarge;
    }
);
