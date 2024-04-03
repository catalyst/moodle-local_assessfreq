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
 * A scheduled task to generate data used in plugin reports.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\task;

use context_system;
use core\task\manager;
use core\task\scheduled_task;
use local_assessfreq\event\event_processed;
use local_assessfreq\frequency;

/**
 * A scheduled task to generate data used in plugin reports.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_process extends scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() : string {
        return get_string('task:dataprocess', 'local_assessfreq');
    }

    /**
     * Do the job.
     * Throw exceptions on errors (the job will be retried).
     */
    public function execute() {
        mtrace('local_assessfreq: Processing event data');
        $now = time();
        $frequency = new frequency();
        $context = context_system::instance();

        // Only run scheduled task if there is not an ad-hoc task pending or processing historic data.
        $adhoctask = manager::get_adhoc_tasks(history_process::class);
        if (!empty($adhoctask)) {
            mtrace('local_assessfreq: Stopping early historic processing task pending');
            return;
        }

        // Due dates may have changed since we last ran report. So delete all events in DB later than now and replace them.
        mtrace('local_assessfreq: Deleting old event data');
        $actionstart = time();
        $frequency->delete_events($now); // Delete event records greater than now.
        $actionduration = time() - $actionstart;
        $event = event_processed::create([
            'context' => $context,
            'other' => ['action' => 'delete', 'duration' => $actionduration],
        ]);
        $event->trigger();
        mtrace('local_assessfreq: Deleting old event data finished in: ' . $actionduration . ' seconds');

        mtrace('local_assessfreq: Processing site events');
        $actionstart = time();
        $frequency->process_site_events($now); // Process records in the future.
        $actionduration = time() - $actionstart;
        $event = event_processed::create([
            'context' => $context,
            'other' => ['action' => 'site', 'duration' => $actionduration],
        ]);
        $event->trigger();
        mtrace('local_assessfreq: Processing site events finished in: ' . $actionduration . ' seconds');

        mtrace('local_assessfreq: Processing user events');
        $actionstart = time();
        $frequency->process_user_events($now); // Process user events.
        $actionduration = time() - $actionstart;
        $event = event_processed::create([
            'context' => $context,
            'other' => ['action' => 'user', 'duration' => $actionduration],
        ]);
        $event->trigger();
        mtrace('local_assessfreq: Processing user events finished in: ' . $actionduration . ' seconds');
    }
}
