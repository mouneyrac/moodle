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
 * Renderer for auth_manual plugin
 *
 * @package    auth_manual
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/authlib.php');

/**
 * Standard HTML output renderer for auth_manual
 */
class auth_manual_renderer extends auth_plugin_renderer_base {
    
    /**
     * Display login form (same as login.php + param to trigger link action)
     * 
     * TODO: This function will be refactored by MDL-29940. 
     *       This is approximatly the same code as login_forms.php
     *
     * @param string $username
     * @param array $additionalparams the "hidden" param to add to the auth login form
     */
    public function loginform($username, $additionalparams) {
        global $CFG;
        
        // Build hidden params.
        $hiddenparams = '';
        foreach ($additionalparams as $name => $value) {
            $hiddenparams .= html_writer::empty_tag('input',
                    array('type' => 'hidden', 'name' => $name, 'value' => $value));
        }
        
        $title = html_writer::tag('h4',
                get_string('existingaccount', 'auth'),
                array('class' => ''));
        if (!empty($CFG->loginpasswordautocomplete)) {
            $autocomplete = 'autocomplete="off"';
        } else {
            $autocomplete = '';
        }

    $htmlalreadyregistered = <<<EOF
      <div class="subcontent loginsub">
        <div class="desc">
EOF;

            $htmlalreadyregistered .= get_string("loginusing");
            $htmlalreadyregistered .= '<br/>';
            $htmlalreadyregistered .= '('.get_string("cookiesenabled").')';
            $htmlalreadyregistered .= $this->help_icon('cookiesenabled');

            $htmlalreadyregistered .= <<<EOF
        </div>
EOF;

          if (!empty($errormsg)) {
              $htmlalreadyregistered .= '<div class="loginerrors">';
              $htmlalreadyregistered .= $this->error_text($errormsg);
              $htmlalreadyregistered .= '</div>';
          }

        $htmlalreadyregistered .= <<<EOF
        <form action="
EOF;

$htmlalreadyregistered .= $CFG->httpswwwroot;

            $htmlalreadyregistered .= <<<EOF
/login/index.php" method="post" id="login"
EOF;
            $htmlalreadyregistered .= $autocomplete;

      $htmlalreadyregistered .= <<<EOF
            >
          <div class="loginform">
            <div class="form-label"><label for="username">
EOF;
      $htmlalreadyregistered .= get_string("username");
      $htmlalreadyregistered .= '</label></div>
            <div class="form-input">
              <input type="text" name="username" id="username" size="15" value="';
      $htmlalreadyregistered .= s($username);
      $htmlalreadyregistered .= '" />
            </div>
            <div class="clearer"><!-- --></div>
            <div class="form-label"><label for="password">';
      $htmlalreadyregistered .= get_string("password");
      $htmlalreadyregistered .= '</label></div>
            <div class="form-input"> ' 
            .$hiddenparams.'
            
              <input type="password" name="password" id="password" size="15" value="" '. $autocomplete .' />
              <input type="submit" id="loginbtn" value="';
              $htmlalreadyregistered .= get_string("login");
              $htmlalreadyregistered .= '" />
            </div>
          </div>
            <div class="clearer"><!-- --></div>';
               if (isset($CFG->rememberusername) and $CFG->rememberusername == 2) {
                   $htmlalreadyregistered .= '

              <div class="rememberpass">
                  <input type="checkbox" name="rememberusername" id="rememberusername" value="1"';
                   if ($username) {$htmlalreadyregistered .= 'checked="checked"';}
                   $htmlalreadyregistered .= '/>
                  <label for="rememberusername">';
                   $htmlalreadyregistered .= get_string('rememberusername', 'admin');
                   $htmlalreadyregistered .= '</label>
              </div>';
              }
          $htmlalreadyregistered .= '<div class="clearer"><!-- --></div>
          <div class="forgetpass"><a href="forgot_password.php">';
          $htmlalreadyregistered .= get_string("forgotten");
          $htmlalreadyregistered .= '</a></div>
        </form>
          </div>';

          return html_writer::tag('div', $title . $htmlalreadyregistered,
                  array('class' => 'alreadyregistered'));
    }
}