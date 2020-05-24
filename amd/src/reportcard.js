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

define(['core/ajax', 'core/fragment', 'core/templates', 'core/notification', 'local_assessfreq/calendar'],
   function(Ajax, Fragment, Templates, Notification, Calendar) {

    /**
     * Module level variables.
     */
    var Reportcard = {};
    var contextid;
    var yearselect;
    var yearselectheatmap;
    var metricselectheatmap;
    var timeout;
    var modulesJson = '';
    var heatmapOptionsJson = '';

    const cards = [
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
    const updateUserPreferences = (type, value) => {
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
        .fail(() => {
            Notification.exception(new Error('Failed to update user preference'));
        });
    };

     /**
      * For each of the cards on the dashbaord get their corresponding chart data.
      * Data is based on the year variable from the corresponding dropdown.
      * Chart data is loaded via ajax.
      *
      */
     const getCardCharts = () => {
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
                 .done((html, js) => {
                     spinner.classList.add('hide'); // Hide sinner if not already hidden.
                     // Load card body.
                     Templates.replaceNodeContents(chartbody, html, js);
                 }).fail(() => {
                     Notification.exception(new Error('Failed to load chart template.'));
                     return;
                 });
                 return;
             }).fail(() => {
                 Notification.exception(new Error('Failed to load card year filter'));
                 return;
             });
         });
     };

     /**
      * Get and process the selected year from the dropdown,
      * and update the corresponding user perference.
      *
      * @param {event} event The triggered event for the element.
      */
     const yearButtonAction = (event) => {
         var element = event.target;

         if (element.tagName.toLowerCase() === 'a' && element.dataset.year != yearselect) { // Only act on certain elements.
             yearselect = element.dataset.year;

             // Save selection as a user preference.
             updateUserPreferences('local_assessfreq_overview_year_preference', yearselect);

             // Update card data based on selected year.
             var yeartitle = document.getElementById('local-assessfreq-report-overview')
                 .getElementsByClassName('local-assessfreq-year')[0];
             yeartitle.innerHTML = yearselect;

             getCardCharts(); // Process loading for the assessment cards.
         }
     };

    /**
     * Quick and dirty debounce method for the heatmap settings menu.
     * This stops the ajax method that updates the heatmap from being updated
     * while the user is still checking options.
     *
     */
    const updateHeatmapDebounce = () => {
        clearTimeout(timeout);
        timeout = setTimeout(updateHeatmap(), 750);
    };

    const generateHeatmap = () => {
        let heatmapOptions = JSON.parse(heatmapOptionsJson);
        let year = parseInt(heatmapOptions.year);
        let metric = heatmapOptions.metric;
        let modules = heatmapOptions.modules;

        Calendar.generate(year, 0, 11, metric, modules).then(calendar => {
            let calendarContainer = document.getElementById('local-assessfreq-report-heatmap-months');
            calendarContainer.innerHTML = calendar.innerHTML;
            return;
        }).catch(() => {
            Notification.exception(new Error('Failed to calendar.'));
            return;
        });
    };

    /**
     * Update the heatmap based on the current filter settings.
     *
     */
    const updateHeatmap = () => {
        // Get current state of select menu items.with
        var cardsModulesSelectHeatmapElement = document.getElementById('local-assessfreq-heatmap-modules');
        var links = cardsModulesSelectHeatmapElement.getElementsByTagName('a');
        var modules = [];

        for (var i = 0; i < links.length; i++) {
            if (links[i].classList.contains('active')) {
                let module = links[i].dataset.module;
                modules.push(module);
            }
        }

        // Save selection as a user preference.
        if (modulesJson !== JSON.stringify(modules)) {
            modulesJson = JSON.stringify(modules);
            updateUserPreferences('local_assessfreq_heatmap_modules_preference', modulesJson);
        }

        // Build settings object.
        var optionsObj = {
                'year' : yearselectheatmap,
                'metric' : metricselectheatmap,
                'modules' : modules
        };

        var optionsJson = JSON.stringify(optionsObj);

        if(optionsJson !== heatmapOptionsJson) { // Compare to global to see if there are any changes.
            // If list has changed fetch heatmap and update user preference.
            heatmapOptionsJson = optionsJson;

            generateHeatmap();
        }
    };

    /**
     * Get and process the selected year from the dropdown for the heatmap display,
     * and update the corresponding user perference.
     *
     * @param {event} event The triggered event for the element.
     */
    const yearHeatmapButtonAction = (event) => {
        event.preventDefault();
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.year != yearselectheatmap) { // Only act on certain elements.
            yearselectheatmap = element.dataset.year;

            // Save selection as a user preference.
            updateUserPreferences('local_assessfreq_heatmap_year_preference', yearselectheatmap);

            // Update card data based on selected year.
            var yeartitle = document.getElementById('local-assessfreq-report-heatmap')
                .getElementsByClassName('local-assessfreq-year')[0];
            yeartitle.innerHTML = yearselectheatmap;

            updateHeatmapDebounce(); // Call function to update heatmap.
        }
    };

    /**
     * Get and process the selected assessment metric from the dropdown for the heatmap display,
     * and update the corresponding user perference.
     *
     * @param {event} event The triggered event for the element.
     */
    const metricHeatmapButtonAction = (event) => {
        event.preventDefault();
        var element = event.target;

        if (element.tagName.toLowerCase() === 'a' && element.dataset.metric != metricselectheatmap) {
            metricselectheatmap = element.dataset.metric;

            // Save selection as a user preference.
            updateUserPreferences('local_assessfreq_heatmap_metric_preference', metricselectheatmap);

            updateHeatmapDebounce(); // Call function to update heatmap.
        }
    };

    /**
     * Add the event listeners to the modules in the module select dropdown.
     *
     * @param {element} element The dropdown HTML element that contains the list of modules as links.
     */
    const moduleListChildrenEvents = (element) => {
        var links = element.getElementsByTagName('a');
        var all = links[0];

        for (var i = 0; i < links.length; i++) {
            let module = links[i].dataset.module;

            if (module.toLowerCase() === 'all') {
                links[i].addEventListener("click", function(event){
                    event.preventDefault();
                    // Remove active class from all other links.
                    for (var j = 0; j < links.length; j++) {
                        links[j].classList.remove('active');
                    }
                    updateHeatmapDebounce(); // Call function to update heatmap.
                });
            } else if (module.toLowerCase() === 'close') {
                links[i].addEventListener("click", function(event){
                    event.preventDefault();
                    event.stopPropagation();

                    var dropdownmenu = document.getElementById('local-assessfreq-heatmap-modules-filter');
                    dropdownmenu.classList.remove('show');

                    updateHeatmapDebounce(); // Call function to update heatmap.
                });

            } else {
                links[i].addEventListener("click", function(event){
                    event.preventDefault();
                    event.stopPropagation();

                    all.classList.remove('active');

                    event.target.classList.toggle('active');
                    updateHeatmapDebounce();
                });
            }

        }
    };

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

        // Get the data for the heatmap.
        updateHeatmap();

    };

    return Reportcard;
});
