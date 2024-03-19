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
 * Javascript for summary participants graph.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['core/fragment', 'core/templates', 'core/str', 'core/notification'],
    function (Fragment, Templates, Str, Notification) {

        /**
         * Module level variables.
         */
        var Summary = {};

        Summary.chart = function (assessids, contextid) {
            assessids.forEach((assessid) => {
                let chartElement = document.getElementById(assessid + '-summary-graph');
                let params = {'data': JSON.stringify({'quiz' : assessid, 'call': 'participant_summary'})};

                Fragment.loadFragment('local_assessfreq', 'get_quiz_chart', contextid, params)
                .done((response) => {
                    let resObj = JSON.parse(response);
                    if (resObj.hasdata == true) {
                        let legend = {position: 'left'};
                        let context = {
                            'withtable' : false,
                            'chartdata' : JSON.stringify(resObj.chart),
                            'aspect' : false,
                            'legend' : JSON.stringify(legend)
                        };
                        Templates.render('local_assessfreq/chart', context).done((html, js) => {
                            // Load card body.
                            Templates.replaceNodeContents(chartElement, html, js);
                        }).fail(() => {
                            Notification.exception(new Error('Failed to load chart template.'));
                            return;
                        });
                        return;
                    } else {
                        Str.get_string('nodata', 'local_assessfreq').then((str) => {
                            const noDatastr = document.createElement('h3');
                            noDatastr.innerHTML = str;
                            chartElement.innerHTML = noDatastr.outerHTML;
                            return;
                        }).catch(() => {
                            return;
                        });
                    }
                }).fail(() => {
                    Notification.exception(new Error('Failed to load card.'));
                    return;
                });
            });
        };

        return Summary;
    }
);
