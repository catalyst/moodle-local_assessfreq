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
 * Renderer class.
 *
 * @package   assessfreqsource_assign
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_assign\output;

use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use moodle_url;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_activity_dashboard($cm, $course, $assign) {

        $detailstable = new html_table();
        $detailstable->attributes['class'] = 'details-table';
        $detailstable->data = [];

        $emptyrow = new html_table_row();
        $emptyrow->attributes['class'] = 'empty';

        // Course.
        $title = new html_table_cell(get_string('detailstable:course', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([
            $title,
            html_writer::link(
                new moodle_url(
                    '/course/view.php',
                    ['id' => $course->id]
                ),
                format_text($course->fullname),
                ['target' => '_blank']
            )
        ]);

        // Open Time.
        $title = new html_table_cell(get_string('detailstable:opentime', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        if (isset($cm->customdata['allowsubmissionsfromdate'])) {
            $detailstable->data[] = new html_table_row([$title, userdate($cm->customdata['allowsubmissionsfromdate'])]);
        } else {
            $detailstable->data[] = new html_table_row([$title, get_string('source:na', 'assessfreqsource_assign')]);
        }

        // Due date.
        $title = new html_table_cell(get_string('detailstable:closetime', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        if (isset($cm->customdata['duedate'])) {
            $detailstable->data[] = new html_table_row([$title, userdate($cm->customdata['duedate'])]);
        } else {
            $detailstable->data[] = new html_table_row([$title, get_string('source:na', 'assessfreqsource_assign')]);
        }

        // Cut off date.
        $title = new html_table_cell(get_string('detailstable:timelimit', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        if (isset($cm->customdata['cutoffdate'])) {
            $detailstable->data[] = new html_table_row([$title, userdate($cm->customdata['cutoffdate'])]);
        } else {
            $detailstable->data[] = new html_table_row([$title, get_string('source:na', 'assessfreqsource_assign')]);
        }

        // First participant starts.
        $title = new html_table_cell(get_string('detailstable:firstparticipantstart', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        if ($assign->firststart) {
            $detailstable->data[] = new html_table_row([$title, userdate($assign->firststart)]);
        } else {
            $detailstable->data[] = new html_table_row([$title, 'N/A']);
        }

        // Last participant finishes.
        $title = new html_table_cell(get_string('detailstable:lastparticipantfinish', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        if ($assign->laststart) {
            $detailstable->data[] = new html_table_row([$title, userdate($assign->laststart)]);
        } else {
            $detailstable->data[] = new html_table_row([$title, 'N/A']);
        }

        // Results.
        $title = new html_table_cell(get_string('detailstable:submissions', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        $activityurl = $cm->get_url();
        $activityurl->param('action', 'grading');
        $detailstable->data[] = new html_table_row([
            $title,
            html_writer::link(
                $activityurl,
                get_string('detailstable:viewsubmissions', 'assessfreqsource_assign'),
                ['target' => '_blank']
            )
        ]);

        $detailstable->data[] = $emptyrow;

        // Participant count.
        $title = new html_table_cell(get_string('detailstable:participantcount', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, $assign->count_participants(0)]);

        // Participant with an override.
        $title = new html_table_cell(get_string('detailstable:participantoverridecount', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, $assign->overridecount]);

        $detailstable->data[] = $emptyrow;

        // Submission types.
        $title = new html_table_cell(get_string('detailstable:submissiontypes', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, implode(', ', $assign->enabledsubmission_plugins)]);

        // Group submissions enabled.
        $title = new html_table_cell(get_string('detailstable:groupsubmissionenabled', 'assessfreqsource_assign'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, $assign->groupsubmissionenabled]);

        // Details container.
        $detailscontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('detailstable:head', 'assessfreqsource_assign'),
                'contents' => html_writer::table($detailstable)
            ]
        );

        // Summary container.
        if ($assign->summarychart['hasdata']) {
            $contents = $assign->summarychart['chart'];
        } else {
            $contents = get_string('nodata', 'assessfreqsource_assign');
        }

        $summarycontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('summarychart:head', 'assessfreqsource_assign'),
                'contents' => $contents
            ]
        );

        // Trend container.
        if ($assign->trendchart['hasdata']) {
            $contents = $assign->trendchart['chart'];
        } else {
            $contents = get_string('nodata', 'assessfreqsource_assign');
        }

        $trendcontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('participanttrend:head', 'assessfreqsource_assign'),
                'contents' => $contents
            ]
        );

        $linkicon = html_writer::tag('i', '', ['class' => 'fa fa-link fa-flip-vertical fa-fw']);

        $activitylink = $cm->get_name() . "&nbsp;" . html_writer::link($cm->get_url(), $linkicon, ['target' => '_blank']);

        $preferencerows = get_user_preferences('assessfreqsource_assign_table_rows_preference', 20);
        $rows = [
            20 => 'rows20',
            50 => 'rows50',
            100 => 'rows100',
        ];

        return $this->render_from_template(
            'assessfreqsource_assign/activity_dashboard',
            [
                'activity' => $activitylink,
                'details' => $detailscontainer,
                'summary' => $summarycontainer,
                'trend' => $trendcontainer,
                'table' => [
                    'id' => 'assessfreqsource-assign-student',
                    'name' => get_string('studentattempt:head', 'assessfreqsource_assign'),
                    'rows' => [$rows[$preferencerows] => 'true'],
                ]
            ]
        );
    }
}
