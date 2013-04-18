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
 * @copyright 2012 Jerome Mouneyrac
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package core_auth
 *
 * Authentication Plugin: Oauth2 Authentication
 * If the email doesn't exist, then the auth plugin creates the user.
 * If the email exist (and the user has for auth plugin this current one),
 * then the plugin login the user related to this email.
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); // It must be included from a Moodle page.
}

require_once($CFG->libdir . '/authlib.php');

/**
 * Oauth2 authentication plugin.
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @copyright 2012 Jerome Mouneyrac
 */
abstract class auth_plugin_oauth2 extends auth_plugin_base {

    /** @var string Name of the oauth provider (PARAM_TEXT) */
    public $name;

    /** @var string authentication plugin folder name (PARAM_ALPHANUM) */
    public $shortname;

    /** @var moodle_url the link to the provider for authentication request */
    public $logourl;

    /** @var oauth2 client instance */
    public $oauth2client;

    /** @var oauth2 client class name */
    public $clientclassname;

    /** @var string path to the lib file containing the client class */
    public $clientlibpath;

    /**
     * Return url (it will be send to the callback by the provider.
     *
     * @return moodle_url
     */
    protected function get_returnurl() {
        global $CFG;
        // Build the return url.
        $loginurl = '/login/index.php';
        if (!empty($CFG->alternateloginurl)) {
            $loginurl = $CFG->alternateloginurl;
        }
        $returnurlparams = array('authprovider' => $this->shortname);
        return new moodle_url($loginurl, $returnurlparams);
    }

    /**
     * Constructor.
     * It requires the following to be set:
     *      $this->shortname
     *      $this->name
     *      $this->clientclassname
     *      $this->clientlibpath
     */
    public function __construct() {
        global $CFG;

        // Check that a shortname is set.
        if (empty($this->shortname)) {
            throw new coding_exception('Your oauth2 plugin needs to set $this->shortname in __construct()');
        }

        // Check the shortname start by oauth2.
        if (strpos($this->shortname, 'oauth2') !== 0) {
            throw new coding_exception('Your oauth2 plugin shortname needs to start by oauth2');
        }

        // Check that shortname is a plugin
        validate_param($this->shortname, PARAM_PLUGIN);

        // Check that the plugin is located in a folder named like the shortname.
        if (!is_dir($CFG->dirroot . '/auth/' . $this->shortname)) {
            throw new coding_exception('Your oauth2 plugin must be located in a folder named /auth/' . $this->shortname);
        }

        // Check that the plugin come with a logo.
        if (empty($this->logourl)) {
            if (file_exists($CFG->dirroot . '/auth/' . $this->shortname . '/pix/logo.png')) {
                $this->logourl = new moodle_url('/auth/' . $this->shortname . '/pix/logo.png');
            } else {
                throw new coding_exception('You must either set $this->logourl,
                    either put a logo.jpg file into \'/auth/' . $this->shortname . '/pix/\'');
            }
        }

        // Check client class name.
        if (empty($this->oauth2client)) {
            if (!empty($this->clientclassname) and !empty($this->clientlibpath)) {
                if (!file_exists($this->clientlibpath)) {
                    throw new coding_exception('The client file lib doesn\'t exist: ' . $this->clientlibpath);
                }
                require_once($this->clientlibpath);
                $clientclassname = $this->clientclassname;

                    $this->oauth2client = new $clientclassname(get_config('auth/' . $this->shortname, 'clientid'),
                            get_config('auth/' . $this->shortname, 'clientsecret'), $this->get_returnurl());
            } else {
                throw new coding_exception('You must set $this->clientclassname and $this->clientlibpath in _construct().
                    This class should extend oauth2_client class.');
            }
        }

        // Settings related to auth_plugin_base class.
        $this->authtype = $this->shortname;
        $this->roleauth = 'auth_' . $this->shortname;
        $this->errorlogtag = '[AUTH ' . $this->shortname . '] ';
        $this->config = get_config('auth/' . $this->shortname);
    }

    /**
     * Prevent authenticate_user_login() to update the password in the DB
     * @return boolean
     */
    public function prevent_local_passwords() {
        return true;
    }

    /**
     * Authenticates user against the selected authentication provide (Google, Facebook...)
     *
     * @param string $username The username (with system magic quotes)
     * @param string $password The password (with system magic quotes)
     * @return bool Authentication success or failure.
     */
    public function user_login($username, $password) {
        global $DB, $CFG;

        // Retrieve the user matching username.
        $user = $DB->get_record('user', array('username' => $username,
            'mnethostid' => $CFG->mnet_localhost_id));

        // Username must exist.
        if (!empty($user)) {
            return true;
        }

        return false;
    }

    /**
     * Returns true if this authentication plugin is 'internal'.
     *
     * @return bool
     */
    public function is_internal() {
        return false;
    }

    /**
     * Returns true if this authentication plugin can change the user's
     * password.
     *
     * @return bool
     */
    public function can_change_password() {
        return false;
    }

    /**
     * Authentication hook - is called every time user hit the login page
     * The code is run only if the param code is mentionned.
     */
    public function loginpage_hook() {
        global $USER, $SESSION, $CFG, $DB;

        // If waiting for create user confirmation then do nothing.
        if (optional_param('createorlinkrequest', false, PARAM_BOOL)) {
            return '';
        }

        // Check the authorization code and that we are the right oauth2 plugin.
        $authorizationcode = optional_param('oauth2code', '', PARAM_RAW);
        $authprovider = optional_param('authprovider', '', PARAM_ALPHANUMEXT);
        if (!empty($authorizationcode) and ($authprovider == $this->shortname)) {

            if ($this->oauth2client->upgrade_token($authorizationcode)) {

                // Load the authenticated user info.
                $this->oauth2client->retrieve_auth_user_info();

                // Throw an error if the email address is not verified.
                if (!$this->oauth2client->oauth2user->verified) {
                    throw new moodle_exception('emailaddressmustbeverified', 'auth');
                }

                // Prohibit login if email belongs to the prohibited domain.
                if (!get_config('auth/' . $this->shortname, 'nomailrestriction') and
                        $err = email_is_not_allowed($this->oauth2client->oauth2user->email)) {
                    throw new moodle_exception($err, 'auth_' . $this->shortname);
                }

                // Email address must be send by the provider - provider not sending email not yet supported
                // Email address must be Moodle valid.
                if (empty($this->oauth2client->oauth2user->email) or
                    $this->oauth2client->oauth2user->email != clean_param($this->oauth2client->oauth2user->email, PARAM_EMAIL)) {
                    throw new moodle_exception('couldnotgetuseremail');
                }

                // Detect it's a linking call.
                // If yes then redirect to the profile page.
                $profilelinking = optional_param('profilelinking', false, PARAM_BOOL);
                if ($profilelinking) {
                    require_sesskey();

                    // Link the account.
                    $this->link_account($this->oauth2client->oauth2user->id);

                    if ($testsession = optional_param('throwtestsession', 0, PARAM_INT)) {
                        redirect(new moodle_url('/login/index.php?testsession='.$testsession));
                    } else {
                        // Redirect back to edit profile page.
                        redirect(new moodle_url('/user/edit.php?id='.$USER->id.'&course=1'));
                    }
                }

                // Set URL redirection.
                if (!empty($SESSION->wantsurl)) {
                    $cleanedwanturls = clean_param($SESSION->wantsurl, PARAM_LOCALURL);
                }
                if (!empty($cleanedwanturls)) {
                    $urltogo = $SESSION->wantsurl;    // Because it's an address in this site.
                    unset($SESSION->wantsurl);
                } else {
                    // No wantsurl stored or external - go to homepage.
                    $urltogo = $CFG->wwwroot . '/';
                    unset($SESSION->wantsurl);
                }

                // Retrieve existing linked account.
                $usersql = 'SELECT u.* FROM {user_idps} idps, {user} u
                            WHERE idps.provideruserid = :provideruserid AND idps.component = :component
                                  AND u.id = idps.userid AND u.deleted = :deleted
                                  AND u.confirmed = :confirmed AND u.mnethostid = :mnethostid';
                $user = $DB->get_record_sql($usersql,
                        array('provideruserid' => $this->oauth2client->oauth2user->id, 'component' => 'auth_'.$this->shortname,
                            'deleted' => 0, 'confirmed' => 1, 'mnethostid' => $CFG->mnet_localhost_id));

                // Avoid to commit an incomplete new user if ever a crash appears.
                $transaction = $DB->start_delegated_transaction();

                // No account is not linked yet.
                if (empty($user)) {
                    // Check if a user has the verified email address sent back by the provider - don't bother with auth = oauth2xxxx because.
                    // Authenticate_user() will fail it if it's not 'oauth2xxxx'.
                    $user = $DB->get_record('user', array('email' => $this->oauth2client->oauth2user->email,
                        'deleted' => 0, 'confirmed' => 1, 'mnethostid' => $CFG->mnet_localhost_id));

                    // If an existing user acount with matching email is found, we display a form to request the user to link his account.
                    // OR If the user didn't confirm creation, then redirect to confirmation page.
                    $confirmcreate = optional_param('confirmcreate', false, PARAM_BOOL);
                    if (!empty($user) or !$confirmcreate) {

                        // Commit the transaction.
                        // Actually we have nothing yet to commit but it does hurt o do it properly.
                        $DB->commit_delegated_transaction($transaction);

                        $confirmationpage = $this->get_returnurl(); //login page
                        $confirmationpage->param('createorlinkrequest', true);
                        $confirmationpage->param('oauth2code', $authorizationcode);
                        $confirmationpage->param('authprovider', $authprovider);
                        if (!empty($user)) {
                            $confirmationpage->param('useremailexists', true);
                        }
                        redirect($confirmationpage);
                    }

                    $user = $this->create_user();

                    $this->link_account($this->oauth2client->oauth2user->id, $user->id);
                }

                // Authenticate the user.
                $authenticateduser = $this->authenticate_user($user, null, $this->shortname);
                // Note: if $user is empty then it means the authentication failed.
                // However we still continue the redirection (at worst the user will go back to login page).
                if ($authenticateduser) {

                    complete_user_login($authenticateduser);

                    // Set URL redirection to the user edit page.
                    if (user_not_fully_set_up($USER)) {
                        $urltogo = $CFG->wwwroot . '/user/edit.php';
                        // We don't delete $SESSION->wantsurl yet, so we get there later.
                    }

                    add_to_log(SITEID, 'auth_' . $this->shortname, '', '', $authenticateduser->username . '/'
                        . $this->oauth2client->oauth2user->email . '/' . $authenticateduser->id);
                }

                // We are going to redirect, it's time to commit the new user.
                $transaction->allow_commit();

                redirect($urltogo);
            } else {
                throw new moodle_exception('couldnotgetaccesstoken', 'auth');
            }
        }
    }

    /**
     * Authenticates a user against the oauth2 plugin
     *
     * if the authentication is successful, it returns a
     * valid $user object from the 'user' table.
     *
     * After authenticate_user_login() returns success, you will need to
     * log that the user has logged in, and call complete_user_login() to set
     * the session up.
     *
     * @param object $user an existing user to authenticate
     * @return object the authenticated user - false is the user could not be authenticated
     */
    private function authenticate_user($user) {
        global $CFG, $DB;

        // Use this auth plugin if auth not set.
        $auth = empty($user->auth) ? $this->shortname : $user->auth;

        // Don't log suspended user.
        if (!empty($user->suspended)) {
            add_to_log(SITEID, 'login', 'error', 'index.php', $user->username);
            error_log('[client ' . getremoteaddr() . "]  $CFG->wwwroot  Suspended Login:  $user->username  " . $_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        // If the primary authentication is disabled or nologin, then the user can't authenticate.
        if ($auth == 'nologin' or !is_enabled_auth($auth)) { //
            add_to_log(SITEID, 'login', 'error', 'index.php', $user->username);
            error_log('[client ' . getremoteaddr() . "]  $CFG->wwwroot  Disabled Login:  $user->username  " . $_SERVER['HTTP_USER_AGENT']);
            return false;
        }

        // For some reason auth isn't set yet.
        if (empty($user->auth)) {
            $DB->set_field('user', 'auth', $auth, array('username' => $user->username));
            $user->auth = $auth;
        }

        // Prevent firstaccess from remaining 0 for manual account that never required confirmation.
        if (empty($user->firstaccess)) {
            $DB->set_field('user', 'firstaccess', $user->timemodified, array('id' => $user->id));
            $user->firstaccess = $user->timemodified;
        }

        // update user record from external DB.
        $userauth = get_auth_plugin($user->auth);
        if ($userauth->is_synchronised_with_external()) {
            $user = update_user_record($user->username);
        }

        // Sync roles for this user with the primary authentication plugin.
        $userauth->sync_roles($user);

        // Trigger all possible post authentication hook.
        $authsenabled = get_enabled_auth_plugins();
        foreach ($authsenabled as $hau) {
            $hauth = get_auth_plugin($hau);
            $hauth->user_authenticated_hook($user, $user->username, null);
        }

        return $user;
    }

    /**
     * Create a user from the information returned by the identity provider.
     *
     * @return object created user
     */
    private function create_user() {
        global $CFG, $DB;

        // Do not try to authenticate non-existent accounts when user creation is not disabled.
        if (!empty($CFG->authpreventaccountcreation)) {
            throw new coding_exception('Sorry CFG->authpreventaccountcreation is set to true.');
        }

        // Check that the auth plugin allow user creation.
        if (!get_config('auth/' . $this->shortname, 'createuser')) {
            throw new coding_exception('Sorry the Oauth2 auth plugin
                allows no account creation');
        }

        // Get following incremented username.
        $lastusernumber = get_config('auth/' . $this->shortname, 'lastusernumber');
        if (empty($lastusernumber)) {
            $lastusernumber = 1;
        } else {
            $lastusernumber = $lastusernumber++;
        }

        // Check the user doesn't exist.
        $nextuser = $DB->get_record('user', array('username' => get_config('auth/' . $this->shortname, 'userprefix') . $lastusernumber));
        while (!empty($nextuser)) {
            $lastusernumber = $lastusernumber + 1;
            $nextuser = $DB->get_record('user', array('username' => get_config('auth/' . $this->shortname, 'userprefix') . $lastusernumber));
        }
        set_config('lastusernumber', $lastusernumber, 'auth/' . $this->shortname);
        $username = get_config('auth/' . $this->shortname, 'userprefix') . $lastusernumber;

        // Retrieve more information from the provider.
        $newuser = new stdClass();
        $newuser->email = $this->oauth2client->oauth2user->email;
        $newuser->auth = $this->shortname;
        if (!empty($this->oauth2client->oauth2user->firstname)) {
            $newuser->firstname = $this->oauth2client->oauth2user->firstname;
        }
        if (!empty($this->oauth2client->oauth2user->lastname)) {
            $newuser->lastname = $this->oauth2client->oauth2user->lastname;
        }

        // Retrieve country and city if the provider failed to give it.
        if (!isset($this->oauth2client->oauth2user->country) or !isset($this->oauth2client->oauth2user->city)) {
            require_once($CFG->dirroot . "/iplookup/lib.php");
            $locationdata = iplookup_find_location(getremoteaddr());

            if (!empty($locationdata)) {
                if (empty($this->oauth2client->oauth2user->country)) {
                    if (!empty($locationdata['countrycode'])) {
                        $newuser->country = $locationdata['countrycode'];
                    } else {
                        unset($newuser->country);
                    }
                }

                if (empty($this->oauth2client->oauth2user->city)) {
                    if (!empty($locationdata['city'])) {
                        $newuser->city = $locationdata['city'];
                    } else {
                        unset($newuser->city);
                    }
                }
            }
        }

        $createduser = create_user_record($username, '', $this->shortname);

        // Update user information.
        $newuser->id = $createduser->id;
        $DB->update_record('user', $newuser);

        return $createduser;
    }

    /**
     * Prints a form for configuring this authentication plugin.
     *
     * This function is called from admin/auth.php, and outputs a full page with
     * a form for configuring this plugin.
     * @param object $config
     * @param string $err
     * @param array $user_fields
     */
    public function config_form($config, $err, $user_fields) {
        global $OUTPUT, $CFG, $PAGE;

        // Set to defaults if undefined.
        if (!isset($config->userprefix)) {
            $config->userprefix = 'oauth2_'.$this->shortname.'_';
        }
        if (!isset($config->clientid)) {
            $config->clientid = '';
        }
        if (!isset($config->clientsecret)) {
            $config->clientsecret = '';
        }
        if (!isset($config->createuser)) {
            $config->createuser = '';
        }
        if (!isset($config->nomailrestriction)) {
            $config->nomailrestriction = '';
        }

        // Documentation.
        $renderer = $PAGE->get_renderer('auth_'.$this->shortname);
        echo html_writer::tag('h2', get_string('oauth2setup', 'auth'), array('class' => 'main'));
        echo $renderer->setupdoc();

        // Settings title.
        $settings = html_writer::tag('h2', get_string('settings'), array('class' => 'main'));

        // Client ID.
        $clientidlabel = html_writer::tag('label',
            get_string('auth_clientid_key', 'auth_' . $this->shortname), array('class' => 'clientidlabel'));
        $clientidinput = html_writer::empty_tag('input', array('type' => 'text', 'id' => 'clientid', 'name' => 'clientid',
            'class' => 'clientid', 'value' => $config->clientid));
        $clientid = html_writer::tag('div', html_writer::tag('span', $clientidlabel, array('class' => 'oauth2settingrow1')) .
            html_writer::tag('span', $clientidinput, array('class' => 'oauth2settingrow2')),
                array('class' => 'oauth2setting'));

        // Client secret.
        $clientsecretlabel = html_writer::tag('label',
            get_string('auth_clientsecret_key', 'auth_' . $this->shortname), array('class' => 'clientsecretlabel'));
        $clientsecretinput = html_writer::empty_tag('input', array('type' => 'text', 'id' => 'clientsecret', 'name' => 'clientsecret',
            'class' => 'clientsecret', 'value' => $config->clientsecret));
        $clientsecret = html_writer::tag('div', html_writer::tag('span', $clientsecretlabel, array('class' => 'oauth2settingrow1')) .
            html_writer::tag('span', $clientsecretinput, array('class' => 'oauth2settingrow2')),
                array('class' => 'oauth2setting'));

        // User prefix.
        $authprefix = new stdClass();
        $authprefix->userprefix = $config->userprefix;
        $authprefix->pluginname = $this->name;
        $userprefixlabel = html_writer::tag('label', get_string('auth_userprefix', 'auth'), array('class' => 'userprefixlabel'));
        $userprefixinput = html_writer::empty_tag('input', array('type' => 'text', 'id' => 'userprefix', 'name' => 'userprefix',
            'class' => 'userprefix', 'value' => $config->userprefix));
        $userprefix = html_writer::tag('div', html_writer::tag('span', $userprefixlabel, array('class' => 'oauth2settingrow1')) .
            html_writer::tag('span', $userprefixinput, array('class' => 'oauth2settingrow2')) .
            html_writer::tag('span', get_string('auth_userprefix_help', 'auth', $authprefix),
                array('class' => 'oauth2settingrow3')), array('class' => 'oauth2setting'));

        // Disallow user creation.
        $createuserlabel = html_writer::tag('label', get_string('auth_createuser', 'auth'), array('class' => 'createuserlabel'));
        $checkboxparams = array('type' => 'checkbox', 'id' => 'createuser', 'name' => 'createuser',
                    'class' => 'createuser', 'value' => 1);
        if ($config->createuser) {
            $checkboxparams['checked'] = 'yes';
        }
        $createuserinput = html_writer::empty_tag('input', $checkboxparams);
        $createuser = html_writer::tag('div', html_writer::tag('span', $createuserlabel, array('class' => 'oauth2settingrow1')) .
            html_writer::tag('span', $createuserinput, array('class' => 'oauth2settingrow2')) .
            html_writer::tag('span', get_string('auth_createuser_help', 'auth'), array('class' => 'oauth2settingrow3')), array('class' => 'oauth2setting'));

        // By pass deny email address global option.
        $nomailrestrictionlabel = html_writer::tag('label', get_string('auth_nomailrestriction', 'auth'), array('class' => 'nomailrestrictionlabel'));
        $checkboxparams = array('type' => 'checkbox', 'id' => 'nomailrestriction', 'name' => 'nomailrestriction',
            'class' => 'nomailrestriction', 'value' => 1);
        if ($config->nomailrestriction) {
            $checkboxparams['checked'] = 'yes';
        }
        $nomailrestrictioninput = html_writer::empty_tag('input', $checkboxparams);
        $nomailrestriction = html_writer::tag('div', html_writer::tag('span', $nomailrestrictionlabel, array('class' => 'oauth2settingrow1')) .
            html_writer::tag('span', $nomailrestrictioninput, array('class' => 'oauth2settingrow2')) .
            html_writer::tag('span', get_string('auth_nomailrestriction_help', 'auth'), array('class' => 'oauth2settingrow3')), array('class' => 'oauth2setting'));

        echo $settings;
        echo $clientid;
        echo $clientsecret;
        echo $userprefix;
        echo $createuser;
        echo $nomailrestriction;

        // Display the fields clock.
        // Note: this table code is the same as all other auth plugins.
        echo '<table cellspacing="0" cellpadding="5" border="0">';
        // Block field options.
        // Hidden email options - email must be set to: locked.
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'value' => 'locked',
            'name' => 'lockconfig_field_lock_email'));
        // Display other field options.
        foreach ($user_fields as $key => $user_field) {
            if ($user_field == 'email') {
                unset($user_fields[$key]);
            }
        }
        print_auth_lock_options($this->shortname, $user_fields, get_string('auth_fieldlocks_help', 'auth'), false, false);
        echo '</table>';
    }

    /**
     * Processes and stores configuration data for this authentication plugin.
     *
     * @param object $config
     * @return boolean
     */
    public function process_config($config) {
        global $CFG;

        // Set to defaults if undefined.
        if (!isset($config->clientid)) {
            $config->clientid = '';
        }
        if (!isset($config->clientsecret)) {
            $config->clientsecret = '';
        }
        if (!isset($config->userprefix)) {
            $config->userprefix = 'oauth2_'.$this->shortname.'_';
        }
        if (!isset($config->createuser)) {
            $config->createuser = 0;
        }
        if (!isset($config->nomailrestriction)) {
            $config->nomailrestriction = 0;
        }

        // Save settings.
        set_config('clientid', $config->clientid, 'auth/' . $this->shortname);
        set_config('clientsecret', $config->clientsecret, 'auth/' . $this->shortname);
        set_config('userprefix', $config->userprefix, 'auth/' . $this->shortname);
        set_config('createuser', $config->createuser, 'auth/' . $this->shortname);
        set_config('nomailrestriction', $config->nomailrestriction, 'auth/' . $this->shortname);

        return true;
    }

    /**
     * Called when the user record is updated.
     *
     * We check there is no hack-attempt by a user to change his/her email address
     *
     * @param mixed $olduser     Userobject before modifications    (without system magic quotes)
     * @param mixed $newuser     Userobject new modified userobject (without system magic quotes)
     * @return boolean result
     *
     */
    public function user_update($olduser, $newuser) {
        if ($olduser->email != $newuser->email) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Link oauth2 account to the user account
     *
     * @param int $provideruserid
     * @param int $userid
     */
    private function link_account($provideruserid, $userid = null) {
        global $USER, $DB;

        if (empty($userid)) {
            $userid = $USER->id;
        }

        // Check if the oauth2 account is linked to a different user
        $linkedaccount = $DB->get_record('user_idps', array('component' => 'auth_' . $this->shortname, 'provideruserid' => $provideruserid));
        if ($linkedaccount) {
            // Check if the account is used by a different user
            if ($linkedaccount->userid != $userid) {
                // Two use cases, same behavior:
                // 1. The previous linked user is deleted or unconfirmed => no problem
                // 2. The previous linked user was only linked to this provider
                //      => user can recover by email adress or ask the admin
                $linkedaccount->userid = $userid;
                $DB->update_record('user_idps', $linkedaccount);
            }
        } else {

            $linkedaccount = $DB->get_record('user_idps', array('userid' => $userid, 'component' => 'auth_' . $this->shortname));
            if ($linkedaccount) {
                // Little check, maybe the oauth2 client code is dealing with a wrong id field.
                if ($linkedaccount->provideruserid != $provideruserid) {
                    throw new coding_exception('It seems that the provider (' . $this->shortname . ') change a user id???');
                }
            } else {
                $linkedaccount = new stdClass();
                $linkedaccount->provideruserid = $provideruserid;
                $linkedaccount->component = 'auth_' . $this->shortname;
                $linkedaccount->userid = $userid;
                if (!empty($this->oauth2client->oauth2user->email)) {
                    $linkedaccount->email = $this->oauth2client->oauth2user->email;
                }
                $DB->insert_record('user_idps', $linkedaccount);
            }
        }

    }

    /**
     * Get linked account
     *
     * @param string|int $provideruserid the user id used by the provider
     * @return object the linked account
     */
    private function get_linked_account($provideruserid) {
        global $DB;
        return $DB->get_record('user_idps',
                array('provideruserid' => $provideruserid, 'component' => 'auth_'.$this->shortname));;
    }

    /**
     * Returns a list of potential IdPs that this authentication plugin supports.
     * This is used to provide links on the login page.
     *
     * @param string $wantsurl the relative url fragment the user wants to get to.
     *                         You can use this to compose a returnurl.
     *
     * @return array like:
     *              array(
     *                  array(
     *                      'url' => 'http://someurl',
     *                      'icon' => new pix_icon(...),
     *                      'name' => get_string('somename', 'auth_yourplugin'),
     *                 ),
     *             )
     */
    public function loginpage_idp_list($wantsurl) {
        $idps = array();
            $idps[] = array(
                'url'  => $this->oauth2client->get_login_url(),
                'name' => $this->name,
                'image' => $this->logourl,
            );

        return $idps;
    }

}

