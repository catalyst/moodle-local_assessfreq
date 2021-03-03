<?php
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
 * Plugin cache definitions.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Cache definitions.
$definitions = [
    'siteevents' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'courseevents' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'userevents' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'usereventsall' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'eventsduemonth' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'yearevents' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'eventsdueactivity' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'monthlyuser' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'eventusers' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
    'trendassess' => [
        'mode' => cache_store::MODE_APPLICATION,
        'staticacceleration' => true,
        'simplekeys' => true,
        'simpledata' => false
    ],
];

