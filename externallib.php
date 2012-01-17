<?php

/**
 * External lib for Local Lightwork WebServices.
 *
 * @package    local-lightwork
 * @copyright  2011 Massey University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_lightworkws_external extends external_api {

    /**
     * Parameters for getServiceVersion method.
     * @return external_function_parameters
     */
    public static function get_service_version_parameters() {
        return new external_function_parameters(array());
    }

    public static function get_service_version_returns() {
        return new external_value(PARAM_TEXT, 'lightwork webservice version');
    }

    public static function get_service_version() {
        return "3.x" ;
    }

    /**
     * Get Courses
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
            array(
        		'timemodified' => new external_value(PARAM_INT, 'The last time modified of Courses information this method should return', VALUE_DEFAULT, 0)
            )
        );
    }

    public static function get_courses($timemodified = 0) {
        $params = self::validate_parameters(self::get_courses_parameters(),
                        array('timemodified'=>$timemodified));
        error_log('get_courses() - params: ' . var_export($params, TRUE));
        
        $courses = array();
        
        for ($i = 0; $i < 2; $i++) {
            $course_info = array();
            $course_info['id'] = $i;
            $course_info['fullname'] = 'Full Course ' . $i;
            $course_info['shortname'] = 'Short Course ' . $i;
            $course_info['timemodified'] = $params['timemodified'] + $i;
            $course_info['assignments'] = array(
                array(
                    'id' 		=> $i,
                    'course'	=> $i,
                    'name'		=> 'Assignment ' . $i,
                    'timedue'	=> ($i * 10000) + 12345678,
                    'assignmenttype' => 'Random Type',
                    'grade'		=> 100,
                    'timemodified' => $params['timemodified'] - $i
                )
            );
                
            $courses[] = $course_info;
        }
        
        $result = array();
        $result['courses'] = $courses;
        $result['errors'] = array();
        return $result;
    }
    
    private static function assignment_record() {
        return new external_single_structure(
            array(
                'id'		=> new external_value(PARAM_INT, 'assignment id'),
                'course'	=> new external_value(PARAM_INT, 'course id'),
                'name'		=> new external_value(PARAM_TEXT, 'assignment name'),
                'timedue'	=> new external_value(PARAM_INT, 'assignment due time'),
                'assignmenttype'	=> new external_value(PARAM_TEXT, 'assignment type'),
                'grade'		=> new external_value(PARAM_INT, 'grade type'),
				'timemodified'	    => new external_value(PARAM_INT, 'last time assignment was modified')
            ), 'assignment information object');
    }
    
    private static function course_record() {
        return new external_single_structure(
            array(
          		'id'		=> new external_value(PARAM_INT, 'course id'),
           		'fullname'	=> new external_value(PARAM_TEXT, 'course full name'),
				'shortname' => new external_value(PARAM_TEXT, 'course short name'),
            	'timemodified' => new external_value(PARAM_INT, 'last time modified'),
            	'assignments'	=> new external_multiple_structure(self::assignment_record(), 'list of assignment information')
              ), 'course information object' );
    }
        
    public static function get_courses_returns() {
        return new external_single_structure(
            array(
                'courses'	=> new external_multiple_structure(self::course_record(), 'list of courses information'),
                'errors'	=> self::errors_structure()
            )
        );
    }
    
    private static function errors_structure() {
        return new external_multiple_structure( 
            new external_single_structure( array(
                'element'	=> new external_value(PARAM_TEXT, 'error element'),
                'id'		=> new external_value(PARAM_INT, 'error id'),
                'errorcode' => new external_value(PARAM_TEXT, 'error code'),
                'errormessage' => new external_value(PARAM_TEXT, 'error message')
            ), 'errorRecord'), 'list of errorRecords'
        );
    }
    
    /**
     * Get Course Participants
     */
    public static function get_course_participants_parameters() {
        return new external_function_parameters(
            array(
                'courseids'		=> new external_multiple_structure(new external_value(PARAM_INT, 'course id')),
                'timemodified'	=> new external_value(PARAM_INT, 'time modified'),
                'allstudents'	=> new external_value(PARAM_BOOL, 'flag to include all students or not')
            )
        );
    }
    
    public static function get_course_participants() {
        
    }
    
    public static function get_course_participants_returns() {
        return new external_single_structure(
            array(
                'courseParticipants' => new external_multiple_structure(self::course_participant(), 'list of courses participant information'),
                'errors'	         => self::errors_structure()
            )
        );
    }
    
    private static function course_participant() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'course id'),
                'user' => new external_multiple_structure(new external_single_structure(
                    array(
                        'id'         => new external_value(PARAM_INT, 'user id'),
                        'username'	 => new external_value(PARAM_TEXT, 'username'),
                        'idnumber'   => new external_value(PARAM_TEXT, 'idnumber'),
                        'firstname'  => new external_value(PARAM_TEXT, 'first name'),
                        'lastname'	 => new external_value(PARAM_TEXT, 'last name'),
                        'roleid'	 => new external_value(PARAM_INT, 'role id'),
                        'capabilitycode' => new external_value(PARAM_TEXT, 'capability code'),
                        'timemodified' => new external_value(PARAM_INT, 'time modified')
                    )
                ))
            )
        );
    }
    
    /**
     * Get Submissions
     */
    public static function get_submissions_parameters() {
        return new external_function_parameters(
            array(
                'assignmentids' => new external_multiple_structure(new external_value(PARAM_INT, 'assignment id')),
                'timemodified'	=> new external_value(PARAM_INT, 'time modified'),
                'allstudents'	=> new external_value(PARAM_BOOL, 'flag to include all students or not')
            )
        );
    }
    
    public static function get_submissions() {
        
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
    
    /**
     * Get Marking Rubrics
     */
    public static function get_marking_rubrics_parameters() {
        return new external_function_parameters(
            array(
            	'assignmentids' => new external_multiple_structure(new external_value(PARAM_INT, 'assignment id')),
                'timemodified'	=> new external_value(PARAM_INT, 'time modified')
            )
        );
    }
    
    public static function get_marking_rubrics() {
        
    }
    
    private static function marking_rubrics() {
        return new external_single_structure(
            array(
                'id'	    => new external_value(PARAM_INT, 'assignment id'),
                'rubrics'	=> new external_multiple_structure(new external_single_structure(
                    array(
                        'id'		    => new external_value(PARAM_INT, 'rubric id'),
                        'activity'	    => new external_value(PARAM_INT, 'activity id'),
                        'activitytype' 	=> new external_value(PARAM_INT, 'activity type'),
                        'xmltextref'	=> new external_value(PARAM_TEXT, 'xml text reference'),
                        'complete'		=> new external_value(PARAM_BOOL, 'whether this rubric is completed'),
                        'deleted'		=> new external_value(PARAM_BOOL, 'whether this rubric is deleted'),
                        'timemodified'  => new external_value(PARAM_INT, 'last time rubric was modified')
                    )
                ))
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