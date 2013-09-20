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
 * This file contains the definition for the library class for edit PDF renderer.
 *
 * @package   assignfeedback_editpdf
 * @copyright 2012 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * A custom renderer class that extends the plugin_renderer_base and is used by the editpdf feedback plugin.
 *
 * @package assignfeedback_editpdf
 * @copyright 2013 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assignfeedback_editpdf_renderer extends plugin_renderer_base {

    /**
     * Render a single colour button.
     *
     * @param $icon string - The key for the icon
     * @param $colour string - The key for the lang string.
     * @param $disabled bool - The is this button disabled.
     * @return string
     */
    private function render_toolbar_button($icon, $tool, $disabled=false) {
        $iconalt = get_string($tool, 'assignfeedback_editpdf');
        $iconhtml = $this->pix_icon($icon, $iconalt, 'assignfeedback_editpdf');
        $iconparams = array('data-tool'=>$tool, 'class'=>$tool . 'button');
        if ($disabled) {
            $iconparams['disabled'] = 'true';
        }

        return html_writer::tag('button', $iconhtml, $iconparams);
    }

    /**
     * Render the editpdf widget in the grading form.
     *
     * @param assignfeedback_editpdf_widget - Renderable widget containing assignment, user and attempt number.
     * @return string
     */
    public function render_assignfeedback_editpdf_widget(assignfeedback_editpdf_widget $widget) {
        global $CFG;

        $html = '';

        $html .= html_writer::div(get_string('jsrequired', 'assignfeedback_editpdf'), 'hiddenifjs');
        $linkid = html_writer::random_id();
        $launcheditorlink = html_writer::tag('button',
                                              get_string('launcheditor', 'assignfeedback_editpdf'),
                                              array('id'=>$linkid, 'class'=>'btn'));
        $html .= html_writer::tag('style', file_get_contents($CFG->dirroot . '/mod/assign/feedback/editpdf/styles.css'));
        $links = $launcheditorlink;

        $linkclass = '';
        if (!$widget->downloadurl) {
            $linkclass .= ' hidden';
        }

        $downloadlinkid = html_writer::random_id();
        $pdficon = $this->pix_icon('f/pdf', '');
        $url = '#';
        $filename = '';
        if ($widget->downloadurl) {
            $url = $widget->downloadurl;
            $filename = $widget->downloadfilename;
        }

        $filename = html_writer::span($filename);
        $downloadfeedbacklink = html_writer::link($url,
                                                $pdficon . ' ' . $filename,
                                                array('class'=>$linkclass, 'id'=>$downloadlinkid, 'role'=>'button'));
        $links .= html_writer::start_tag('div', array('class'=>'assignfeedback_editpdf_downloadlink'));
        $links .= $downloadfeedbacklink;
        $deletelinkid = html_writer::random_id();
        $deleteicon = $this->pix_icon('t/delete', get_string('deletefeedback', 'assignfeedback_editpdf'));
        $deletefeedbacklink = html_writer::link('#',
                                                $deleteicon,
                                                array('class'=>$linkclass, 'id'=>$deletelinkid, 'role'=>'button'));
        $links .= ' ' . $deletefeedbacklink;
        $links .= html_writer::end_tag('div');

        $html .= html_writer::div($links, 'visibleifjs');
        $header = get_string('pluginname', 'assignfeedback_editpdf');
        $body = '';
        $spacer = '<span class="verticaldivider"></span>';

        // Create the page navigation.
        $navigation = '';

        $navigation .= html_writer::tag('button', '⤎', array('disabled'=>'true', 'class'=>'navigate-previous-button'));
        $pageoptions = html_writer::tag('option', get_string('gotopage', 'assignfeedback_editpdf'), array('value'=>''));
        $navigation .= html_writer::tag('select', $pageoptions, array('disabled'=>'true', 'class'=>'navigate-page-select'));
        $navigation .= html_writer::tag('button', '⤏', array('disabled'=>'true', 'class'=>'navigate-next-button'));

        $navigation = html_writer::div($navigation, 'navigation', array('role'=>'navigation'));

        $toolbar1 = '';
        $toolbar2 = '';
        $toolbar3 = '';
        $toolbar4 = '';
        $toolbar5 = '';

        // Comments.
        $toolbar1 .= $this->render_toolbar_button('comment', 'comment');
        $toolbar1 .= $this->render_toolbar_button('search', 'searchcomments');
        $toolbar1 .= $this->render_toolbar_button('commentcolour', 'commentcolour');
        $toolbar1 = html_writer::div($toolbar1, 'toolbar', array('role'=>'toolbar'));

        // Select Tool.
        $toolbar2 .= $this->render_toolbar_button('select', 'select');
        $toolbar2 = html_writer::div($toolbar2, 'toolbar', array('role'=>'toolbar'));

        // Other Tools.
        $toolbar3 = $this->render_toolbar_button('pen', 'pen');
        $toolbar3 .= $this->render_toolbar_button('line', 'line');
        $toolbar3 .= $this->render_toolbar_button('rectangle', 'rectangle');
        $toolbar3 .= $this->render_toolbar_button('oval', 'oval');
        $toolbar3 .= $this->render_toolbar_button('highlight', 'highlight');
        $toolbar3 .= $this->render_toolbar_button('annotationcolour', 'annotationcolour');
        $toolbar3 = html_writer::div($toolbar3, 'toolbar', array('role'=>'toolbar'));

        // Stamps.
        $toolbar4 .= $this->render_toolbar_button('stamp', 'stamp');
        $toolbar4 .= $this->render_toolbar_button('currentstamp', 'currentstamp');
        $toolbar4 = html_writer::div($toolbar4, 'toolbar', array('role'=>'toolbar'));

        // Generate PDF.
        $attributes = array('disabled'=>'true', 'class'=>'savebutton');
        $toolbar5 .= html_writer::tag('button', '⤾', $attributes);

        $toolbar5 = html_writer::div($toolbar5, 'toolbar', array('role'=>'toolbar'));

        $pageheader = html_writer::div($navigation . $spacer .
                                       $toolbar1 . $spacer .
                                       $toolbar2 . $spacer .
                                       $toolbar3 . $spacer .
                                       $toolbar4 . $spacer .
                                       $toolbar5,
                                        'pageheader');
        $body = $pageheader;

        $loading = $this->pix_icon('i/loading', get_string('loadingeditor', 'assignfeedback_editpdf'), 'moodle', array('class'=>'loading'));
        $canvas = html_writer::div($loading, 'drawingcanvas');
        $body .= html_writer::div($canvas, 'drawingregion');

        $body .= '<hr/>';

        $footer = '';

        // Retrieve the stamp image file urls.
        $stampfilenames = get_config('assignfeedback_editpdf', 'stamps_jsonfilenames');
        $stampfilenames = json_decode($stampfilenames);
        $fileurls = array();
        foreach ($stampfilenames as $stampfilename) {
            $stampfileurl = moodle_url::make_pluginfile_url(context_system::instance()->id,
                'assignfeedback_editpdf', 'stamps', 0, '/', $stampfilename)->out(false);
            // Strip the wwwroot.
            $fileurls[] = str_replace($CFG->wwwroot,"",$stampfileurl);;
        }

        $editorparams = array(array('header'=>$header,
                                    'body'=>$body,
                                    'footer'=>$footer,
                                    'linkid'=>$linkid,
                                    'deletelinkid'=>$deletelinkid,
                                    'downloadlinkid'=>$downloadlinkid,
                                    'assignmentid'=>$widget->assignment,
                                    'userid'=>$widget->userid,
                                    'attemptnumber'=>$widget->attemptnumber,
                                    'stampfileurls'=>$fileurls,
                                    'menuicon'=>$this->pix_url('t/contextmenu')->out(true)));

        $this->page->requires->yui_module('moodle-assignfeedback_editpdf-editor',
                                          'M.assignfeedback_editpdf.editor.init',
                                          $editorparams);

        $this->page->requires->strings_for_js(array(
            'yellow',
            'white',
            'red',
            'blue',
            'green',
            'black',
            'clear',
            'colourpicker',
            'loadingeditor',
            'pagexofy',
            'deletecomment',
            'addtoquicklist',
            'filter',
            'searchcomments'
        ), 'assignfeedback_editpdf');

        return $html;
    }
}
