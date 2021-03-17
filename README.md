![Build Status](https://github.com/catalyst/moodle-local_assessfreq/actions/workflows/master.yml/badge.svg?branch=master)

# Assessment frequency #

Advanced assessment reporting for the Moodle LMS.

## Branches ##
The following maps the plugin version to use depending on your Moodle version.

| Moodle verion     | Branch      |
| ----------------- | ----------- |
| Moodle 3.5 to 3.8 | MOODLE_35   |
| Moodle 3.9        | MOODLE_39   |
| Moodle 3.10       | MOODLE_310  |
| Moodle 3.11+      | master      |


## Plugin Installation ##
The following steps will help you install this plugin into your Moodle instance.

1. Clone or copy the code for this repository into your Moodle installation at the following location: `<moodledir>/local/assessfreq`
2. Make sure you have checked-out the correct version of the code, e.g. for Moodle version 3.10: `git checkout MOODLE_39`
3. Run the upgrade: `sudo -u www-data php admin/cli/upgrade` **Note:** the user may be different to www-data on your system.

Once the plugin is installed, next the Moodle setup needs to be performed.

**Note:** It is recommended that installation be completed via the command line instead of the Moodle user interface.

## Moodle setup
TODO: this


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
