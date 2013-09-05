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
 * Strings for component 'assignfeedback_corepdf', language 'en'
 *
 * @package   assignfeedback_corepdf
 * @copyright 2012 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Annotate PDF';
$string['enabled'] = 'Annotate PDF';
$string['editpdf'] = 'Annotate PDF';
$string['downloadfeedback'] = 'Download feedback PDF';
$string['deletefeedback'] = 'Delete feedback PDF';
$string['launcheditor'] = 'Launch PDF editor...';
$string['gotopage'] = 'Go to page';
$string['red'] = 'Red';
$string['blue'] = 'Blue';
$string['green'] = 'Green';
$string['yellow'] = 'Yellow';
$string['white'] = 'White';
$string['black'] = 'Black';
$string['pen'] = 'Pen';
$string['tool'] = 'Tool';
$string['line'] = 'Line';
$string['rectangle'] = 'Rectangle';
$string['oval'] = 'Oval';
$string['comment'] = 'Comments';
$string['stamp'] = 'Stamp';
$string['select'] = 'Select';
$string['loadingeditor'] = 'Loading PDF editor';
$string['colour'] = 'Color';
$string['jsrequired'] = 'Annotating PDF documents requires javascript. Please enable javascript in your browser if you want to use this feature.';
$string['editpdf_help'] = 'Annotate students submissions directly in the browser and produce an edited downloadable PDF.';
$string['enabled_help'] = 'If enabled, the teacher will be able to create annotated pdf files when marking the assignments. This allows the teacher to add comments, drawing and stamps directly on top of the students work. The annotating is done in the browser and no extra software is required.';
$string['testgs'] = 'Test ghostscript path';
$string['test_ok'] = 'The ghostscript path appears to be OK - please check you can see the message in the image below';
$string['test_empty'] = 'The ghostscript path is empty - please enter the correct path';
$string['test_doesnotexist'] = 'The ghostscript path points to a non-existent file';
$string['test_isdir'] = 'The ghostscript path points to a folder, please include the ghostscript program in the path you specify';
$string['test_notexecutable'] = 'The ghostscript points to a file that is not executable';
$string['test_notestfile'] = 'The test PDF is missing';
$string['gspath'] = 'Ghostscript path';
$string['pagexofy'] = 'Page {$a->page} of {$a->total}';
$string['couldnotsavepage'] = 'Could not save page {$a}';
$string['gspath_help'] = 'On most Linux installs, this can be left as \'/usr/bin/gs\'. On Windows it will be something like \'c:\\gs\\bin\\gswin32c.exe\' (make sure there are no spaces in the path - if necessary copy the files \'gswin32c.exe\' and \'gsdll32.dll\' to a new folder without a space in the path)';
$string['downloadablefilename'] = 'feedback.pdf';
$string['colourpicker'] = 'Colour Picker';
