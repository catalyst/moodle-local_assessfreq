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
 * Chart output for chart.js with custom override for aspect config.
 *
 * @package    local_assessfreq
 * @copyright  2020 Matt Porritt <mattp@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/chart_output_chartjs'], function(Output) {

    /**
     * Module level variables.
     */
    var ChartOutput = {};
    var aspectRatio = false;
    var rtLegendoptions = false;

    /**
     * Overrride the config.
     *
     * @protected
     * @param {module:core/chart_axis} axis The axis.
     * @return {Object} The axis config.
     */
    Output.prototype._makeConfig = function() {
        var config = {
            type: this._getChartType(),
            data: {
                labels: this._cleanData(this._chart.getLabels()),
                datasets: this._makeDatasetsConfig()
            },
            options: {
                title: {
                    display: this._chart.getTitle() !== null,
                    text: this._cleanData(this._chart.getTitle())
                }
            }
        };

        // Override legend options with those provided at run time.
        if (rtLegendoptions)  {
            config.options.legend = rtLegendoptions;
        }

        this._chart.getXAxes().forEach(function(axis, i) {
            var axisLabels = axis.getLabels();

            config.options.scales = config.options.scales || {};
            config.options.scales.xAxes = config.options.scales.xAxes || [];
            config.options.scales.xAxes[i] = this._makeAxisConfig(axis, 'x', i);

            if (axisLabels !== null) {
                config.options.scales.xAxes[i].ticks.callback = function(value, index) {
                    return axisLabels[index] || '';
                };
            }
            config.options.scales.xAxes[i].stacked = this._isStacked();
        }.bind(this));

        this._chart.getYAxes().forEach(function(axis, i) {
            var axisLabels = axis.getLabels();

            config.options.scales = config.options.scales || {};
            config.options.scales.yAxes = config.options.scales.yAxes || [];
            config.options.scales.yAxes[i] = this._makeAxisConfig(axis, 'y', i);

            if (axisLabels !== null) {
                config.options.scales.yAxes[i].ticks.callback = function(value) {
                    return axisLabels[parseInt(value, 10)] || '';
                };
            }
            config.options.scales.yAxes[i].stacked = this._isStacked();
        }.bind(this));

        config.options.tooltips = {
            callbacks: {
                label: this._makeTooltip.bind(this)
            }
        };

        config.options.maintainAspectRatio = aspectRatio;

        return config;
    };

    /**
     * Get the aspect ratio setting and initialise the chart.
     */
    ChartOutput.init = function(chartImage, ChartInst, aspect, legend) {
        aspectRatio = aspect;
        rtLegendoptions = legend;
        new Output(chartImage, ChartInst);
    };

    return ChartOutput;

});
