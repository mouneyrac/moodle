<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'jerome.moodle.local';
$CFG->dbname    = 'Moodle_Hub_server';
$CFG->dbuser    = 'postgres';
$CFG->dbpass    = 'root';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
    'dbsocket' => '',
    );

    $CFG->wwwroot   = 'http://jerome.moodle.local/~jerome/Moodle_Hub_server';
    $CFG->dataroot  = '/Users/jerome/Documents/moodledata/Sites_Moodle_Hub_server_postgres';
    $CFG->admin     = 'admin';

    $CFG->directorypermissions = 0777;

    $CFG->passwordsaltmain = '}piX<J[0IAu/Si8aB+Pz2[%ce%fVEJL';

    require_once(dirname(__FILE__) . '/lib/setup.php');

    // There is no php closing tag in this file,
    // it is intentional because it prevents trailing whitespace problems!

/**
     * log a variable
     */
    function varlog($stringData = "", $filename="log.txt", $mode='a+') {
        $myFile = "/Applications/MAMP/logs/".$filename;
        //$myFile = "/var/www/vhosts/hub.moodle.org/moodledata/".$filename;
        $fh = fopen($myFile, $mode) or die("can't open file");
        $date = date('l jS \of F Y h:i:s A');
        fwrite($fh, $date.": ".print_r($stringData,true)."\n");
        fclose($fh);
        chmod($myFile, 0777);
    }
