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
 * External course API
 *
 * @package    core
 * @subpackage course
 * @copyright  2010 Moodle Pty Ltd (http://moodle.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class moodle_course_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_course_contents_parameters() {
        return new external_function_parameters(
                array('courseid' => new external_value(PARAM_INT, 'course id'),
                      'options' => new external_multiple_structure (
                              new external_single_structure(
                                    array('key' => new external_value(PARAM_ALPHANUM, 'option name'),
                                          'value' => new external_value(PARAM_RAW, 'the value of the option, this param is personaly validated in the external function.')
                              )
                      ), 'options', VALUE_DEFAULT, array())
                )
        );
    }

    /**
     * Get course contents
     * @param array $options
     * @return array
     */
    public static function get_course_contents($courseid, $options) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        //validate parameter
        $params = self::validate_parameters(self::get_course_contents_parameters(),
                        array('courseid' => $courseid, 'options' => $options));

        //retrieve the course
        $course = $DB->get_record('course', array('id' => $params['courseid']), '*', MUST_EXIST);

        //check course format exist
        if (!file_exists($CFG->dirroot . '/' . '/course/format/' . $course->format . '/lib.php')) {
            throw new moodle_exception('courseformatnotfound', 'error', '', $course->format);
        }

        //return error on unknown key
        foreach ($params['options'] as $option) {
            switch ($option['key']) {
                default:
                    throw new moodle_exception('unknownoptionkey', 'webservice', $option['key']);
                    break;
            }
        }

        //create return value
        $coursecontents = array();

        // now security checks
        $context = get_context_instance(CONTEXT_COURSE, $course->id);
        try {
            self::validate_context($context);
        } catch (Exception $e) {
            $exceptionparam = new stdClass();
            $exceptionparam->message = $e->getMessage();
            $exceptionparam->courseid = $course->id;
            throw new moodle_exception(
                    get_string('errorcoursecontextnotvalid', 'webservice', $exceptionparam));
        }
        require_capability('moodle/course:view', $context);

        $canupdatecourse = has_capability('moodle/course:update', $context);

        if ($canupdatecourse or $course->visible
                or has_capability('moodle/course:viewhiddencourses', $context)) {

            //retrieve sections
            $modinfo = get_fast_modinfo($course);
            $sections = get_all_sections($course->id);

            //for each sections (first displayed to last displayed)
            foreach ($sections as $key => $section) {
                // reset $sectioncontents
                $sectionvalues = array();
                $sectionvalues['id'] = $section->id;
                $sectionvalues['name'] = $section->name;
                $sectionvalues['summary'] = file_rewrite_pluginfile_urls($section->summary, 'pluginfile.php', $context->id, 'course', 'section', $section->id);
                $sectionvalues['summaryformat'] = $section->summaryformat;
                $sectioncontents = array();

                if (!array_key_exists($section->section, $modinfo->sections)) {
                    continue;
                }

                //for each module of the section
                foreach ($modinfo->sections[$section->section] as $cmid) { //matching /course/lib.php:print_section() logic
                    $cm = $modinfo->cms[$cmid];

                    //return this module if:
                    //a) the module is visible for the user
                    //b) user can see hidden module
                    $canviewhidden = has_capability('moodle/course:viewhiddenactivities',
                                        get_context_instance(CONTEXT_MODULE, $cm->id));
                    if (!$cm->uservisible && empty($canviewhidden)) {
                        continue;
                    }

                    $module = array();

                    //common info (for people being able to see the module or availability dates)
                    $module['id'] = $cm->id;
                    $module['name'] = $cm->name;
                    $module['modname'] = $cm->modname;
                    $module['modplural'] = $cm->modplural;
                    $module['modicon'] = $cm->get_icon_url()->out(false);
                    $module['indent'] = $cm->indent;
                    //TODO: description will be added in MDL-27001
                    //$module['description'] = $cm->description;
                    $module['description'] = '';
                    //url of the module
                    $url = $cm->get_url();
                    if ($url) { //labels don't have url
                        $module['url'] = $cm->get_url()->out();
                    }

                    //user that can view hidden module should know about the visibility
                    if ($canupdatecourse or $canviewhidden) {
                        $module['visible'] = $cm->visible;
                    }

                    //availability date (also send to user who can see hidden module when the showavailabilyt is ON)
                    if ($canupdatecourse or ($CFG->enableavailability && $canviewhidden && $cm->showavailability)) {
                        $module['availablefrom'] = $cm->availablefrom;
                        $module['availableuntil'] = $cm->availableuntil;
                    }

                    //call $modulename_export_contents
                    //(each module callback take care about checking the capabilities)
                    require_once($CFG->dirroot . '/mod/' . $cm->modname . '/lib.php');
                    $getcontentfunction = $cm->modname.'_export_contents';
                    if (function_exists($getcontentfunction)) {
                        $module['contents'] = $getcontentfunction($cm);
                    }

                    //assign result to $sectioncontents
                    $sectioncontents[] = $module;

                }
                $sectionvalues['modules'] = $sectioncontents;

                // assign result to $coursecontents
                $coursecontents[] = $sectionvalues;
            }
        }
        return $coursecontents;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_course_contents_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Section ID'),
                    'name' => new external_value(PARAM_TEXT, 'Section NAME'),
                    'summary' => new external_value(PARAM_RAW, 'Section description'),
                    'summaryformat' => new external_value(PARAM_ALPHANUMEXT, 'Section description format'),
                    'modules' => new external_multiple_structure(
                            new external_single_structure(
                                array(
                                    'id' => new external_value(PARAM_INT, 'activity id'),
                                    'availablefrom' => new external_value(PARAM_INT, 'module availability start date', VALUE_OPTIONAL),
                                    'availableuntil' => new external_value(PARAM_INT, 'module availability en date', VALUE_OPTIONAL),
                                    'indent' => new external_value(PARAM_INT, 'number of identation in the site'),
                                    'modicon' => new external_value(PARAM_URL, 'activity icon url'),
                                    'modname' => new external_value(PARAM_TEXT, 'activity module type'),
                                    'modplural' => new external_value(PARAM_TEXT, 'activity module plural name'),
                                    'name' => new external_value(PARAM_TEXT, 'activity module name'),
                                    'description' => new external_value(PARAM_TEXT, 'activity description'),
                                    'visible' => new external_value(PARAM_INT, 'is the module visible', VALUE_OPTIONAL),
                                    'url' => new external_value(PARAM_URL, 'activity url', VALUE_OPTIONAL),
                                    'contents' => new external_multiple_structure(
                                          new external_single_structure(
                                              array(
                                                  'type'=> new external_value(PARAM_TEXT, 'a file or a folder or external link'),
                                                  'filename'=> new external_value(PARAM_FILE, 'filename'),
                                                  'filepath'=> new external_value(PARAM_FILE, 'filepath'),
                                                  'filesize'=> new external_value(PARAM_INT, 'filesize'),
                                                  'fileurl' => new external_value(PARAM_URL, 'downloadable file url'),
                                                   // perhaps an extra fields to store addtional info?
                                                  'data' => new external_value(PARAM_RAW, 'addtional info')
                                              )
                                          ), VALUE_DEFAULT, array()
                                      )
                                )
                            ), 'list of module'
                    )
                )
            )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
                array('options' => new external_single_structure(
                            array('ids' => new external_multiple_structure(
                                        new external_value(PARAM_INT, 'Course id')
                                        , 'List of course id. If empty return all courses
                                            except front page course.',
                                        VALUE_OPTIONAL)
                            ), 'options - operator OR is used', VALUE_DEFAULT, array())
                )
        );
    }

    /**
     * Get courses
     * @param array $options
     * @return array
     */
    public static function get_courses($options) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");

        //validate parameter
        $params = self::validate_parameters(self::get_courses_parameters(),
                        array('options' => $options));

        //retrieve courses
        if (!key_exists('ids', $params['options'])
                or empty($params['options']['ids'])) {
            $courses = $DB->get_records('course');
        } else {
            $courses = $DB->get_records_list('course', 'id', $params['options']['ids']);
        }

        //create return value
        $coursesinfo = array();
        foreach ($courses as $course) {

            // now security checks
            $context = get_context_instance(CONTEXT_COURSE, $course->id);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $course->id;
                throw new moodle_exception(
                        get_string('errorcoursecontextnotvalid', 'webservice', $exceptionparam));
            }
            require_capability('moodle/course:view', $context);

            $courseinfo = array();
            $courseinfo['id'] = $course->id;
            $courseinfo['fullname'] = $course->fullname;
            $courseinfo['shortname'] = $course->shortname;
            $courseinfo['categoryid'] = $course->category;
            $courseinfo['summary'] = $course->summary;
            $courseinfo['summaryformat'] = $course->summaryformat;
            $courseinfo['format'] = $course->format;
            $courseinfo['startdate'] = $course->startdate;
            $courseinfo['numsections'] = $course->numsections;

            //some field should be returned only if the user has update permission
            $courseadmin = has_capability('moodle/course:update', $context);
            if ($courseadmin) {
                $courseinfo['categorysortorder'] = $course->sortorder;
                $courseinfo['idnumber'] = $course->idnumber;
                $courseinfo['showgrades'] = $course->showgrades;
                $courseinfo['showreports'] = $course->showreports;
                $courseinfo['newsitems'] = $course->newsitems;
                $courseinfo['visible'] = $course->visible;
                $courseinfo['maxbytes'] = $course->maxbytes;
                $courseinfo['hiddensections'] = $course->hiddensections;
                $courseinfo['groupmode'] = $course->groupmode;
                $courseinfo['groupmodeforce'] = $course->groupmodeforce;
                $courseinfo['defaultgroupingid'] = $course->defaultgroupingid;
                $courseinfo['lang'] = $course->lang;
                $courseinfo['timecreated'] = $course->timecreated;
                $courseinfo['timemodified'] = $course->timemodified;
                $courseinfo['forcetheme'] = $course->theme;
                $courseinfo['enablecompletion'] = $course->enablecompletion;
                $courseinfo['completionstartonenrol'] = $course->completionstartonenrol;
                $courseinfo['completionnotify'] = $course->completionnotify;
            }

            if ($courseadmin or $course->visible
                    or has_capability('moodle/course:viewhiddencourses', $context)) {
                $coursesinfo[] = $courseinfo;
            }
        }

        return $coursesinfo;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function get_courses_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'course id'),
                            'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                            'categoryid' => new external_value(PARAM_INT, 'category id'),
                            'categorysortorder' => new external_value(PARAM_INT,
                                    'sort order into the category', VALUE_OPTIONAL),
                            'fullname' => new external_value(PARAM_TEXT, 'full name'),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                            'summary' => new external_value(PARAM_RAW, 'summary'),
                            'summaryformat' => new external_value(PARAM_INT,
                                    'the summary text Moodle format'),
                            'format' => new external_value(PARAM_ALPHANUMEXT,
                                    'course format: weeks, topics, social, site,..'),
                            'showgrades' => new external_value(PARAM_INT,
                                    '1 if grades are shown, otherwise 0', VALUE_OPTIONAL),
                            'newsitems' => new external_value(PARAM_INT,
                                    'number of recent items appearing on the course page', VALUE_OPTIONAL),
                            'startdate' => new external_value(PARAM_INT,
                                    'timestamp when the course start'),
                            'numsections' => new external_value(PARAM_INT, 'number of weeks/topics'),
                            'maxbytes' => new external_value(PARAM_INT,
                                    'largest size of file that can be uploaded into the course',
                                    VALUE_OPTIONAL),
                            'showreports' => new external_value(PARAM_INT,
                                    'are activity report shown (yes = 1, no =0)', VALUE_OPTIONAL),
                            'visible' => new external_value(PARAM_INT,
                                    '1: available to student, 0:not available', VALUE_OPTIONAL),
                            'hiddensections' => new external_value(PARAM_INT,
                                    'How the hidden sections in the course are displayed to students',
                                    VALUE_OPTIONAL),
                            'groupmode' => new external_value(PARAM_INT, 'no group, separate, visible',
                                    VALUE_OPTIONAL),
                            'groupmodeforce' => new external_value(PARAM_INT, '1: yes, 0: no',
                                    VALUE_OPTIONAL),
                            'defaultgroupingid' => new external_value(PARAM_INT, 'default grouping id',
                                    VALUE_OPTIONAL),
                            'timecreated' => new external_value(PARAM_INT,
                                    'timestamp when the course have been created', VALUE_OPTIONAL),
                            'timemodified' => new external_value(PARAM_INT,
                                    'timestamp when the course have been modified', VALUE_OPTIONAL),
                            'enablecompletion' => new external_value(PARAM_INT,
                                    'Enabled, control via completion and activity settings. Disbaled,
                                        not shown in activity settings.',
                                    VALUE_OPTIONAL),
                            'completionstartonenrol' => new external_value(PARAM_INT,
                                    '1: begin tracking a student\'s progress in course completion
                                        after course enrolment. 0: does not',
                                    VALUE_OPTIONAL),
                            'completionnotify' => new external_value(PARAM_INT,
                                    '1: yes 0: no', VALUE_OPTIONAL),
                            'lang' => new external_value(PARAM_ALPHANUMEXT,
                                    'forced course language', VALUE_OPTIONAL),
                            'forcetheme' => new external_value(PARAM_ALPHANUMEXT,
                                    'name of the force theme', VALUE_OPTIONAL),
                        ), 'course'
                )
        );
    }

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function create_courses_parameters() {
        $courseconfig = get_config('moodlecourse'); //needed for many default values
        return new external_function_parameters(
            array(
                'courses' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'fullname' => new external_value(PARAM_TEXT, 'full name'),
                            'shortname' => new external_value(PARAM_TEXT, 'course short name'),
                            'categoryid' => new external_value(PARAM_INT, 'category id'),
                            'idnumber' => new external_value(PARAM_RAW, 'id number', VALUE_OPTIONAL),
                            'summary' => new external_value(PARAM_RAW, 'summary', VALUE_OPTIONAL),
                            'summaryformat' => new external_value(PARAM_INT,
                                    'the summary text Moodle format', VALUE_DEFAULT, FORMAT_MOODLE),
                            'format' => new external_value(PARAM_ALPHANUMEXT,
                                    'course format: weeks, topics, social, site,..',
                                    VALUE_DEFAULT, $courseconfig->format),
                            'showgrades' => new external_value(PARAM_INT,
                                    '1 if grades are shown, otherwise 0', VALUE_DEFAULT,
                                    $courseconfig->showgrades),
                            'newsitems' => new external_value(PARAM_INT,
                                    'number of recent items appearing on the course page',
                                    VALUE_DEFAULT, $courseconfig->newsitems),
                            'startdate' => new external_value(PARAM_INT,
                                    'timestamp when the course start', VALUE_OPTIONAL),
                            'numsections' => new external_value(PARAM_INT, 'number of weeks/topics',
                                    VALUE_DEFAULT, $courseconfig->numsections),
                            'maxbytes' => new external_value(PARAM_INT,
                                    'largest size of file that can be uploaded into the course',
                                    VALUE_DEFAULT, $courseconfig->maxbytes),
                            'showreports' => new external_value(PARAM_INT,
                                    'are activity report shown (yes = 1, no =0)', VALUE_DEFAULT,
                                    $courseconfig->showreports),
                            'visible' => new external_value(PARAM_INT,
                                    '1: available to student, 0:not available', VALUE_OPTIONAL),
                            'hiddensections' => new external_value(PARAM_INT,
                                    'How the hidden sections in the course are displayed to students',
                                    VALUE_DEFAULT, $courseconfig->hiddensections),
                            'groupmode' => new external_value(PARAM_INT, 'no group, separate, visible',
                                    VALUE_DEFAULT, $courseconfig->groupmode),
                            'groupmodeforce' => new external_value(PARAM_INT, '1: yes, 0: no',
                                    VALUE_DEFAULT, $courseconfig->groupmodeforce),
                            'defaultgroupingid' => new external_value(PARAM_INT, 'default grouping id',
                                    VALUE_DEFAULT, 0),
                            'enablecompletion' => new external_value(PARAM_INT,
                                    'Enabled, control via completion and activity settings. Disabled,
                                        not shown in activity settings.',
                                    VALUE_OPTIONAL),
                            'completionstartonenrol' => new external_value(PARAM_INT,
                                    '1: begin tracking a student\'s progress in course completion after
                                        course enrolment. 0: does not',
                                    VALUE_OPTIONAL),
                            'completionnotify' => new external_value(PARAM_INT,
                                    '1: yes 0: no', VALUE_OPTIONAL),
                            'lang' => new external_value(PARAM_ALPHANUMEXT,
                                    'forced course language', VALUE_OPTIONAL),
                            'forcetheme' => new external_value(PARAM_ALPHANUMEXT,
                                    'name of the force theme', VALUE_OPTIONAL),
                        )
                    ), 'courses to create'
                )
            )
        );
    }

    /**
     * Create  courses
     * @param array $courses
     * @return array courses (id and shortname only)
     */
    public static function create_courses($courses) {
        global $CFG, $DB;
        require_once($CFG->dirroot . "/course/lib.php");
        require_once($CFG->libdir . '/completionlib.php');


        $params = self::validate_parameters(self::create_courses_parameters(),
                        array('courses' => $courses));

        $availablethemes = get_plugin_list('theme');
        $availablelangs = get_string_manager()->get_list_of_translations();

        $transaction = $DB->start_delegated_transaction();

        foreach ($params['courses'] as $course) {

            // Ensure the current user is allowed to run this function
            $context = get_context_instance(CONTEXT_COURSECAT, $course['categoryid']);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->catid = $course['categoryid'];
                throw new moodle_exception(
                        get_string('errorcatcontextnotvalid', 'webservice', $exceptionparam));
            }
            require_capability('moodle/course:create', $context);

            // Make sure lang is valid
            if (key_exists('lang', $course) and empty($availablelangs[$course['lang']])) {
                throw new moodle_exception(
                        get_string('errorinvalidparam', 'webservice', 'lang'));
            }

            // Make sure theme is valid
            if (key_exists('forcetheme', $course)) {
                if (!empty($CFG->allowcoursethemes)) {
                    if (empty($availablethemes[$course['forcetheme']])) {
                        throw new moodle_exception(
                                get_string('errorinvalidparam', 'webservice', 'forcetheme'));
                    } else {
                        $course['theme'] = $course['forcetheme'];
                    }
                }
            }

            //force visibility if ws user doesn't have the permission to set it
            $category = $DB->get_record('course_categories', array('id' => $course['categoryid']));
            if (!has_capability('moodle/course:visibility', $context)) {
                $course['visible'] = $category->visible;
            }

            //set default value for completion
            $courseconfig = get_config('moodlecourse');
            if (completion_info::is_enabled_for_site()) {
                if (!key_exists('enablecompletion', $course)) {
                    $course['enablecompletion'] = $courseconfig->enablecompletion;
                }
                if (!key_exists('completionstartonenrol', $course)) {
                    $course['completionstartonenrol'] = $courseconfig->completionstartonenrol;
                }
            } else {
                $course['enablecompletion'] = 0;
                $course['completionstartonenrol'] = 0;
            }

            $course['category'] = $course['categoryid'];

            //Note: create_course() core function check shortname, idnumber, category
            $course['id'] = create_course((object) $course)->id;

            $resultcourses[] = array('id' => $course['id'], 'shortname' => $course['shortname']);
        }

        $transaction->allow_commit();

        return $resultcourses;
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function create_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id'       => new external_value(PARAM_INT, 'course id'),
                    'shortname' => new external_value(PARAM_TEXT, 'short name'),
                )
            )
        );
    }

}