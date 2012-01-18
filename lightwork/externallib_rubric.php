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

class local_lightworkws_rubric_external extends local_lightworkws_common_external {

    public static function get_marking_rubrics_parameters() {
        return new external_function_parameters(
            array(
                'assignmentids' => new external_multiple_structure(new external_value(PARAM_INT, 'assignment id')),
                'timemodified'	=> new external_value(PARAM_INT, 'time modified', FALSE, 0)
            )
        );
    }
    
    public static function get_marking_rubrics($assignmentids = array(), $timemodified = 0) {
        $params = self::validate_parameters(self::get_marking_rubrics_parameters(),
            array('assignmentids' => $assignmentids, 'timemodified' => $timemodified));
        error_log('get_marking_rubrics() - params: ' . var_export($params, TRUE));
        
        $assignments = array();
        
        $assignmentRubric = array();
        $assignmentRubric['id'] = 1;
        $assignments[] = $assignmentRubric;
        
        $assignmentRubric = array();
        $assignmentRubric['id'] = 2;
        $assignmentRubric['rubric'] = array(
            array(
                'id'		        => 21,
                'activity'			=> 21,
                'activitytype'		=> 12,
                'xmltextref'		=> 'text ref',
                'complete'			=> 0,
                'deleted'			=> 0,
                'timemodified'		=> 359857234
            )
        );
        $assignments[] = $assignmentRubric;
        
        $assignmentRubric = array();
        $assignmentRubric['id'] = 3;
        $assignmentRubric['rubric'] = array(
            array(
                'id'		        => 31,
                'activity'			=> 31,
        		'activitytype'		=> 13,
                'xmltextref'		=> 'text ref 31',
                'complete'			=> 1,
                'deleted'			=> 0,
                'timemodified'		=> 35479834
            ),
            array(
                'id'		        => 32,
                'activity'			=> 32,
        		'activitytype'		=> 23,
                'xmltextref'		=> 'text ref 32',
                'complete'			=> 1,
                'deleted'			=> 1,
                'timemodified'		=> 36754890
            )
        );
        $assignments[] = $assignmentRubric;
        
        $result = array();
        $result['assignments'] = $assignments;
        $result['errors'] = array();
        
        return $result;
    }
    
    private static function marking_rubrics() {
        return new external_single_structure(
            array(
                'id'	    => new external_value(PARAM_INT, 'assignment id'),
                'rubric'	=> new external_multiple_structure(new external_single_structure(
                        array(
                            'id'		    => new external_value(PARAM_INT, 'rubric id'),
                            'activity'	    => new external_value(PARAM_INT, 'activity id'),
                            'activitytype' 	=> new external_value(PARAM_INT, 'activity type'),
                            'xmltextref'	=> new external_value(PARAM_TEXT, 'xml text reference'),
                            'complete'		=> new external_value(PARAM_BOOL, 'whether this rubric is completed'),
                            'deleted'		=> new external_value(PARAM_BOOL, 'whether this rubric is deleted'),
                            'timemodified'  => new external_value(PARAM_INT, 'last time rubric was modified')
                        )
                    ), 'rubric records', false)
            )
        );
    }
    
    public static function get_marking_rubrics_returns() {
        return new external_single_structure(
            array(
                'assignments' => new external_multiple_structure(self::marking_rubrics(), 'list of assignment submissions'),
                'errors'	  => self::errors_structure()
            )
        );
    }
}