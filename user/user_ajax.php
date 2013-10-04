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

/*
 * Handling all ajax request for user API
 */
define('AJAX_SCRIPT', true);

require_once('../config.php');

function find_and_add_user_by_name($users, $usermax, $name, $nametype = 'firstname') {
    global $DB;

    // search lastname matching the first word.
    $nameresult = $DB->count_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                        AND id != ? AND ' . $DB->sql_like($nametype,'?',false,false),
        array(guest_user()->id, $name . '%'));


    if ($nameresult > 0) {
        $nameresults = $DB->get_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                            AND id != ? AND ' . $DB->sql_like($nametype,'?',false,false),
            array(guest_user()->id, $name . '%'), '', '*', 0, $usermax);

        //manual merge to avoid duplicate
        foreach($nameresults as $newuserid => $newuser) {
            if (!isset($users[$newuserid])) {
                $users[$newuserid] = $newuser;
            }
        }
    }

    return $users;
}

$contextid = optional_param('contextid', SYSCONTEXTID, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$usermax = optional_param('usermax', 0, PARAM_INT);

list($context, $course, $cm) = get_context_info_array($contextid);

$PAGE->set_url('/user/user_ajax.php');

require_login();
$PAGE->set_context($context);
if (!empty($cm)) {
    $PAGE->set_cm($cm, $course);
} else if (!empty($course)) {
    $PAGE->set_course($course);
}

if (!confirm_sesskey()) {
    $error = array('error' => get_string('invalidsesskey', 'error'));
    die(json_encode($error));
}

echo $OUTPUT->header(); // send headers
// process ajax request
switch ($action) {
    case 'get':
        if (is_siteadmin()) {   //TODO: rewrite to support other roles - 
                                // mainly you need to check Moodle front end code logic, 
                                // and check all the required capabilities.
                                // I would suggest to create an externallib.php function if possible.

            $query = required_param('q', PARAM_RAW);
            $query = trim($query);
            $queries = explode(' ', $query);

//            $queries = array();
//            $explodednamequeries = explode(',', $query);
            
//            //clean the result from white spaces
//            $trimmedexplodednamequeries = array();
//            foreach($explodednamequeries as $searchterm) {
//                $searchterm = trim($searchterm);
//                if (!empty($searchterm)) {
//                    $trimmedexplodednamequeries[] = $searchterm;
//                }
//            }
//            $explodednamequeriescount = count($trimmedexplodednamequeries);
//
//            //do not support multiple comma in the search
//            if ($explodednamequeriescount > 2) { // e.g. ' ggg gg, ss ww, gg' - more than 1 comma
//
//                echo json_encode(array('total' => 0, 'result' => array()));
//                die();
//            } else if (($explodednamequeriescount == 2)) { //1 comma in the search term
//
//                $queries = $trimmedexplodednamequeries;
//            } else {
//                        // 'xxx aaa bbb ccc ...' will we look for match in this order:
//                        //      2 search terms: 'xxx' (fistname) and 'aaa ...' (lastname)
//                        //      2 search terms: 'xxx aaa' (fistname) and 'bbb ...' (lastname)
//                        //      2 search terms: 'xxx aaa bbb' (fistname) and 'ccc ...' (lastname)
//                        //      etc...
//                        //      2 search terms: 'xxx' (lastname) and 'aaa ...' (fistname)
//                        //      2 search terms: 'xxx aaa' (lastname) and 'bbb ...' (fistname)
//                        //      2 search terms: 'xxx aaa bbb' (lastname) and 'ccc ...' (fistname)
//                        //      etc...
//                        //      Search for lastname 'xxx aaa bbb ccc ...'
//                        //      Search for firstname 'xxx aaa bbb ccc ...'
//                        // For performance purpose we do not support
//                        // two search terms with multiple white spaces (except if they are separated by comma).
//
//                $explodedqueries = explode(' ', $trimmedexplodednamequeries[0]);
//
//
//                //remove empty white space
//                foreach ($explodedqueries as $searchterm) {
//                    $searchterm = trim($searchterm);
//                    if (!empty($searchterm)) {
//                        $queries[] = $searchterm;
//                    }
//                }
//                $queriescount = count($queries);
//
//
//                if ($queriescount != 2) {
//                    $queries = array(trim($query)); // 'ddd' or 'eee rr ppp' (otherwise $queries contains 'dfdd aaa')
//                }
//            }
            $queriescount = count($queries);
            
            $totalresult = 0;
            $results = array();
            
            //Here we are looking for very fast query. It's performance over quality result.
            //The goal here is to make a user search instantaneous.
            // For hight performance we separate searches on the following indexes:
            // username, email, firstname, lastname.
            // We give up on left '%' in the SQL LIKE otherwise it would be a full scan of the DB
            // We give up on UNION - much slower
            // We give up on looking to two index for a same SELECT - much slower
            
            //algorithm for selecting user by fullname
            // 1- we remove occurence from the search terms
            // 2- we keep only user matching all search terms
            // 3- user who have at least one term in firstname and one term in lastname are prioritary
            //    over the one matching firstname only or last name only.
            //    It means than "Jean (firstanme) Francois (lastname)" will be returned before "Jean Francois (firstname)".

            //check if it's an email
            if (($queriescount == 1) and ($queries[0] == clean_param($query, PARAM_EMAIL))) { //if more than a word then the user is not searching for an email
                $totalresult = $DB->count_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                    AND ' . $DB->sql_like('email','?',false,false), array($queries[0].'%'));          
                $results = $DB->get_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                    AND ' . $DB->sql_like('email','?',false,false), array($queries[0].'%'), '', '*', 0, $usermax);
            } else {        

                // There is only one word to search.
                if ($queriescount == 1) {


                    // Look for user id

                    // Look for idnumber

                    // Look for specific field

                    // Look for the username
                    $totalresult = $DB->count_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                        AND id != ? AND ' . $DB->sql_like('username','?',false,false),
                            array(guest_user()->id,$queries[0].'%'));

                    if ($totalresult > 0) {
                        $results = $DB->get_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                            AND id != ? AND ' . $DB->sql_like('username','?',false,false),
                                array(guest_user()->id,$queries[0].'%'), '', '*', 0, $usermax);
                    }


                    //if max number of result not reach, look for lastname
                    if ($totalresult < $usermax) {

                        $results = find_and_add_user_by_name($results, $usermax, $queries[0], 'lastname');

                        $totalresult = count($results);

                    }

                    //if max number of result not reach, look for firstname
                    if (($totalresult < $usermax)) {

                        $results = find_and_add_user_by_name($results, $usermax, $queries[0], 'firstname');

                        $totalresult = count($results);
                    }
                } else {
                    // Two or more search terms.

                    // Algorithm.
                    // 'xxx aaa bbb ccc ...' will we look for match in this order:
                    // a)     2 search terms: 'xxx' (first name) and 'aaa ...' (last name)
                    // b)     2 search terms: 'xxx aaa' (first name) and 'bbb ...' (last name)
                    // c)     2 search terms: 'xxx aaa bbb' (first name) and 'ccc ...' (last name)
                    // etc...
                    // d)     2 search terms: 'xxx' (last name) and 'aaa ...' (first name)
                    // e)     2 search terms: 'xxx aaa' (last name) and 'bbb ...' (first name)
                    // f)     2 search terms: 'xxx aaa bbb' (last name) and 'ccc ...' (first name)
                    // etc...
                    // g)     Search for last name 'xxx aaa bbb ccc ...'
                    // h)     Search for first name 'xxx aaa bbb ccc ...'
                    // For performance purpose we do not support two search terms with multiple white spaces.

                    // First we look for all first name / last name combination
                    for ($separatorpos = 1; $separatorpos < $queriescount; $separatorpos += 1) {
                        if ($totalresult < $usermax) {
                            // Retrieve first name and last name
                            $firstname = '';
                            $lastname = '';
                            foreach ($queries as $key => $word) {
                                if ($key < $separatorpos) {
                                    $firstname = $firstname . ' ' . $word;
                                } else {
                                    $lastname = $lastname . ' ' . $word;
                                }
                            }
                            error_log(print_r($firstname, true));
                            error_log(print_r($lastname, true));

                            // Search first name.
                            $firstnameresults = find_and_add_user_by_name($results, $usermax, trim($firstname), 'firstname');

                            // Search last name.
                            $lastnameresults = find_and_add_user_by_name($results, $usermax, trim($lastname), 'lastname');

                            // Get common results.
                            $flresults = array_intersect_key($firstnameresults, $lastnameresults);

                            // Merge the result to the main result variable.
                            //manual merge to avoid duplicate
                            foreach($flresults as $newuserid => $newuser) {
                                if (!isset($results[$newuserid])) {
                                    $results[$newuserid] = $newuser;
                                }
                            }

                            $totalresult = count($results);
                        }
                    }

                    // Then we look for all last name / first name combination
                    for ($separatorpos = 1; $separatorpos < $queriescount; $separatorpos += 1) {
                        if ($totalresult < $usermax) {
                            // Retrieve first name and last name
                            $firstname = '';
                            $lastname = '';
                            foreach ($queries as $key => $word) {
                                if ($key < $separatorpos) {
                                    $lastname = $lastname . ' ' . $word;
                                } else {
                                    $firstname = $firstname . ' ' . $word;
                                }
                            }

                            // Search first name.
                            $firstnameresults = find_and_add_user_by_name($results, $usermax, trim($firstname), 'firstname');

                            // Search last name.
                            $lastnameresults = find_and_add_user_by_name($results, $usermax, trim($lastname), 'lastname');

                            // Get common results.
                            $flresults = array_intersect_key($firstnameresults, $lastnameresults);

                            // Merge the result to the main result variable.
                            //manual merge to avoid duplicate
                            foreach($flresults as $newuserid => $newuser) {
                                if (!isset($results[$newuserid])) {
                                    $results[$newuserid] = $newuser;
                                }
                            }

                            $totalresult = count($results);
                        }
                    }

                    // Then we look to lastname.
                    if ($totalresult < $usermax) {

                        // Search last name.
                        $lastnameresults = find_and_add_user_by_name($results, $usermax, $query, 'lastname');

                        // Merge the result to the main result variable.
                        // manual merge to avoid duplicate.
                        foreach($lastnameresults as $newuserid => $newuser) {
                            if (!isset($results[$newuserid])) {
                                $results[$newuserid] = $newuser;
                            }
                        }

                        $totalresult = count($results);
                    }

                    // Finally we look to firstname.
                    if ($totalresult < $usermax) {

                        // Search first name.
                        $firstnameresults = find_and_add_user_by_name($results, $usermax, $query, 'firstname');

                        // Merge the result to the main result variable.
                        // manual merge to avoid duplicate.
                        foreach($firstnameresults as $newuserid => $newuser) {
                            if (!isset($results[$newuserid])) {
                                $results[$newuserid] = $newuser;
                            }
                        }

                        $totalresult = count($results);
                    }

                }

            }
//            
            
//            $results = $DB->get_records('user', array('id' => 2));
            
//              $timeend = time();
            
//          varlog($timeend - $timestart);
            
            $users = array();
            foreach($results as $user) {
                $profileimageurl = moodle_url::make_pluginfile_url(
                get_context_instance(CONTEXT_USER, $user->id)->id, 'user', 'icon', NULL, '/', 'f2');
                $user->profileimage = $profileimageurl->out(false);
                $users[] = $user;
            }
            
            echo json_encode(array('total' => $totalresult, 'result' => $users));

            die();
        }
        break;
        
    default:
        break;
}

if (!isloggedin()) {
    // tell user to log in to view comments
    echo json_encode(array('error' => 'require_login'));
}
// ignore request
die;
