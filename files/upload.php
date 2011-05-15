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
 *
 * @package    moodlecore
 * @subpackage files
 * @copyright  2011 Dongsheng Cai <dongsheng@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(__FILE__)) . '/config.php');
$token = required_param('token', PARAM_ALPHANUM);

// Obtain token record
if (!$token = $DB->get_record('external_tokens', array('token'=>$token))) {
    throw new webservice_access_exception(get_string('invalidtoken', 'webservice'));
}

// Validate token date
if ($token->validuntil and $token->validuntil < time()) {
    $DB->delete_records('external_tokens', array('token'=>$this->token, 'tokentype'=>$tokentype));
    throw new webservice_access_exception(get_string('invalidtimedtoken', 'webservice'));
}

//assumes that if sid is set then there must be a valid associated session no matter the token type
if ($token->sid) {
    $session = session_get_instance();
    if (!$session->session_exists($token->sid)) {
        $DB->delete_records('external_tokens', array('sid'=>$token->sid));
        throw new webservice_access_exception(get_string('invalidtokensession', 'webservice'));
    }
}

// Check ip
if ($token->iprestriction and !address_in_subnet(getremoteaddr(), $token->iprestriction)) {
    add_to_log(SITEID, 'webservice', get_string('tokenauthlog', 'webservice'), '' , get_string('failedtolog', 'webservice').": ".getremoteaddr() , 0);
    throw new webservice_access_exception(get_string('invalidiptoken', 'webservice'));
}

//$this->restricted_context = get_context_instance_by_id($token->contextid);
//$this->restricted_serviceid = $token->externalserviceid;

$user = $DB->get_record('user', array('id'=>$token->userid, 'deleted'=>0), '*', MUST_EXIST);

// log token access
$DB->set_field('external_tokens', 'lastaccess', time(), array('id'=>$token->id));

session_set_user($user);
$context = get_context_instance(CONTEXT_USER, $USER->id);

$fs = get_file_storage();
$file_record = new stdClass;
$file_record->component = 'user';
$file_record->contextid = $context->id;
$file_record->userid    = $USER->id;
$file_record->filearea  = 'private';
$file_record->filename = clean_param($_FILES['thefile']['name'], PARAM_FILE);
$file_record->filepath  = '/';
$file_record->itemid    = 0;
$file_record->license   = $CFG->sitedefaultlicense;
$file_record->author    = '';
$file_record->source    = '';
$stored_file = $fs->create_file_from_pathname($file_record, $_FILES['thefile']['tmp_name']);
