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
 * Source plugininfo.
 *
 * @package   local_assessfreq
 * @author    Simon Thornett <simon.thornett@catalyst-eu.net>
 * @copyright Catalyst IT, 2024
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_assessfreq\plugininfo;

use admin_settingpage;
use core\plugininfo\base;
use part_of_admin_tree;

class assessfreqsource extends base {

    /**
     * Finds all enabled plugin names, the result may include missing plugins.
     * @return array of enabled plugins $pluginname=>$pluginname, null means unknown
     */
    public static function get_enabled_plugins() : array {
        $pluginmanager = \core_plugin_manager::instance();
        $plugins = $pluginmanager->get_plugins_of_type('assessfreqsource');

        if (empty($plugins)) {
            return array();
        }

        $enabled = [];
        foreach ($plugins as $name => $plugin) {
            if ($plugin->is_enabled()) {
                $enabled[$name] = $name;
            }
        }
        return $enabled;
    }

    /**
     * Whether the subplugin is enabled.
     *
     * @return bool Whether enabled.
     */
    public function is_enabled() : bool {
        return get_config('assessfreqsource_' . $this->name, 'enabled');
    }

    /**
     * Returns the node name used in admin settings menu for this plugin settings (if applicable)
     *
     * @return string node name or null if plugin does not create settings node (default)
     */
    public function get_settings_section_name() : string {
        return 'assessfreqsource_' . $this->name;
    }

    /**
     * Include the settings.php file from sub plugins if they provide it.
     * This is a copy of very similar implementations from various other subplugin areas.
     */
    public function load_settings(part_of_admin_tree $adminroot, $parentnodename, $hassiteconfig) {
        global $CFG, $USER, $DB, $OUTPUT, $PAGE; // In case settings.php wants to refer to them.
        $ADMIN = $adminroot; // May be used in settings.php.
        $plugininfo = $this; // Also can be used inside settings.php.

        if (!$this->is_installed_and_upgraded()) {
            return;
        }

        if (!$hassiteconfig || !file_exists($this->full_path('settings.php'))) {
            return;
        }

        $section = $this->get_settings_section_name();
        $settings = new admin_settingpage($section, $this->displayname, 'moodle/site:config', $this->is_enabled() === false);
        include($this->full_path('settings.php')); // This may also set $settings to null.

        if ($settings) {
            $ADMIN->add($parentnodename, $settings);
        }
    }
}

