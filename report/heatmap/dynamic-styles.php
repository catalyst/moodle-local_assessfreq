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
 * Dynamic CSS file.
 *
 * @package   assessfreqreport_heatmap
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('NO_MOODLE_COOKIES', true);

require_once(dirname(__FILE__, 5) . '/config.php');

$PAGE->set_url($url);

header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
header('Expires: ' . gmdate("D, d M Y H:i:s", time() + 60) . ' GMT');
header('Cache-control: max_age = '. 60);
header('Pragma: ');
header('Content-type: text/css; charset=utf-8');

$config = get_config('assessfreqreport_heatmap');

echo <<<CONTENT
#local-assessfreq-report-heatmap .heat-1 {
    background-color: $config->heat1;
}

#local-assessfreq-report-heatmap .heat-2 {
    background-color: $config->heat2;
}

#local-assessfreq-report-heatmap .heat-3 {
    background-color: $config->heat3;
}

#local-assessfreq-report-heatmap .heat-4 {
    background-color: $config->heat4;
}

#local-assessfreq-report-heatmap .heat-5 {
    background-color: $config->heat5;
}

#local-assessfreq-report-heatmap .heat-6 {
    background-color: $config->heat6;
}
CONTENT;
