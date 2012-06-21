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
 * External enrol functions unit tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Enrol external PHPunit tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */
class core_enrol_external_testcase extends advanced_testcase {

    /**
     * Tests set up
     */
    protected function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/externallib.php');
    }

    /**
     * Test get_enrolled_users
     */
    public function test_get_enrolled_users() {
        global $USER, $CFG;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();
        $user1 = array(
            'username' => 'usernametest1',
            'idnumber' => 'idnumbertest1',
            'firstname' => 'First Name User Test 1',
            'lastname' => 'Last Name User Test 1',
            'email' => 'usertest1@email.com',
            'address' => '2 Test Street Perth 6000 WA',
            'phone1' => '01010101010',
            'phone2' => '02020203',
            'icq' => 'testuser1',
            'skype' => 'testuser1',
            'yahoo' => 'testuser1',
            'aim' => 'testuser1',
            'msn' => 'testuser1',
            'department' => 'Department of user 1',
            'institution' => 'Institution of user 1',
            'description' => 'This is a description for user 1',
            'descriptionformat' => FORMAT_MOODLE,
            'city' => 'Perth',
            'url' => 'http://moodle.org',
            'country' => 'au'
            );
        $user1 = self::getDataGenerator()->create_user($user1);
        if (!empty($CFG->usetags)) {
            require_once($CFG->dirroot . '/user/editlib.php');
            require_once($CFG->dirroot . '/tag/lib.php');
            $user1->interests = array('Cinema', 'Tennis', 'Dance', 'Guitar', 'Cooking');
            useredit_update_interests($user1, $user1->interests);
        }
        $user2 = self::getDataGenerator()->create_user();

        // Set the required capabilities by the external function.
        $context = context_course::instance($course->id);
        $roleid = $this->assignUserCapability('moodle/course:viewparticipants', $context->id);
        $this->assignUserCapability('moodle/user:viewdetails', $context->id, $roleid);

        // Enrol the users in the course.
        // We use the manual plugin.
        $enrol = enrol_get_plugin('manual');
        $enrolinstances = enrol_get_instances($course->id, true);
        foreach ($enrolinstances as $courseenrolinstance) {
            if ($courseenrolinstance->enrol == "manual") {
                $instance = $courseenrolinstance;
                break;
            }
        }
        $enrol->enrol_user($instance, $user1->id, $roleid);
        $enrol->enrol_user($instance, $user2->id, $roleid);
        $enrol->enrol_user($instance, $USER->id, $roleid);

        // Call the external function.
        $enrolledusers = core_enrol_external::get_enrolled_users($course->id);

        // Check we retrieve the good total number of enrolled users.
        $this->assertEquals(3, count($enrolledusers));

        // Do the same call as admin to receive all possible fields.
        $this->setAdminUser();
        $USER->email = "admin@fakeemail.com";

        // Call the external function.
        $enrolledusers = core_enrol_external::get_enrolled_users($course->id);

        foreach($enrolledusers as $enrolleduser) {
            if ($enrolleduser['username'] == $user1->username) {
                $this->assertEquals($user1->idnumber, $enrolleduser['idnumber']);
                $this->assertEquals($user1->firstname, $enrolleduser['firstname']);
                $this->assertEquals($user1->lastname, $enrolleduser['lastname']);
                $this->assertEquals($user1->email, $enrolleduser['email']);
                $this->assertEquals($user1->address, $enrolleduser['address']);
                $this->assertEquals($user1->phone1, $enrolleduser['phone1']);
                $this->assertEquals($user1->phone2, $enrolleduser['phone2']);
                $this->assertEquals($user1->icq, $enrolleduser['icq']);
                $this->assertEquals($user1->skype, $enrolleduser['skype']);
                $this->assertEquals($user1->yahoo, $enrolleduser['yahoo']);
                $this->assertEquals($user1->aim, $enrolleduser['aim']);
                $this->assertEquals($user1->msn, $enrolleduser['msn']);
                $this->assertEquals($user1->department, $enrolleduser['department']);
                $this->assertEquals($user1->institution, $enrolleduser['institution']);
                $this->assertEquals($user1->description, $enrolleduser['description']);
                $this->assertEquals(FORMAT_HTML, $enrolleduser['descriptionformat']);
                $this->assertEquals($user1->city, $enrolleduser['city']);
                $this->assertEquals($user1->country, $enrolleduser['country']);
                $this->assertEquals($user1->url, $enrolleduser['url']);
                if (!empty($CFG->usetags)) {
                    $this->assertEquals(implode(', ', $user1->interests), $enrolleduser['interests']);
                }
            }
        }

        $this->setGuestUser();

        // Call without required capability.
        $this->unassignUserCapability('moodle/course:viewparticipants', $context->id, $roleid);
        $this->setExpectedException('moodle_exception');
        $categories = core_enrol_external::get_enrolled_users($course->id);
    }

    /**
     * Test get_users_courses
     */
    public function test_get_users_courses() {
        global $USER;

        $this->resetAfterTest(true);

        $course1 = self::getDataGenerator()->create_course();
        $course2 = self::getDataGenerator()->create_course();
        $courses = array($course1, $course2);

        // Enrol $USER in the courses.
        // We use the manual plugin.
        $enrol = enrol_get_plugin('manual');
        $roleid = null;
        foreach ($courses as $course) {
            $context = context_course::instance($course->id);
            $roleid = $this->assignUserCapability('moodle/course:viewparticipants',
                    $context->id, $roleid);

            $enrolinstances = enrol_get_instances($course->id, true);
            foreach ($enrolinstances as $courseenrolinstance) {
                if ($courseenrolinstance->enrol == "manual") {
                    $instance = $courseenrolinstance;
                    break;
                }
            }
            $enrol->enrol_user($instance, $USER->id, $roleid);
        }

        // Call the external function.
        $enrolledincourses = core_enrol_external::get_users_courses($USER->id);

        // Check we retrieve the good total number of enrolled users.
        $this->assertEquals(2, count($enrolledincourses));
    }
}

/**
 * Role external PHPunit tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */
class core_role_external_testcase extends advanced_testcase {

    /**
     * Tests set up
     */
    protected function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/enrol/externallib.php');
    }

    /**
     * Test assign_roles
     */
    public function test_assign_roles() {
        global $USER;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();

        // Set the required capabilities by the external function.
        $context = context_course::instance($course->id);
        $roleid = $this->assignUserCapability('moodle/role:assign', $context->id);
        $this->assignUserCapability('moodle/course:view', $context->id, $roleid);

        // Add manager role to $USER.
        // So $USER is allowed to assign 'manager', 'editingteacher', 'teacher' and 'student'.
        role_assign(1, $USER->id, context_system::instance()->id);

        // Check the teacher role has not been assigned to $USER.
        $users = get_role_users(3, $context);
        $this->assertEquals(count($users), 0);

        // Call the external function. Assign teacher role to $USER.
        core_role_external::assign_roles(array(
            array('roleid' => 3, 'userid' => $USER->id, 'contextid' => $context->id)));

        // Check the role has been assigned.
        $users = get_role_users(3, $context);
        $this->assertEquals(count($users), 1);

        // Call without required capability.
        $this->unassignUserCapability('moodle/role:assign', $context->id, $roleid);
        $this->setExpectedException('moodle_exception');
        $categories = core_role_external::assign_roles(
            array('roleid' => 3, 'userid' => $USER->id, 'contextid' => $context->id));
    }

    /**
     * Test unassign_roles
     */
    public function test_unassign_roles() {
        global $USER;

        $this->resetAfterTest(true);

        $course = self::getDataGenerator()->create_course();

        // Set the required capabilities by the external function.
        $context = context_course::instance($course->id);
        $roleid = $this->assignUserCapability('moodle/role:assign', $context->id);
        $this->assignUserCapability('moodle/course:view', $context->id, $roleid);

        // Add manager role to $USER.
        // So $USER is allowed to assign 'manager', 'editingteacher', 'teacher' and 'student'.
        role_assign(1, $USER->id, context_system::instance()->id);

        // Add teacher role to $USER on course context.
        role_assign(3, $USER->id, $context->id);

        // Check the teacher role has been assigned to $USER on course context.
        $users = get_role_users(3, $context);
        $this->assertEquals(count($users), 1);

        // Call the external function. Assign teacher role to $USER.
        core_role_external::unassign_roles(array(
            array('roleid' => 3, 'userid' => $USER->id, 'contextid' => $context->id)));

        // Check the role has been unassigned on course context.
        $users = get_role_users(3, $context);
        $this->assertEquals(count($users), 0);

        // Call without required capability.
        $this->unassignUserCapability('moodle/role:assign', $context->id, $roleid);
        $this->setExpectedException('moodle_exception');
        $categories = core_role_external::unassign_roles(
            array('roleid' => 3, 'userid' => $USER->id, 'contextid' => $context->id));
    }
}
