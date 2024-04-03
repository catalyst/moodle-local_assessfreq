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
 * Main renderer.
 *
 * @package   assessfreqsource_quiz
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assessfreqsource_quiz\output;

use html_table;
use html_table_cell;
use html_table_row;
use html_writer;
use moodle_url;
use plugin_renderer_base;

class renderer extends plugin_renderer_base {

    public function render_activity_dashboard($cm, $course, $quiz) {

        $detailstable = new html_table();
        $detailstable->attributes['class'] = 'details-table';
        $detailstable->data = [];

        $emptyrow = new html_table_row();
        $emptyrow->attributes['class'] = 'empty';

        // Course.
        $title = new html_table_cell(get_string('detailstable:course', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        $courseurl = (new moodle_url('/course/view.php', ['id' => $course->id]))->out();
        $detailstable->data[] = new html_table_row([$title, "<a href='$courseurl'>$course->fullname</a>"]);

        // Open Time.
        $title = new html_table_cell(get_string('detailstable:opentime', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        if ($quiz->timeopen) {
            $detailstable->data[] = new html_table_row([$title, userdate($quiz->timeopen)]);
        } else {
            $detailstable->data[] = new html_table_row([$title, get_string('source:na', 'assessfreqsource_assign')]);
        }

        // Close Time.
        $title = new html_table_cell(get_string('detailstable:closetime', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        if ($quiz->timeclose) {
            $detailstable->data[] = new html_table_row([$title, userdate($quiz->timeclose)]);
        } else {
            $detailstable->data[] = new html_table_row([$title, get_string('source:na', 'assessfreqsource_assign')]);
        }

        // Time Limit.
        $title = new html_table_cell(get_string('detailstable:timelimit', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        if ($quiz->timelimit) {
            $detailstable->data[] = new html_table_row([$title, format_time($quiz->timelimit)]);
        } else {
            $detailstable->data[] = new html_table_row([$title, get_string('source:na', 'assessfreqsource_assign')]);
        }

        // First participant starts.
        $title = new html_table_cell(get_string('detailstable:firstparticipantstart', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        if ($quiz->firststart) {
            $detailstable->data[] = new html_table_row([$title, userdate($quiz->firststart)]);
        } else {
            $detailstable->data[] = new html_table_row([$title, 'N/A']);
        }

        // Last participant finishes.
        $title = new html_table_cell(get_string('detailstable:lastparticipantfinish', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        if ($quiz->laststart) {
            $detailstable->data[] = new html_table_row([$title, userdate($quiz->laststart)]);
        } else {
            $detailstable->data[] = new html_table_row([$title, 'N/A']);
        }

        // Results.
        $title = new html_table_cell(get_string('detailstable:submissions', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        $activityurl = new moodle_url('/mod/quiz/report.php', ['id' => $cm->id, 'mode' => 'overview']);
        $detailstable->data[] = new html_table_row([
            $title,
            html_writer::link(
                $activityurl,
                get_string('detailstable:viewsubmissions', 'assessfreqsource_quiz'),
                ['target' => '_blank']
            )
        ]);

        $detailstable->data[] = $emptyrow;

        // Participant count.
        $title = new html_table_cell(get_string('detailstable:participantcount', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, $quiz->participants]);

        // Participant with an override.
        $title = new html_table_cell(get_string('detailstable:participantoverridecount', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, $quiz->overridecount]);

        $detailstable->data[] = $emptyrow;

        // Questions in quiz.
        $title = new html_table_cell(get_string('detailstable:questioncount', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, $quiz->questions->questioncount]);

        // Question types in quiz.
        $title = new html_table_cell(get_string('detailstable:questiontypecount', 'assessfreqsource_quiz'));
        $title->attributes['class'] = 'title';
        $detailstable->data[] = new html_table_row([$title, $quiz->questions->typecount]);

        // Details container.
        $detailscontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('detailstable:head', 'assessfreqsource_quiz'),
                'contents' => html_writer::table($detailstable)
            ]
        );

        // Summary container.
        if ($quiz->summarychart['hasdata']) {
            $contents = $quiz->summarychart['chart'];
        } else {
            $contents = get_string('nodata', 'assessfreqsource_quiz');
        }

        $summarycontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('summarychart:head', 'assessfreqsource_quiz'),
                'contents' => $contents
            ]
        );

        // Trend container.
        if ($quiz->trendchart['hasdata']) {
            $contents = $quiz->trendchart['chart'];
        } else {
            $contents = get_string('nodata', 'assessfreqsource_quiz');
        }

        $trendcontainer = $this->render_from_template(
            'local_assessfreq/card',
            [
                'header' => get_string('participanttrend:head', 'assessfreqsource_quiz'),
                'contents' => $contents
            ]
        );

        $linkicon = html_writer::tag('i', '', ['class' => 'fa fa-link fa-flip-vertical fa-fw']);

        $activitylink = $cm->get_name() . "&nbsp;" . html_writer::link($cm->get_url(), $linkicon, ['target' => '_blank']);

        $preferencerows = get_user_preferences('assessfreqsource_quiz_table_rows_preference', 20);
        $rows = [
            20 => 'rows20',
            50 => 'rows50',
            100 => 'rows100',
        ];

        return $this->render_from_template(
            'assessfreqsource_quiz/activity_dashboard',
            [
                'activity' => $activitylink,
                'details' => $detailscontainer,
                'summary' => $summarycontainer,
                'trend' => $trendcontainer,
                'table' => [
                    'id' => 'assessfreqsource-quiz-student',
                    'name' => get_string('studentattempt:head', 'assessfreqsource_quiz'),
                    'rows' => [$rows[$preferencerows] => 'true'],
                ]
            ]
        );
    }
}
