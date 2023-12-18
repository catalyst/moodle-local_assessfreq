![Build Status](https://github.com/catalyst/moodle-local_assessfreq/actions/workflows/master.yml/badge.svg?branch=master)

# Assessment frequency
Advanced assessment reporting for the Moodle LMS. It adds four new
reports which can be accessed from

youroodlesite/admin/category.php?category=local_assessfreq_reports

### The new reports
* Assessment dashboard
* Quiz dashboard
* Quizzes in progress dashboard
* Student Search

#### Assessment Dashboard
The Assessment Dashboard displays a page divided into summary cards at the top and a heatmap
calendar section below that. It can be used to identify “bunching” of
assignment submission dates. The heatmap calendar shows colours to
indicate the density of submissions required on each day.
There is a year selector for both the summary and heatmap sections

### Quiz dashboard
The Quiz Dashboard shows a summary of quiz data after selecting a course and quiz.It displays a summary of the quiz e.g. open and closing times and participant count. Below that is a report on student attempts.

### Quizzes in progress dashboard
The Quizzes in progress dashboard shows a sitewide summary of quizzes in progress based on the open and closing dates.

### Student search
The Student search report shows student attempt status site wide with buttons for "hours behind" and "hours ahead.

## Branches ##
The following maps the plugin version to use depending on your Moodle version.

| Moodle version        | Branch            |
| ----------------------| ------------------|
| Moodle 3.5 to 3.8     | MOODLE_35         |
| Moodle 3.9            | MOODLE_39         |
| Moodle 3.10           | MOODLE_310        |
| Moodle 3.11           | MOODLE_311_STABLE |
| Moodle 4.0 to 4.1     | MOODLE_400_STABLE |
| Moodle 4.2 and higher | MOODLE_402_STABLE |

## Plugin Installation ##
The following steps will help you install this plugin into your Moodle instance.

1. Clone or copy the code for this repository into your Moodle installation at the following location: `<moodledir>/local/assessfreq`
2. Make sure you have checked-out the correct version of the code, e.g. for Moodle version 3.10: `git checkout MOODLE_39`
3. Run the upgrade: `sudo -u www-data php admin/cli/upgrade` **Note:** the user may be different to www-data on your system.

Once the plugin is installed, next the Moodle setup needs to be performed.

**Note:** It is recommended that installation be completed via the command line instead of the Moodle user interface.

## Moodle setup
This plugin creates two new scheduled tasks,
\local_assessfreq\task\data_process
\local_assessfreq\task\quiz_tracking
Which can be viewed on yourmoodlesite.edu/admin/tool/task/scheduledtasks.php
After installation Cron must be run to populate the tables that allow the reports to work.



## License ##

2020 Matt Porritt <mattp@catalyst-au.net>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.
