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
 * External course functions unit tests
 *
 * @package    core
 * @category   phpunit
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

class courseexternallib_testcase extends advanced_testcase {

    /**
     * Tests set up
     */
    protected function setUp() {
        global $CFG;
        require_once($CFG->dirroot . '/course/externallib.php');
    }

    /**
     * Test create_categories
     */
    public function test_create_categories() {

        global $DB, $USER;

        // Set the required capabilities by the external function
        $contextid = context_system::instance()->id;
        $roleid = $this->assignUserCapability('moodle/category:manage', $contextid);

        $this->resetAfterTest(true);

        // Create base categories.
        $category1 = new stdClass();
        $category1->name = 'Root Test Category 1';
        $category2 = new stdClass();
        $category2->name = 'Root Test Category 2';
        $category2->idnumber = 'rootcattest2';
        $category2->desc = 'Description for root test category 1';
        $category2->theme = 'base';
        $categories = array(
            array('name' => $category1->name, 'parent' => 0),
            array('name' => $category2->name, 'parent' => 0, 'idnumber' => $category2->idnumber,
                'description' => $category2->desc, 'theme' => $category2->theme)
        );

        $createdcats = core_course_external::create_categories($categories);

        // Initially confirm that base data was inserted correctly.
        $this->assertEquals($category1->name, $createdcats[0]['name']);
        $this->assertEquals($category2->name, $createdcats[1]['name']);

        // Save the ids.
        $category1->id = $createdcats[0]['id'];
        $category2->id = $createdcats[1]['id'];

        // Create on sub category.
        $category3 = new stdClass();
        $category3->name = 'Sub Root Test Category 3';
        $subcategories = array(
            array('name' => $category3->name, 'parent' => $category1->id)
        );

        $createdsubcats = core_course_external::create_categories($subcategories);

        // Confirm that sub categories were inserted correctly.
        $this->assertEquals($category3->name, $createdsubcats[0]['name']);

        // Save the ids.
        $category3->id = $createdsubcats[0]['id'];

        // Calling the ws function should provide a new sortorder to give category1,
        // category2, category3. New course categories are ordered by id not name.
        $category1 = $DB->get_record('course_categories', array('id' => $category1->id));
        $category2 = $DB->get_record('course_categories', array('id' => $category2->id));
        $category3 = $DB->get_record('course_categories', array('id' => $category3->id));

        $this->assertGreaterThanOrEqual($category1->sortorder, $category3->sortorder);
        $this->assertGreaterThanOrEqual($category2->sortorder, $category3->sortorder);
        
        // Call without required capability
        $this->unassignUserCapability('moodle/category:manage', $contextid, $roleid);
        $this->setExpectedException('required_capability_exception');
        $createdsubcats = core_course_external::create_categories($subcategories);
        
    }

}
