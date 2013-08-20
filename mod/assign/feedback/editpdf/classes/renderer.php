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
     * @param $id string - Set the id for this button.
     * @return string
     */
    private function render_toolbar_button($icon, $colour, $disabled=false, $id='') {
        $iconalt = get_string($colour, 'assignfeedback_editpdf');
        $iconhtml = $this->pix_icon($icon, $iconalt, 'assignfeedback_editpdf');
        $iconparams = array('data-colour'=>$colour);
        if ($disabled) {
            $iconparams['disabled'] = 'true';
        }
        if ($id) {
            $iconparams['id'] = $id;
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
        $launcheditorlink = html_writer::link('#',
                                              get_string('launcheditor', 'assignfeedback_editpdf'),
                                              array('class'=>'donkey', 'id'=>$linkid, 'role'=>'button'));
        $html .= html_writer::tag('style', file_get_contents($CFG->dirroot . '/mod/assign/feedback/editpdf/styles.css'));
        $html .= html_writer::div($launcheditorlink, 'visibleifjs');

        $header = get_string('pluginname', 'assignfeedback_editpdf');
        $body = '';

        // Create the page navigation.
        $navigation = '';

        $navigation .= html_writer::tag('button', get_string('previous'), array('disabled'=>'true', 'class'=>'navigate-previous-button'));
        $pageoptions = html_writer::tag('option', get_string('gotopage', 'assignfeedback_editpdf'), array('value'=>''));
        $navigation .= html_writer::tag('select', $pageoptions, array('disabled'=>'true', 'class'=>'navigate-page-select'));
        $navigation .= html_writer::tag('button', get_string('next'), array('disabled'=>'true', 'class'=>'navigate-next-button'));

        $navigation = html_writer::div($navigation, 'navigation', array('role'=>'navigation'));

        $toolbar = '';
        // Foreground colour chooser.
        $toolbar .= $this->render_toolbar_button('yellow', 'colour', true);

        $colourlist = html_writer::start_tag('ul');
        $colourlist .= html_writer::tag('li', $this->render_toolbar_button('red', 'red'));
        $colourlist .= html_writer::tag('li', $this->render_toolbar_button('blue', 'blue'));
        $colourlist .= html_writer::tag('li', $this->render_toolbar_button('green', 'green'));
        $colourlist .= html_writer::tag('li', $this->render_toolbar_button('yellow', 'yellow'));
        $colourlist .= html_writer::tag('li', $this->render_toolbar_button('white', 'white'));

        $colourlist .= html_writer::end_tag('ul');

        $toolbar .= $colourlist;

        $toolbar .= $this->render_toolbar_button('comment', 'tool', true);

        $toollist = html_writer::start_tag('ul');
        $toollist .= html_writer::tag('li', $this->render_toolbar_button('comment', 'comment'));
        $toollist .= html_writer::tag('li', $this->render_toolbar_button('line', 'line'));
        $toollist .= html_writer::tag('li', $this->render_toolbar_button('rectangle', 'rectangle'));
        $toollist .= html_writer::tag('li', $this->render_toolbar_button('oval', 'oval'));
        $toollist .= html_writer::tag('li', $this->render_toolbar_button('pen', 'pen'));
        $toollist .= html_writer::tag('li', $this->render_toolbar_button('stamp', 'stamp'));
        $toollist .= html_writer::tag('li', $this->render_toolbar_button('eraser', 'eraser'));
        $toollist .= html_writer::end_tag('ul');

        $toolbar .= $toollist;

        $toolbar = html_writer::div($toolbar, 'toolbar', array('role'=>'toolbar'));
        $body = $navigation . $toolbar . '<hr/>';

        $loading = $this->pix_icon('i/loading', get_string('loadingeditor', 'assignfeedback_editpdf'), 'moodle', array('class'=>'loading'));
        $canvas = html_writer::div($loading, 'drawingcanvas');
        $body .= html_writer::div($canvas, 'drawingregion');

        $body .= '<hr/>';

        $attributes = array('disabled'=>'true', 'class'=>'savebutton');
        $footer = html_writer::tag('button', get_string('savechanges'), $attributes);
        $attributes = array('disabled'=>'true', 'class'=>'cancelbutton');
        $footer .= html_writer::tag('button', get_string('cancel'), $attributes);
        $editorparams = array(array('header'=>$header,
                                    'body'=>$body,
                                    'footer'=>$footer,
                                    'linkid'=>$linkid,
                                    'assignmentid'=>$widget->assignment,
                                    'userid'=>$widget->userid,
                                    'attemptnumber'=>$widget->attemptnumber));

        $this->page->requires->yui_module('moodle-assignfeedback_editpdf-editor',
                                          'M.assignfeedback_editpdf.editor.init',
                                          $editorparams);

        $this->page->requires->strings_for_js(array('loadingeditor', 'pagexofy'), 'assignfeedback_editpdf');

        return $html;
    }
}
