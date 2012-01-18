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

class local_lightworkws_courses_external extends local_lightworkws_common_external {

    /**
     * Get Courses
     */
    public static function get_courses_parameters() {
        return new external_function_parameters(
            array(
        		'timemodified' => new external_value(PARAM_INT, 'The last time modified of Courses information this method should return', FALSE, 0)
            )
        );
    }

    public static function get_courses($timemodified = 0) {
        $params = self::validate_parameters(self::get_courses_parameters(),
                        array('timemodified'=>$timemodified));
        error_log('get_courses() - params: ' . var_export($params, TRUE));
        
        $courses = array();
        
        if ($timemodified == 0) {
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
    
    /**
     * Get Course Participants
     */
    public static function get_course_participants_parameters() {
        return new external_function_parameters(
            array(
                'courseids'		=> new external_multiple_structure(new external_value(PARAM_INT, 'course id')),
                'timemodified'	=> new external_value(PARAM_INT, 'time modified', FALSE, 0),
                'allstudents'	=> new external_value(PARAM_BOOL, 'flag to include all students or not', FALSE, 0)
            )
        );
    }
    
    public static function get_course_participants($courseids = array(), $timemodified = 0, $allstudents = 0) {
        $params = self::validate_parameters(self::get_courses_parameters(), array('timemodified'=>$timemodified));
        error_log('get_courses() - params: ' . var_export($params, TRUE));
        
        $result = array();
        $result['courseParticipants'] = array();
        $result['errors'] = array();
        
        return $result;
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
    
}