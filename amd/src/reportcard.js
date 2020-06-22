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

define(['core/ajax', 'core/fragment', 'core/templates', 'core/notification', 'local_assessfreq/calendar', 'core/str',
    'core/modal_factory', 'local_assessfreq/modal_large'],
function(Ajax, Fragment, Templates, Notification, Calendar, Str, ModalFactory, ModalLarge) {

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
    var modalObj;
    const spinner = '<p class="text-center">'
        + '<i class="fa fa-spinner fa-pulse fa-2x fa-fw"></i>'
        + '</p>';

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
    const updateUserPreferences = function(type, value) {
        var request = {
            methodname: 'core_user_update_user_preferences',
            args: {
                preferences: [{type: type, value: value}]
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
    const getCardCharts = function() {
        cards.forEach((cardData) => {
            let cardElement = document.getElementById(cardData.cardId);
            let spinner = cardElement.getElementsByClassName('overlay-icon-container')[0];
            let chartbody = cardElement.getElementsByClassName('chart-body')[0];
            let params = {'data': JSON.stringify({'year' : yearselect, 'call': cardData.call})};

            spinner.classList.remove('hide'); // Show sinner if not already shown.
            Fragment.loadFragment('local_assessfreq', 'get_chart', contextid, params)
            .done((response) => {
                var context = { 'withtable' : true, 'chartdata' : response };
                Templates.render('core/chart', context).done((html, js) => {
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
    const yearButtonAction = function(event) {
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
    const updateHeatmapDebounce = function() {
        clearTimeout(timeout);
        timeout = setTimeout(updateHeatmap(), 750);
    };

    /**
     * Start heatmap generation.
     *
     */
    const generateHeatmap = function() {
        let heatmapOptions = JSON.parse(heatmapOptionsJson);
        let year = parseInt(heatmapOptions.year);
        let metric = heatmapOptions.metric;
        let modules = heatmapOptions.modules;
        let heatmapContainer = document.getElementById('local-assessfreq-report-heatmap');
        let spinner = heatmapContainer.getElementsByClassName('overlay-icon-container')[0];

        spinner.classList.remove('hide'); // Show spinner if not already shown.

        Calendar.generate(year, 0, 11, metric, modules).then(calendar => {
            let calendarContainer = document.getElementById('local-assessfreq-report-heatmap-months');
            calendarContainer.innerHTML = calendar.innerHTML;
            spinner.classList.add('hide'); // Hide sinner if not already hidden.
            return;
        }).catch(() => {
            Notification.exception(new Error('Failed to calendar.'));
            return;
        });
    };

    const updateDownload = ({year, metric, modules}) => {
        let downloadForm = document.getElementById('local-assessfreq-heatmap-form');
        let formElements = downloadForm.elements;
        let toRemove = new Array();

        if (modules.length == 0) {
            modules = ['all'];
        }

        for (let i = 0; i < formElements.length; i++) {
            if (formElements[i] === undefined) {
                continue;
            }
            // Update year field.
            if((formElements[i].type === 'hidden') && (formElements[i].name === 'year')) {
                formElements[i].value = year;
                continue;
            }

            // Update metric field.
            if((formElements[i].type === 'hidden') && (formElements[i].name === 'metric')) {
                formElements[i].value = metric;
                continue;
            }

            // Update module fields.
            if((formElements[i].type === 'hidden') && (formElements[i].name.startsWith('modules'))) {
                toRemove.push(formElements[i]);
                continue;
            }
        }

        for (const element of toRemove) {
            element.remove();
        }

        for (let i = 0; i < modules.length; i++) {
            let input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'modules[' + modules[i] + ']';
            input.value = modules[i];

            downloadForm.appendChild(input);
        }
    };

    /**
     * Update the heatmap based on the current filter settings.
     *
     */
    const updateHeatmap = function() {
        // Get current state of select menu items.
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

            // Update the download options.
            updateDownload(optionsObj);
        }
    };

    /**
     * Get and process the selected year from the dropdown for the heatmap display,
     * and update the corresponding user perference.
     *
     * @param {event} event The triggered event for the element.
     */
    const yearHeatmapButtonAction = function(event) {
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
    const metricHeatmapButtonAction = function(event) {
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
     * @param {Object} element The dropdown HTML element that contains the list of modules as links.
     */
    const moduleListChildrenEvents = function(element) {
        var links = element.getElementsByTagName('a');
        var all = links[0];

        for (var i = 0; i < links.length; i++) {
            let module = links[i].dataset.module;

            if (module.toLowerCase() === 'all') {
                links[i].addEventListener('click', function(event){
                    event.preventDefault();
                    // Remove active class from all other links.
                    for (var j = 0; j < links.length; j++) {
                        links[j].classList.remove('active');
                    }
                    updateHeatmapDebounce(); // Call function to update heatmap.
                });
            } else if (module.toLowerCase() === 'close') {
                links[i].addEventListener('click', function(event){
                    event.preventDefault();
                    event.stopPropagation();

                    var dropdownmenu = document.getElementById('local-assessfreq-heatmap-modules-filter');
                    dropdownmenu.classList.remove('show');

                    updateHeatmapDebounce(); // Call function to update heatmap.
                });

            } else {
                links[i].addEventListener('click', function(event){
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
     *
     */
    const zoomGraph = function(event) {
        let title = event.target.parentElement.dataset.title;
        let call = event.target.parentElement.dataset.call;

        let params = {'data': JSON.stringify({'year' : yearselect, 'call': call})};

        Fragment.loadFragment('local_assessfreq', 'get_chart', contextid, params)
        .done((response) => {
            var context = { 'withtable' : true, 'chartdata' : response };
            modalObj.setTitle(title);
            modalObj.setBody(Templates.render('core/chart', context));
            modalObj.show();
            return;
        }).fail(() => {
            Notification.exception(new Error('Failed to load card year filter'));
            return;
        });

    };

    /**
     * Create the modal window for graph zooming.
     *
     * @private
     */
    const createModal = function() {
        return new Promise((resolve, reject) => {
            Str.get_string('loading', 'core').then((title) => {
                // Create the Modal.

                ModalFactory.create({
                    type: ModalLarge.TYPE,
                    title: title,
                    body: spinner
                })
                .done((modal) => {
                    modalObj = modal;
                    resolve();
                });
            }).catch(() => {
                reject(new Error('Failed to load string: loading'));
            });
        });
    };

    /**
     * Initialise method for report card rendering.
     *
     * @param {integer} context The current context id.
     */
    Reportcard.init = function(context) {
        contextid = context;

        // Set up event listener and related actions for year dropdown on report cards.
        let cardsYearSelectElement = document.getElementById('local-assessfreq-cards-year');
        yearselect = cardsYearSelectElement.getElementsByClassName('active')[0].dataset.year;
        cardsYearSelectElement.addEventListener('click', yearButtonAction);

        // Set up event listener and related actions for year dropdown on heatmp.
        let cardsYearSelectHeatmapElement = document.getElementById('local-assessfreq-heatmap-year');
        yearselectheatmap = cardsYearSelectHeatmapElement.getElementsByClassName('active')[0].dataset.year;
        cardsYearSelectHeatmapElement.addEventListener('click', yearHeatmapButtonAction);

        // Set up event listener and related actions for metric dropdown on heatmp.
        let cardsMetricSelectHeatmapElement = document.getElementById('local-assessfreq-heatmap-metrics');
        metricselectheatmap = cardsMetricSelectHeatmapElement.getElementsByClassName('active')[0].dataset.metric;
        cardsMetricSelectHeatmapElement.addEventListener('click', metricHeatmapButtonAction);

        // Set up event listener and related actions for module dropdown on heatmp.
        let cardsModulesSelectHeatmapElement = document.getElementById('local-assessfreq-heatmap-modules');
        moduleListChildrenEvents(cardsModulesSelectHeatmapElement);

        // Set up zoom event listeners.
        let dueMonthZoom = document.getElementById('local-assessfreq-assess-due-month-zoom');
        dueMonthZoom.addEventListener('click', zoomGraph);

        let dueActivityZoom = document.getElementById('local-assessfreq-assess-by-activity-zoom');
        dueActivityZoom.addEventListener('click', zoomGraph);

        let dueStudentZoom = document.getElementById('local-assessfreq-assess-due-month-student-zoom');
        dueStudentZoom.addEventListener('click', zoomGraph);

        // Create the zoom modal.
        createModal();

        // Process loading for the assessment cards.
        getCardCharts();

        // Get the data for the heatmap.
        updateHeatmap();

    };

    return Reportcard;
});
