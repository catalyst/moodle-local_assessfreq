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
 * Javascript for report card display and processing.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    ['core/fragment', 'core/templates', 'core/notification', 'core/ajax'],
    function(Fragment, Templates, Notification, Ajax) {

    /**
     * Module level variables.
     */
    var Reportcard = {};
    var contextid;
    var yearselect;
    var yearselectheatmap;
    var metricselectheatmap;

    var cards = [
        {cardId: 'local-assessfreq-assess-due-month', call: 'assess_by_month'},
        {cardId: 'local-assessfreq-assess-by-activity', call: 'assess_by_activity'},
        {cardId: 'local-assessfreq-assess-due-month-student', call: 'assess_by_month_student'}
    ];

    /**
     * Generic handler to persist user preferences
     *
     * @param {string} type The name of the attribute you're updating
     * @param {string} value The value of the attribute you're updating
     */
     function updateUserPreferences(type, value) {
        var request = {
            methodname: 'core_user_update_user_preferences',
            args: {
                preferences: [
                    {
                        type: type,
                        value: value
                    }
                ]
            }
        };

        Ajax.call([request])[0]
        .fail(function() {
            Notification.exception(new Error('Failed to update user preference'));
        });
    }

    /**
     *
     */
    function getCardCharts() {
        cards.forEach(function(cardData) {
            var cardElement = document.getElementById(cardData.cardId);
            var spinner = cardElement.getElementsByClassName('overlay-icon-container')[0];
            var chartbody = cardElement.getElementsByClassName('chart-body')[0];
            var params = {'data': JSON.stringify({'year' : yearselect, 'call': cardData.call})};

            spinner.classList.remove('hide'); // Show sinner if not already shown.

            Fragment.loadFragment('local_assessfreq', 'get_chart', contextid, params)
            .done(function(response) {

                var context = { 'withtable' : true, 'chartdata' : response };
                Templates.render('core/chart', context)
                .done(function(html, js) {
                    spinner.classList.add('hide'); // Hide sinner if not already hidden.
                    // Load card body.
                    Templates.replaceNodeContents(chartbody, html, js);
                }).fail(function() {
                    Notification.exception(new Error('Failed to load chart template.'));
                    return;
                });
                return;
            }).fail(function() {
                Notification.exception(new Error('Failed to load card year filter'));
                return;
            });
        });
    }

    function yearButtonAction(event) {
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.year != yearselect) { // Only act on certain elements.
            yearselect = element.dataset.year;

            // Save selection as a user preference.
            updateUserPreferences('local_assessfreq_overview_year_preference', yearselect);

            // Update card data based on selected year.
            var yeartitle = document.getElementById('local-assessfreq-report-overview')
                                .getElementsByClassName('local-assessfreq-year')[0];
            yeartitle.innerHTML = yearselect;

            // Process loading for the assessment cards.
            getCardCharts();
        }
    }

    function yearHeatmapButtonAction(event) {
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.year != yearselectheatmap) { // Only act on certain elements.
            yearselectheatmap = element.dataset.year;

            // Save selection as a user preference.
            updateUserPreferences('local_assessfreq_heatmap_year_preference', yearselectheatmap);

            // Update card data based on selected year.
            var yeartitle = document.getElementById('local-assessfreq-report-heatmap')
                                .getElementsByClassName('local-assessfreq-year')[0];
            yeartitle.innerHTML = yearselectheatmap;

            // Process loading heatmap.
            window.console.log('TODO: load heatmap');
        }
    }

    function metricHeatmapButtonAction(event) {
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.metric != metricselectheatmap) {
            metricselectheatmap = element.dataset.metric;

            // Save selection as a user preference.
            updateUserPreferences('local_assessfreq_heatmap_metric_preference', metricselectheatmap);

            // Process loading heatmap.
            window.console.log('TODO: load heatmap');
        }
    }

    function updateHeatmap(links) {
        // Get list of links with active class.
        // Compare to global to see if there are any changes.
        // If list has changed fetch heatmap.
        window.console.log('updating heatmap');

    }

    function moduleListChildrenEvents(element) {
        var links = element.getElementsByTagName('a');
        var all = links[0];

        for (let link of links) {
            let module = link.dataset.module;

            if (module.toLowerCase() === 'all') {
                link.addEventListener("click", function(event){
                    event.preventDefault();
                    // Remove active class from all other links.
                    for (let link of links) {
                        link.classList.remove('active');
                    }
                    updateHeatmap(links); // Call function to update heatmap.
                });
            } else if (module.toLowerCase() === 'close') {
                link.addEventListener("click", function(event){
                    event.preventDefault();
                    event.stopPropagation();

                    var dropdownmenu = document.getElementById('local-assessfreq-heatmap-modules-filter');
                    dropdownmenu.classList.remove('show');

                    updateHeatmap(links); // Call function to update heatmap.
                });

            } else {
                link.addEventListener("click", function(event){
                    event.preventDefault();
                    event.stopPropagation();

                    all.classList.remove('active');

                    event.target.classList.toggle('active');
                    updateHeatmap(links);
                });
            }

        }
    }

    /**
     * Initialise method for report card rendering.
     *
     * @param {integer} context The current context id.
     */
    Reportcard.init = function(context) {
        contextid = context;

        // Set up event listener and related actions for year dropdown on report cards.
        var cardsYearSelectElement = document.getElementById('local-assessfreq-cards-year');
        yearselect = cardsYearSelectElement.getElementsByClassName('active')[0].dataset.year;
        cardsYearSelectElement.addEventListener("click", yearButtonAction);

        // Set up event listener and related actions for year dropdown on heatmp.
        var cardsYearSelectHeatmapElement = document.getElementById('local-assessfreq-heatmap-year');
        yearselectheatmap = cardsYearSelectHeatmapElement.getElementsByClassName('active')[0].dataset.year;
        cardsYearSelectHeatmapElement.addEventListener("click", yearHeatmapButtonAction);

        // Set up event listener and related actions for metric dropdown on heatmp.
        var cardsMetricSelectHeatmapElement = document.getElementById('local-assessfreq-heatmap-metrics');
        metricselectheatmap = cardsMetricSelectHeatmapElement.getElementsByClassName('active')[0].dataset.metric;
        cardsMetricSelectHeatmapElement.addEventListener("click", metricHeatmapButtonAction);

        // Set up event listener and related actions for module dropdown on heatmp.
        var cardsModulesSelectHeatmapElement = document.getElementById('local-assessfreq-heatmap-modules');
        moduleListChildrenEvents(cardsModulesSelectHeatmapElement);

        // Process loading for the assessment cards.
        getCardCharts();

    };

    return Reportcard;
});
