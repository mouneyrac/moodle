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
 * File.
 *
 * @package    core
 * @copyright  2013 Frédéric Massart
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../config.php');
require($CFG->libdir . '/formslib.php');

class accountsettings_form extends moodleform {

    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $mform->setDisableShortforms(true);
        $mform->addElement('header', 'i18nhdr', 'Internationalisation');

        $choices = get_list_of_timezones();
        $choices['99'] = get_string('serverlocaltime');
        if ($CFG->forcetimezone != 99) {
            $mform->addElement('static', 'forcedtimezone', get_string('timezone'), $choices[$CFG->forcetimezone]);
        } else {
            $mform->addElement('select', 'timezone', get_string('timezone'), $choices);
            $mform->setDefault('timezone', '99');
        }

        $mform->addElement('select', 'lang', get_string('preferredlanguage'), get_string_manager()->get_list_of_translations());
        $mform->setDefault('lang', $CFG->lang);

        // Multi-Calendar Support - see MDL-18375.
        $calendartypes = \core_calendar\type_factory::get_list_of_calendar_types();
        // We do not want to show this option unless there is more than one calendar type to display.
        if (count($calendartypes) > 1) {
            $mform->addElement('select', 'calendartype', get_string('preferredcalendar', 'calendar'), $calendartypes);
        }

        $mform->addElement('header', 'appearancehdr', 'Appearance');

        if (true || !empty($CFG->allowuserthemes)) {
            $choices = array();
            $choices[''] = get_string('default');
            $themes = get_list_of_themes();
            foreach ($themes as $key=>$theme) {
                if (empty($theme->hidefromselector)) {
                    $choices[$key] = get_string('pluginname', 'theme_'.$theme->name);
                }
            }
            $mform->addElement('select', 'theme', get_string('preferredtheme'), $choices);
        }

        $mform->addElement('header', 'editorhdr', 'Editing');

        $editors = editors_get_enabled();
        if (count($editors) > 1) {
            $choices = array('' => get_string('defaulteditor'));
            $firsteditor = '';
            foreach (array_keys($editors) as $editor) {
                if (!$firsteditor) {
                    $firsteditor = $editor;
                }
                $choices[$editor] = get_string('pluginname', 'editor_' . $editor);
            }
            $mform->addElement('select', 'preference_htmleditor', get_string('textediting'), $choices);
            $mform->setDefault('preference_htmleditor', '');
        } else {
            // Empty string means use the first chosen text editor.
            $mform->addElement('hidden', 'preference_htmleditor');
            $mform->setDefault('preference_htmleditor', '');
            $mform->setType('preference_htmleditor', PARAM_PLUGIN);
        }

        $mform->addElement('header', 'emailhdr', 'Email');

        $choices = array();
        $choices['0'] = get_string('emaildisplayno');
        $choices['1'] = get_string('emaildisplayyes');
        $choices['2'] = get_string('emaildisplaycourse');
        $mform->addElement('select', 'maildisplay', get_string('emaildisplay'), $choices);
        $mform->setDefault('maildisplay', 2);

        $this->add_action_buttons();

    }

}

$userid = optional_param('userid', $USER->id, PARAM_INT);
require_login();
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Account settings');
$PAGE->set_url('/user/accountsettings.php?userid=' . $userid);

if ($USER->id == $userid) {
    $nav = $PAGE->settingsnav->get('usercurrentsettings');
} else {
    $user = $DB->get_record('user', array('id' => $userid));
    $PAGE->navigation->extend_for_user($user);
    $nav = $PAGE->settingsnav->get('userviewingsettings'.$user->id);
}

echo $OUTPUT->header();
$mform = new accountsettings_form($PAGE->url->out(false));
echo $mform->display();
echo $OUTPUT->footer();
