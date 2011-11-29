<?php
require_once("HTML/QuickForm/text.php");
require_once("text.php");

/**
 * HTML class for a text type element
 *
 * @author       Jerome Mouneyrac
 * @access       public
 */
class MoodleQuickForm_autocompletetext extends MoodleQuickForm_text{
    
    var $setelementid;
    var $source;
    
    /**
     *
     * @param type $elementName
     * @param type $elementLabel
     * @param type $attributes
     * @param type $options
     */
    function MoodleQuickForm_autocompletetext($elementName=null, $elementLabel=null, $attributes=null, $options = array()) {
        $this->setelementid = $options['setelementid'];
        $this->source = $options['source'];
        parent::HTML_QuickForm_text($elementName, $elementLabel, $attributes);
    }

    function toHtml(){
        global $PAGE;
        
        //Pass id of the element, so that unmask checkbox can be attached.
        $PAGE->requires->yui_module('moodle-form-autocompletetext', 'M.form.autocompletetext',
                array(array('formid' => $this->getAttribute('id'), 
                    'setelementid' => $this->setelementid,
                    'source' => $this->source,
                    'elementname' => $this->getName())));

        $parenthtml = parent::toHtml();
        
        //add the selected user display
        $parenthtml .= '<div id="displayedresult_'.$this->getName().'" class="autocompletedisplayedresult">
            ' . get_string('autocompleteuserinit', 'search') . '</div>'; 
        
        //add a remove selected user button
        
        
        return $parenthtml;
        
    }
}
