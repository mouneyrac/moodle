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
            
            //separating each words of the query (so we can look for the fullname)
            $query = required_param('q', PARAM_RAW);   
            $queries = array();
            $explodednamequeries = explode(',', $query);
            
            //clean the result from white spaces
            $trimmedexplodednamequeries = array();
            foreach($explodednamequeries as $searchterm) {
                $searchterm = trim($searchterm);
                if (!empty($searchterm)) {
                    $trimmedexplodednamequeries[] = $searchterm;
                }
            }
            $explodednamequeriescount = count($trimmedexplodednamequeries);
            
            //do not support multiple comma in the search
            if ($explodednamequeriescount > 2) { // e.g. ' ggg gg, ss ww, gg' - more than 1 comma
               
                echo json_encode(array('total' => 0, 'result' => array()));
                die();
            } else if (($explodednamequeriescount == 2)) { //1 comma in the search term
               
                $queries = $trimmedexplodednamequeries;   
            } else {  //'ddd' - 1 search term
                    //'dfdd aaa' - 2 search term o 1 search term
                    //'eee rr ppp' - 1 search term with many whit space. For performance purpose we do not support two search terms with multiple white spaces (except if they are separated by comma)
             
                $explodedqueries = explode(' ', $trimmedexplodednamequeries[0]);
                
                
                //remove empty white space
                foreach ($explodedqueries as $searchterm) {
                    $searchterm = trim($searchterm);
                    if (!empty($searchterm)) {
                        $queries[] = $searchterm;
                    }
                }
                $queriescount = count($queries);
                
                
                if ($queriescount != 2) {
                    $queries = array(trim($query)); // 'ddd' or 'eee rr ppp' (otherwise $queries contains 'dfdd aaa')
                }
            }
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
            // 3- user who have at least one term in firstname and one term in lastname got a +1 bonus

            
            //check if it's an email
            if (($queriescount == 1) and ($queries[0] == clean_param($query, PARAM_EMAIL))) { //if more than a word then the user is not searching for an email
                $totalresult = $DB->count_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                    AND ' . $DB->sql_like('email','?',false,false), array($queries[0].'%'));          
                $results = $DB->get_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                    AND ' . $DB->sql_like('email','?',false,false), array($queries[0].'%'), '', '*', 0, $usermax);
            } else {        
                
                
                //first look for the username
                if ($queriescount == 1) { //if more than a word then the user is not searching for a username
                    $totalresult = $DB->count_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                        AND id != ? AND ' . $DB->sql_like('username','?',false,false), 
                            array(guest_user()->id,$queries[0].'%'));  

                    if ($totalresult > 0) {
                        $results = $DB->get_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                            AND id != ? AND ' . $DB->sql_like('username','?',false,false), 
                                array(guest_user()->id,$queries[0].'%'), '', '*', 0, $usermax);
                    }
                }
                
                //if max number of result not reach, look for lastname
                if ($totalresult < $usermax) {
                      
                    $lastnameresult = $DB->count_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                    AND id != ? AND ' . $DB->sql_like('lastname','?',false,false), 
                        array(guest_user()->id,$queries[0].'%'));  

                    if ($lastnameresult > 0) {
                        $lastnameresults = $DB->get_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                        AND id != ? AND ' . $DB->sql_like('lastname','?',false,false), 
                            array(guest_user()->id,$queries[0].'%'), '', '*', 0, $usermax);
                        
                        //manual merge to avoid duplicate
                        foreach($lastnameresults as $newuserid => $newuser) {
                            if (!isset($results[$newuserid])) {
                                $results[$newuserid] = $newuser;
                            }
                        }  
                        
                        $totalresult = count($results);
                    }
                    
                    
                }
                
                //if max number of result not reach, look for firstname
                if (($totalresult < $usermax)) {
                    $firstnameresult = $DB->count_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                    AND id != ? AND ' . $DB->sql_like('firstname','?',false,false), 
                        array(guest_user()->id,$queries[0].'%'));  

                    if ($firstnameresult > 0) {
                        $firstnameresults = $DB->get_records_select('user', 'mnethostid = 1 AND deleted = 0 AND suspended = 0 AND confirmed = 1
                        AND id != ? AND ' . $DB->sql_like('firstname','?',false,false), 
                            array(guest_user()->id,$queries[0].'%'), '', '*', 0, $usermax);
                        
                        //manual merge to avoid duplicate
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
