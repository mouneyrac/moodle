<?php

require(__DIR__ . '/../config.php');
$userid = optional_param('userid', $USER->id, PARAM_INT);

require_login();
$PAGE->set_context(context_user::instance($userid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title('Preferences');
$PAGE->set_url(new moodle_url('/user/preferences.php', array('userid' => $userid)));

if ($USER->id == $userid) {
    $nav = $PAGE->settingsnav->get('usercurrentsettings');
} else {
    $user = $DB->get_record('user', array('id' => $userid));
    $PAGE->navigation->extend_for_user($user);
    $nav = $PAGE->settingsnav->get('userviewingsettings'.$user->id);
}

echo $OUTPUT->header();
echo html_writer::start_tag('div', array('class' => 'row-fluid'));
$i = 1;
foreach ($nav->children as $node) {

    echo html_writer::start_tag('div', array('class' => 'span4'));
    echo html_writer::start_tag('div');
    echo html_writer::tag('h3', $node->get_content());
    echo html_writer::end_tag('div');
    echo html_writer::start_tag('div');
    echo html_writer::start_tag('ul');
    foreach ($node->children as $child) {
        echo html_writer::tag('li', html_writer::link($child->action, $child->get_content()));
    }
    echo html_writer::end_tag('ul');
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    if ($i++ % 3 == 0) {
        echo html_writer::end_tag('div');
        echo html_writer::start_tag('div', array('class' => 'row-fluid'));
    }
}


echo html_writer::end_tag('div');

echo $OUTPUT->footer();
