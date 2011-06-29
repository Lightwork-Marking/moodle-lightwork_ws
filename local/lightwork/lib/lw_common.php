<?php
/**
 *
 *
 * PHP version 5
 *
 * @package LW_Common
 * @version $Id$
 * @author  yyin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */

class LW_Common {
    
    const CID = 'cid:';
    const CAP_MANAGELWMARKERS  = 'moodle/local/lightwork:managelightworkmarkers';
    const CAP_MARKLWSUBMISSIONS = 'moodle/local/lightwork:marklightworksubmissions';
    const STUDENT_MARKING = 1;
    const TEAM_MARKING = 2;
    const FEEDBACK = 3;
    const STUDENT_ROLE = 5;
    const SUBMITTED = 'submitted';
    const UPLOAD_TYPE = 'upload';
    const UPLOADSINGLE_TYPE = 'uploadsingle';
    const TEAM_TYPE = 'team';
    const FEEDBACK_TYPE = 'feedback';
    const WORD_2007_EXT = 'docx';
    const PPT_2007_EXT = 'pptx';
    const EXCEL_2007_EXT = 'xlsx';

    function sanitiseXml($text) {
        return addslashes($text);
    }
    
    function sanitise_for_msoffice2007($filename, $data){
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        error_log('sanitise_for_msoffice2007 $extension : '.$extension);
        if ((strcasecmp($extension, self::WORD_2007_EXT)==0)
        	 || (strcasecmp($extension, self::PPT_2007_EXT)==0)
        	 || (strcasecmp($extension, self::EXCEL_2007_EXT)==0)){
            $data = rtrim($data, "\r\n");    	
        }
        return $data;
    }
    
    function get_submitting_assignment_students_only_sql($assignmentId) {
        global $CFG;
        return "IN (SELECT sb.userid FROM ".
               "{$CFG->prefix}assignment_submissions sb INNER JOIN ".
               "{$CFG->prefix}assignment a on sb.assignment=a.id ".
               "WHERE a.id= '$assignmentId' AND sb.data2 = 'submitted')";
    }
}

?>