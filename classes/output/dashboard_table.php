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
namespace local_assessfreq\output;

/**
 * Common code for outputting dashboard tables
 *
 * @package   local_assessfreq
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait dashboard_table {
    /**
     * Get content for title column.
     *
     * @param \stdClass $row
     * @return string html used to display the video field.
     * @throws \moodle_exception
     */
    public function col_fullname($row): string {
        global $OUTPUT;

        return $OUTPUT->user_picture($row, ['size' => 35, 'includefullname' => true]);
    }

    /**
     * Get content for time start column.
     * Displays the user attempt start time.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_timestart($row) {
        if ($row->timestart == 0) {
            $content = \html_writer::span(get_string('na', 'local_assessfreq'));
        } else {
            $datetime = userdate($row->timestart, get_string('trenddatetime', 'local_assessfreq'));
            $content = \html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for time finish column.
     * Displays the user attempt finish time.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_timefinish($row) {
        if ($row->timefinish == 0 && $row->timestart == 0) {
            $content = \html_writer::span(get_string('na', 'local_assessfreq'));
        } else if ($row->timefinish == 0 && $row->timestart > 0) {
            $time = $row->timestart + $row->timelimit;
            $datetime = userdate($time, get_string('trenddatetime', 'local_assessfreq'));
            $content = \html_writer::span($datetime, 'local-assessfreq-disabled');
        } else {
            $datetime = userdate($row->timefinish, get_string('trenddatetime', 'local_assessfreq'));
            $content = \html_writer::span($datetime);
        }

        return $content;
    }

    /**
     * Get content for state column.
     * Displays the users state in the quiz.
     *
     * @param \stdClass $row
     * @return string html used to display the field.
     */
    public function col_state($row) {
        if ($row->state == 'notloggedin') {
            $color = 'background: ' . get_config('local_assessfreq', 'notloggedincolor');
        } else if ($row->state == 'loggedin') {
            $color = 'background: ' . get_config('local_assessfreq', 'loggedincolor');
        } else if ($row->state == 'inprogress') {
            $color = 'background: ' . get_config('local_assessfreq', 'inprogresscolor');
        } else if ($row->state == 'uploadpending') {
            $color = 'background: ' . get_config('local_assessfreq', 'inprogresscolor');
        } else if ($row->state == 'finished') {
            $color = 'background: ' . get_config('local_assessfreq', 'finishedcolor');
        } else if ($row->state == 'abandoned') {
            $color = 'background: ' . get_config('local_assessfreq', 'finishedcolor');
        } else if ($row->state == 'overdue') {
            $color = 'background: ' . get_config('local_assessfreq', 'finishedcolor');
        }

        $content = \html_writer::span('', 'local-assessfreq-status-icon', ['style' => $color]);
        $content .= get_string($row->state, 'local_assessfreq');

        return $content;
    }

    /**
     * Return an array of headers common across dashboard tables.
     *
     * @return array
     */
    protected function get_common_headers(): array {
        return [
            get_string('quiztimeopen', 'local_assessfreq'),
            get_string('quiztimeclose', 'local_assessfreq'),
            get_string('quiztimelimit', 'local_assessfreq'),
            get_string('quiztimestart', 'local_assessfreq'),
            get_string('quiztimefinish', 'local_assessfreq'),
            get_string('status', 'local_assessfreq'),
            get_string('actions', 'local_assessfreq'),
        ];
    }

    /**
     * Return an array of columns common across dashboard tables.
     *
     * @return array
     */
    protected function get_common_columns(): array {
        return [
            'timeopen',
            'timeclose',
            'timelimit',
            'timestart',
            'timefinish',
            'state',
            'actions',
        ];
    }

    /**
     * Return HTML for common column actions.
     *
     * @param \stdClass $row
     * @return string
     */
    protected function get_common_column_actions(\stdClass $row): string {
        global $OUTPUT;
        $actions = '';
        if (
                $row->state == 'finished'
                || $row->state == 'inprogress'
                || $row->state == 'uploadpending'
                || $row->state == 'abandoned'
                || $row->state == 'overdue'
        ) {
            $classes = 'action-icon';
            $attempturl = new \moodle_url('/mod/quiz/review.php', ['attempt' => $row->attemptid]);
            $attributes = [
                    'class' => $classes,
                    'id' => 'tool-assessfreq-attempt-' . $row->id,
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'top',
                    'title' => get_string('userattempt', 'local_assessfreq'),
            ];
        } else {
            $classes = 'action-icon disabled';
            $attempturl = '#';
            $attributes = [
                    'class' => $classes,
                    'id' => 'tool-assessfreq-attempt-' . $row->id,
            ];
        }
        $icon = $OUTPUT->render(new \pix_icon('i/search', ''));
        $actions .= \html_writer::link($attempturl, $icon, $attributes);

        $profileurl = new \moodle_url('/user/profile.php', ['id' => $row->id]);
        $icon = $OUTPUT->render(new \pix_icon('i/completion_self', ''));
        $actions .= \html_writer::link($profileurl, $icon, [
                'class' => 'action-icon',
                'id' => 'tool-assessfreq-profile-' . $row->id,
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'title' => get_string('userprofile', 'local_assessfreq'),
        ]);

        $logurl = new \moodle_url('/report/log/user.php', ['id' => $row->id, 'course' => 1, 'mode' => 'all']);
        $icon = $OUTPUT->render(new \pix_icon('i/report', ''));
        $actions .= \html_writer::link($logurl, $icon, [
                'class' => 'action-icon',
                'id' => 'tool-assessfreq-log-' . $row->id,
                'data-toggle' => 'tooltip',
                'data-placement' => 'top',
                'title' => get_string('userlogs', 'local_assessfreq'),
        ]);
        return $actions;
    }
}
