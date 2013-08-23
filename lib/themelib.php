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


defined('MOODLE_INTERNAL') || die();

/**
 * Theme helper class
 *
 * @package    moodlecore
 * @copyright  2013 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class theme_helper {

	/**
	 * Replace a theme by another theme into the DB.
	 *
	 * @param string $themename the theme to replace in the DB by the replacement theme.
	 * @param string $replacementtheme the replacement theme.
	 */
	public function removethemefromdb($themename, $replacementtheme) {
		global $DB;

		$DB->set_field('course', 'theme', $replacementtheme, array('theme' => $themename));
		$DB->set_field('course_categories', 'theme', $replacementtheme, array('theme' => $themename));
		$DB->set_field('user', 'theme', $replacementtheme, array('theme' => $themename));
		$DB->set_field('mnet_host', 'theme', $replacementtheme, array('theme' => $themename));

		// Replace the theme configs.
		if (get_config('core', 'theme') == $themename) {
			set_config('theme', $replacementtheme);
		}
		if (get_config('core', 'thememobile') == $themename) {
			set_config('thememobile', $replacementtheme);
		}
		if (get_config('core', 'themelegacy') == $themename) {
			set_config('themelegacy', $replacementtheme);
		}
		if (get_config('core', 'themetablet') == $themename) {
			set_config('themetablet', $replacementtheme);
		}
	}

	/**
	 * Remove extending themes. The function recursively removes the extending theme.
	 *
	 * @param array $themes array of themes
	 * @param string $themename the theme that need to be removed and all the themes that extend it.
	 * @param string $replacementtheme the replacement theme.
	 */
	public function removesubthemes($themes, $themename, $replacementtheme) {

		// Remove the themes that extend $themename.
		foreach($themes as $theme) {
			$themeconfig = theme_config::load($theme->name);

			// If the theme extend $themename, then remove it from the DB (replace it by $replacementtheme).
			foreach($themeconfig->parents as $parent) {
				if ($parent == $themename) {
					$this->removethemefromdb($themeconfig->name, $replacementtheme);
					$extendedmobilethemes[] = $themeconfig->name;
				}
				break;
			}
		}

		// Remove the themes that extend the themes we just removed.
		// (i.e. remove the themes that extend the themes that extends $themename).
		if (!empty($extendedmobilethemes)) {
			foreach ($extendedmobilethemes as $extendedmobiletheme) {
				$this->removesubthemes($themes, $extendedmobiletheme, $replacementtheme);
			}
		}

	}

	/** 
	 * Remove a theme (and its extending themes) from Moodle.
	 * The themes will be replaced by a specified theme.
	 *
	 * @param string $coretheme the theme to remove.
	 * @param string $replacementtheme the replacement theme.
	 */
	public function removecoretheme($coretheme, $replacementtheme) {
		global $CFG;

		require_once($CFG->dirroot . "/lib/pluginlib.php");
		$pluginmanager = plugin_manager::instance();

		//Check that the theme still exists
		$corethemeinfo = $pluginmanager->get_plugin_info('theme_'.$coretheme);
		if (!empty($corethemeinfo)) {
			// Need to uninstall the plugin from the DB.
			require_once($CFG->dirroot . "/lib/adminlib.php");
			uninstall_plugin('theme', $coretheme);
		}

		// Replace all themes extending the core theme by the replacement theme.
		$this->removethemefromdb($coretheme, $replacementtheme);
		
		$themes = $pluginmanager->get_plugins_of_type('theme');
		$this->removesubthemes($themes, $coretheme, $replacementtheme);
	}
}
