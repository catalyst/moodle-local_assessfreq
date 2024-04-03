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
 * Adhoc task to process historical data used in plugin.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_assessfreq\task;

use context_system;
use core\task\adhoc_task;
use core\task\manager;
use local_assessfreq\event\event_processed;
use local_assessfreq\frequency;
use moodle_exception;

/**
 * Adhoc task to process historical data used in plugin.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class history_process extends adhoc_task {
    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        mtrace('local_assessfreq: Processing historic event data');

        // Only run if scheduled task is not running.
        // Throw an error if it is and this task will be retried after a delay.
        // The scheduled task won't start while this job is pending.
        $schedtask = manager::get_scheduled_task(data_process::class);
        if ($schedtask->get_lock()) {
            throw new moodle_exception('local_assessfreq_scheduled_task_running');
        }

        $frequency = new frequency();
        $context = context_system::instance();

        $actionstart = time();
        $frequency->delete_events(0); // Delete ALL event records.
        $actionduration = time() - $actionstart;
        $event = event_processed::create([
            'context' => $context,
            'other' => ['action' => 'delete', 'duration' => $actionduration],
        ]);
        $event->trigger();
        mtrace('local_assessfreq: Deleting old event data finished in: ' . $actionduration . ' seconds');

        mtrace('local_assessfreq: Processing site events');
        $actionstart = time();
        $frequency->process_site_events(1); // Process ALL records.
        $actionduration = time() - $actionstart;
        $event = event_processed::create([
            'context' => $context,
            'other' => ['action' => 'site', 'duration' => $actionduration],
        ]);
        $event->trigger();
        mtrace('local_assessfreq: Processing site events finished in: ' . $actionduration . ' seconds');

        mtrace('local_assessfreq: Processing user events');
        $actionstart = time();
        $frequency->process_user_events(1); // Process ALL user events.
        $actionduration = time() - $actionstart;
        $event = event_processed::create([
            'context' => $context,
            'other' => ['action' => 'user', 'duration' => $actionduration],
        ]);
        $event->trigger();
        mtrace('local_assessfreq: Processing user events finished in: ' . $actionduration . ' seconds');
    }
}
