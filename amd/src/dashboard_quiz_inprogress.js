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
 * Javascript for quizzes in progress display and processing.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/templates', 'core/fragment', 'local_assessfreq/zoom_modal', 'core/str', 'core/notification'],
function(Ajax, Templates, Fragment, ZoomModal, Str, Notification) {

    /**
     * Module level variables.
     */
    var DashboardQuizInprogress = {};
    var contextid;

    const cards = [
        {cardId: 'local-assessfreq-quiz-summary-upcomming-graph', call: 'upcomming_quizzes', aspect: false},
        {cardId: 'local-assessfreq-quiz-summary-inprogress-graph', call: 'all_participants_inprogress', aspect: true}
    ];

    /**
     * For each of the cards on the dashbaord get their corresponding chart data.
     * Data is based on the year variable from the corresponding dropdown.
     * Chart data is loaded via ajax.
     *
     */
    const getCardCharts = function() {
        cards.forEach((cardData) => {
            let cardElement = document.getElementById(cardData.cardId);
            let spinner = cardElement.getElementsByClassName('overlay-icon-container')[0];
            let chartbody = cardElement.getElementsByClassName('chart-body')[0];
            let params = {'data': JSON.stringify({'call': cardData.call})};

            spinner.classList.remove('hide'); // Show sinner if not already shown.
            Fragment.loadFragment('local_assessfreq', 'get_quiz_inprogress_chart', contextid, params)
            .done((response) => {
                let resObj = JSON.parse(response);
                if (resObj.hasdata == true) {
                    let context = { 'withtable' : true, 'chartdata' : JSON.stringify(resObj.chart), 'aspect' :  cardData.aspect};
                    Templates.render('local_assessfreq/chart', context).done((html, js) => {
                        spinner.classList.add('hide'); // Hide spinner if not already hidden.
                        // Load card body.
                        Templates.replaceNodeContents(chartbody, html, js);
                    }).fail(() => {
                        Notification.exception(new Error('Failed to load chart template.'));
                        return;
                    });
                    return;
                } else {
                    Str.get_string('nodata', 'local_assessfreq').then((str) => {
                        const noDatastr = document.createElement('h3');
                        noDatastr.innerHTML = str;
                        chartbody.innerHTML = noDatastr.outerHTML;
                        spinner.classList.add('hide'); // Hide spinner if not already hidden.
                        return;
                    }).catch(() => {
                        Notification.exception(new Error('Failed to load string: nodata'));
                    });
                }
            }).fail(() => {
                Notification.exception(new Error('Failed to load card.'));
                return;
            });
        });
    };

    /**
     * Starts the processing of the dashboard.
     */
    const processDashboard = function() {
        // Get summary quiz data.
        Ajax.call([{
            methodname: 'local_assessfreq_get_inprogress_counts',
            args: {},
        }])[0].then((response) => {
            let quizSummary = JSON.parse(response);
            let summaryElement = document.getElementById('local-assessfreq-quiz-dashboard-inprogress-summary-card');
            let summarySpinner = summaryElement.getElementsByClassName('overlay-icon-container')[0];

            summaryElement.classList.remove('hide'); // Show the card.

            // Populate summary card with details.
            Templates.render('local_assessfreq/quiz-dashboard-inprogress-summary-card-content', quizSummary)
            .done((html) => {
                summarySpinner.classList.add('hide');

                let contentcontainer = document.getElementById('local-assessfreq-quiz-dashboard-inprogress-summary-card-content');
                Templates.replaceNodeContents(contentcontainer, html);
            }).fail(() => {
                Notification.exception(new Error('Failed to load quiz counts template.'));
                return;
            });

            getCardCharts();

            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to get quiz summary counts'));
        });
    };

    /**
     * Initialise method for quizzes in progress dashboard rendering.
     */
    DashboardQuizInprogress.init = function(context) {
        contextid = context;
        window.console.log(contextid);
        ZoomModal.init(context); // Create the zoom modal.

        processDashboard();

    };

    return DashboardQuizInprogress;
});
