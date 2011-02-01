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

require_once('../config.php');


/**
 * Small file cache system
 */
class moodle_cache_filesystem  {

    /**
     * Create cache file
     * @param string $key
     * @param <any type> $data
     */
    function store($key, $data) {
        //create empty cache file
        $cachefile = fopen($this->getfilename($key), 'w+');
        if (!$cachefile) {
            throw new moodle_exception('Could not write to cache');
        }

        //add cache data (lock the file in writer lock mode)
        flock($cachefile, LOCK_EX);
        $data = serialize($data);
        if (fwrite($cachefile, $data) === false) {
            throw new moodle_exception('Could not write to cache');
        }
        flock($cachefile, LOCK_UN);
        fclose($cachefile);
    }

    /**
     * Get cache file content
     * @param string $key
     * @return <any type>
     */
    function fetch($key) {
        global $CFG;

        //TODO: implement it in Administration
        $CFG->guestsessioncachettl = 600;
        
        //retrieve the cache file
        $filename = $this->getfilename($key);

        //check if the cache exist
        if (!file_exists($filename)) {
            return false;
        }

        //TODO: check if the cache is not expired
        if (time() > (filemtime($filename) + $CFG->guestsessioncachettl)) {
            unlink($filename);
            return false;
        }

        //retrieve the cache content
        $cachefile = fopen($filename, 'r');
        //any error during opening cache (example: no permission to read)
        if (!$cachefile) {
            return false;
        }
        // Getting the content (reader lock mode)
        flock($cachefile, LOCK_SH);
        $data = file_get_contents($filename);
        flock($cachefile, LOCK_UN);
        fclose($cachefile);
        $data = @unserialize($data);
        
        return $data[1];
    }

    /**
     * Delete cache file
     * @param string $key
     * @return bool - true for success
     */
    function delete($key) {
        $filename = $this->getfilename($key);
        if (file_exists($filename)) {
            return unlink($filename);
        }
        return false;       
    }

    /**
     * Get cache file name
     * @param string $key
     * @return string file path + file name (moodledata/cache/cache_key)
     */
    private function getfilename($key) {
        global $CFG;

        return $CFG->dataroot . '/cache/cache_' . md5($key);
    }

}

