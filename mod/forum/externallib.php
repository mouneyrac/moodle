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
 * External forum api.
 *
 * This api is to add/create/update/delete/... forums, discussions and posts.
 *
 * @package    mod_forum
 * @category   external
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Forum external functions
 *
 * @package    mod_forum
 * @category   external
 * @copyright  2012 Jerome Mouneyrac
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since Moodle 2.3
 */
class mod_forum_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 2.3
     */
    public static function create_forums_parameters() {
        return new external_function_parameters(
                array(
                    'forums' => new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'type' => new external_value(PARAM_ALPHA, ''),
                                        'courseid' => new external_value(PARAM_INT, ''),
                                        'coursesectionid' => new external_value(PARAM_INT, 'course section id'),
                                        'name' => new external_value(PARAM_TEXT, ''),
                                        'intro' => new external_value(PARAM_RAW, '', VALUE_OPTIONAL),
                                        'assessed' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'assesstimestart' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'assesstimefinish' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'scale' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'maxbytes' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'maxattachements' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'forcesubscribe' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'trackingtype' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'rsstype' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'rssarticles' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'timemodified' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'warnafter' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'blockafter' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'blockperiod' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'completiondiscussions' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'completionreplies' => new external_value(PARAM_INT, '', VALUE_OPTIONAL),
                                        'completionposts' => new external_value(PARAM_INT, '', VALUE_OPTIONAL)
                                    )
                            )
                    )
                )
        );
    }

    /**
     * Create forums
     *
     * Function throw an exception at the first error encountered.
     * @param array $forums  An array of forum
     * @since Moodle 2.3
     */
    public static function create_forums($forums) {
        global $DB, $CFG;

        require_once("$CFG->dirroot/mod/forum/locallib.php");

        $params = self::validate_parameters(self::create_forums_parameters(),
                array('forums' => $forums));

        $transaction = $DB->start_delegated_transaction(); //rollback all enrolment if an error occurs
                                                           //(except if the DB doesn't support it)

        foreach ($params['forums'] as $forum) {
            
            //check the course exist
            $course = $DB->get_record('course', array('id'=> $forum['courseid']), '*', MUST_EXIST);
            
            //check forum module exist
            $module = $DB->get_record('modules', array('name'=>'forum'), '*', MUST_EXIST);
            
            
            // Ensure the current user is allowed to run this function in the course context
            $context = get_context_instance(CONTEXT_COURSE, $course->id); 
            self::validate_context($context);

            //check that the user has the permissions to create a forum
            require_capability('moodle/course:manageactivities', $context);
            require_capability('mod/forum:addinstance', $context); 
            
            //check if forum module is enable
            if (!course_allowed_module($course, 'forum')) {
                throw new moodle_exception('forummoduledisable', 'forum'); //TODO have the string translated
            }

            //create the forum
            $forum['instance'] = '';
            $forum['coursemodule'] = '';
            $forum['course'] = $course->id;
            $forum['modulename'] = 'forum'; 

            require_once($CFG->libdir . '/modinfolib.php');
            $newforum = create_module((object)$forum);

            $createdforum['name'] = $newforum->name;
            $createdforum['courseid'] = $newforum->courseid;
            $createdforum['coursesectionid'] = $newforum->coursesectionid;


            //add the id to the result
            $createdforums[] = $createdforum;
        }

        $transaction->allow_commit();
    }

    /**
     * Returns description of method result value
     *
     * @return null
     * @since Moodle 2.3
     */
    public static function create_forums_returns() {
        return new external_multiple_structure(
                            new external_single_structure(
                                    array(
                                        'id' => new external_value(PARAM_INT, 'id of the create forum'),
                                        'courseid' => new external_value(PARAM_INT, 'course id where the the forum is created'),
                                        'coursesectionid' => new external_value(PARAM_INT, 'course section id where the the forum is created'),
                                        'name' => new external_value(PARAM_TEXT, 'name of the created forum')
                                    )
                            )
                );
    }

}
