<?php

/**
 * External lib for Local Lightwork WebServices.
 *
 * @package    local-lightwork
 * @copyright  2011-2012 Massey University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");
require_once('externallib_common.php');

class local_lightworkws_submissions_external extends local_lightworkws_common_external {
    
    /**
    * Get Submissions
    */
    public static function get_submissions_parameters() {
        return new external_function_parameters(
            array(
    			'assignmentids' => new external_multiple_structure(new external_value(PARAM_INT, 'assignment id')),
    			'timemodified'	=> new external_value(PARAM_INT, 'time modified', FALSE, 0),
                'allstudents'	=> new external_value(PARAM_BOOL, 'flag to include all students or not', FALSE, 0)
            )
        );
    }
    
    public static function get_submissions($assignmentids = array(), $timemodified = 0, $allstudents = 0) {
        $params = self::validate_parameters(self::get_submissions_parameters(),
            array('assignmentids' => $assignmentids, 'timemodified' => $timemodified, 'allstudents' => $allstudents));
        error_log('get_submissions() - params: ' . var_export($params, TRUE));
        
        $submissions = array();
        
        for ($i = 1; $i < 3; $i++) {
            $submission_wrapper = array();
            $submission_wrapper['assignmentid'] = $i;
            $submission_wrapper['submissions'] = array();
            
            $submission_info = array();
            $submission_info['id'] = $i;
            $submission_info['userid'] = $i;
            $submission_info['timecreated'] = $i * 4332 + 574389475;
            $submission_info['timemodified'] = $submission_info['timecreated'];
            $submission_info['numfiles'] = 1;
            $submission_info['status'] = 'TEST';
            $submission_info['grade'] = 100.0;
            $submission_info['submissioncomment'] = 'submit assignment';
            $submission_info['teacher'] = 3;
            $submission_info['timemarked'] = $submission_info['timemodified'];

            $submission_wrapper['submissions'][] = $submission_info;
            
            $submissions[] = $submission_wrapper;
        }
        
        $result = array();
        $result['assignments'] = $submissions;
        $result['errors'] = array();
        return $result;
    }
    
    private static function assignment_submissions() {
        return new external_single_structure(
            array (
        		'assignmentid'	=> new external_value(PARAM_INT, 'assignment id'),
            	'submissions'   => new external_multiple_structure(new external_single_structure(
                        array(
                            'id'		    => new external_value(PARAM_INT, 'submission id'),
            				'userid'	    => new external_value(PARAM_INT, 'student id'),
            				'timecreated'   => new external_value(PARAM_INT, 'submission creation time'),
            				'timemodified'  => new external_value(PARAM_INT, 'submission last modified time'),
            				'numfiles'	    => new external_value(PARAM_INT, 'number of files in the submission'),
            				'status'	    => new external_value(PARAM_TEXT, 'submission status'),
                            'grade'		    => new external_value(PARAM_FLOAT, 'grade'),
            				'submissioncomment' => new external_value(PARAM_TEXT, 'submission comment'),
            				'teacher'	    => new external_value(PARAM_INT, 'teacher id'),
            				'timemarked'    => new external_value(PARAM_INT, 'time the submission was marked')
                        )
                ))
            )
        );
    }
    
    public static function get_submissions_returns() {
        return new external_single_structure(
            array(
            	'assignments' => new external_multiple_structure(self::assignment_submissions(), 'list of assignment submissions'),
            	'errors'	  => self::errors_structure()
            )
        );
    }
    
}