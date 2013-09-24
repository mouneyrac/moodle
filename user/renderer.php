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
 * Print private files tree
 *
 * @package    core_user
 * @copyright  2010 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

class core_user_renderer extends plugin_renderer_base {

    /**
     * Prints authentication user page.
     *
     * @return string the html to display.
     */
    public function user_auth($primaryauth, $secondaryauths) {
        global $CFG, $PAGE;

        // Primary authentication - read only information.
        $primaryauthtitle = html_writer::tag('h3', get_string('primaryauth', 'auth'), array('class' => 'mdl-left'));
        $primaryauthcontent = html_writer::tag('div', $primaryauth->authtype, array());
        $primaryauthhtml = html_writer::tag('div', $primaryauthtitle . $primaryauthcontent, array());

        // Secondary authentications - the user can enable/disable them.

        // Oauth2 linking section.
        require_once($CFG->dirroot . "/lib/oauth2/lib.php");
        $profilelinking = true;
        $oauth2manager = new auth_oauth2_manager();
        $providers = $oauth2manager->get_linkable_providers(true);

        // Retrieve all oauth2 providers.
        $oauth2providers = '';
        foreach ($providers as $provider) {
            if ($provider->linked) {
                $linkedproviders[] = $provider;
            }

            require_once($CFG->dirroot . '/auth/' . $provider->shortname . '/renderer.php');
            $authrendererclass = 'auth_'. $provider->shortname .'_renderer';
            $authrenderer = new $authrendererclass($PAGE, 'auth');
            $oauth2providers .= $authrenderer->link($provider);
        }
        $oauth2boxhtml = html_writer::tag('div', $oauth2providers, array('class' => 'oauth2providers'));

        $oauth2providerstitle = html_writer::tag('h5', get_string('linkproviders', 'auth'));

        // Display the linked oauth2 provider text information.
        if (empty($linkedproviders)) {
            $linkedprovidershtml = html_writer::tag('div', get_string('nolinkedproviders', 'auth'),
                array('class' => 'linkedprovidertext'));
        } else {
            $providernames = array();
            foreach ($linkedproviders as $provider) {
                $providernames[] = $provider->name;
            }
            $providernames = implode(',', $providernames);
            $linkedprovidershtml = html_writer::tag('div', get_string('linkedproviders', 'auth', $providernames),
                array('class' => 'linkedprovidertext'));
        }


        $secondaryauthtitle = html_writer::tag('h3', get_string('secondaryauths', 'auth'), array('class' => 'mdl-left'));
        $secondaryauthscontent = html_writer::tag('div', $linkedprovidershtml . $oauth2providerstitle . $oauth2boxhtml, array());
        $secondaryauthshtml = html_writer::tag('div', $secondaryauthtitle . $secondaryauthscontent, array());

        return $primaryauthhtml . $secondaryauthshtml;
    }


    /**
     * Prints user files tree view
     * @return string
     */
    public function user_files_tree() {
        return $this->render(new user_files_tree);
    }

    public function render_user_files_tree(user_files_tree $tree) {
        if (empty($tree->dir['subdirs']) && empty($tree->dir['files'])) {
            $html = $this->output->box(get_string('nofilesavailable', 'repository'));
        } else {
            $htmlid = 'user_files_tree_'.uniqid();
            $module = array('name'=>'core_user', 'fullpath'=>'/user/module.js');
            $this->page->requires->js_init_call('M.core_user.init_tree', array(false, $htmlid), false, $module);
            $html = '<div id="'.$htmlid.'">';
            $html .= $this->htmllize_tree($tree, $tree->dir);
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Internal function - creates htmls structure suitable for YUI tree.
     */
    protected function htmllize_tree($tree, $dir) {
        global $CFG;
        $yuiconfig = array();
        $yuiconfig['type'] = 'html';

        if (empty($dir['subdirs']) and empty($dir['files'])) {
            return '';
        }
        $result = '<ul>';
        foreach ($dir['subdirs'] as $subdir) {
            $image = $this->output->pix_icon(file_folder_icon(), $subdir['dirname'], 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.s($subdir['dirname']).'</div> '.$this->htmllize_tree($tree, $subdir).'</li>';
        }
        foreach ($dir['files'] as $file) {
            $url = file_encode_url("$CFG->wwwroot/pluginfile.php", '/'.$tree->context->id.'/user/private'.$file->get_filepath().$file->get_filename(), true);
            $filename = $file->get_filename();
            $image = $this->output->pix_icon(file_file_icon($file), $filename, 'moodle', array('class'=>'icon'));
            $result .= '<li yuiConfig=\''.json_encode($yuiconfig).'\'><div>'.$image.' '.html_writer::link($url, $filename).'</div></li>';
        }
        $result .= '</ul>';

        return $result;
    }
}

class user_files_tree implements renderable {
    public $context;
    public $dir;
    public function __construct() {
        global $USER;
        $this->context = context_user::instance($USER->id);
        $fs = get_file_storage();
        $this->dir = $fs->get_area_tree($this->context->id, 'user', 'private', 0);
    }
}

