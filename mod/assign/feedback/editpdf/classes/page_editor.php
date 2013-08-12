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
 * This file contains the editor class for the assignfeedback_editpdf plugin
 *
 * @package   assignfeedback_editpdf
 * @copyright 2012 Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace assignfeedback_editpdf;

/**
 * This class performs crud operations on comments and annotations from a page of a response.
 * No capability checks are done - they should be done by the calling class.
 */
class page_editor {

    /**
     * Get all comments for a page.
     * @param int gradeid
     * @param int pageno
     * @return array(comment)
     */
    public static function get_comments($gradeid, $pageno) {
        global $DB;

        $comments = array();
        $records = $DB->get_records('assignfeedback_editpdf_cmnt', array('gradeid'=>$gradeid, 'pageno'=>$pageno));
        foreach ($records as $record) {
            array_push($comments, self::comment_from_record($record));
        }

        return $comments;
    }

    /**
     * Convert a compatible stdClass into an instance of a comment.
     * @param int gradeid
     * @param int pageno
     * @return array(comment)
     */
    public static function comment_from_record(stdClass $record) {
        $comment = new comment();
        foreach ($comment as $key => $value) {
            $comment->$key = $record->key;
        }
        return $comment;
    }

    /**
     * Get a single comment by id.
     * @param int commentid
     * @return comment or false
     */
    public static function get_comment($commentid) {
        $record = $DB->get_record('assignfeedback_editpdf_cmnt', array('id'=>$commentid), '*', IGNORE_MISSING);
        if ($record) {
            return self::comment_from_record($record);
        }
        return false;
    }

    /**
     * Add a comment to a page.
     * @param int gradeid
     * @param int pageno
     * @param comment
     * @return bool
     */
    public static function add_comment(comment $comment) {
        $comment->id = null;
        return $DB->insert_record('assignfeedback_editpdf_cmnt', $comment);
    }

    /**
     * Remove a comment from a page.
     * @param int commentid
     * @return bool
     */
    public static function remove_comment($commentid) {
        return $DB->delete_record('assignfeedback_editpdf_cmnt', array('id'=>$commentid));
    }

    /**
     * Get all annotations for a page.
     * @param int gradeid
     * @param int pageno
     * @return array(annotations)
     */
    public static function get_annotations($gradeid, $pageno) {
        global $DB;

        $annotations = array();
        $records = $DB->get_records('assignfeedback_editpdf_annot', array('gradeid'=>$gradeid, 'pageno'=>$pageno));
        foreach ($records as $record) {
            array_push($annotations, self::annotation_from_record($record));
        }

        return $annotations;
    }

    /**
     * Convert a compatible stdClass into an instance of a annotation.
     * @param int gradeid
     * @param int pageno
     * @return annotation
     */
    public static function annotation_from_record(stdClass $record) {
        $annotation = new annotation();
        foreach ($annotation as $key => $value) {
            $annotation->$key = $record->key;
        }
        return $annotation;
    }

    /**
     * Get a single annotation by id.
     * @param int annotationid
     * @return annotation or false
     */
    public static function get_annotation($annotationid) {
        $record = $DB->get_record('assignfeedback_editpdf_annot', array('id'=>$annotationid), '*', IGNORE_MISSING);
        if ($record) {
            return self::annotation_from_record($record);
        }
        return false;
    }

    /**
     * Add a annotation to a page.
     * @param int gradeid
     * @param int pageno
     * @param annotation
     * @return bool
     */
    public static function add_annotation(annotation $annotation) {
        $annotation->id = null;
        return $DB->insert_record('assignfeedback_editpdf_annot', $annotation);
    }

    /**
     * Remove a annotation from a page.
     * @param int annotationid
     * @return bool
     */
    public static function remove_annotation($annotationid) {
        return $DB->delete_record('assignfeedback_editpdf_annot', array('id'=>$annotationid));
    }
}
