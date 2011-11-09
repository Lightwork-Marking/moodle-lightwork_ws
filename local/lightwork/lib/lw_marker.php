<?php

/**
 * Class for handling querying and manipulating LW Marker records.
 *
 * PHP version 5
 *
 * @package LW_Marker
 * @version $Id$
 * @author Dean Stringer <deans@waikato.ac.nz>
 * @author David Vega Morales <davidvm@waikato.ac.nz>
 * @author Yoke Chui <yokec@waikato.ac.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 * notes:
 * o we dont do any user validation, the constructor is passed a userid and we assume the calling class has
 *   access to be querying the data for that user
 */


/**
 * Class for handling querying and manipulating individual Marker records. Checking their
 * properties, inserting, updating deleting etc.
 * @package LW_Marker
 */
require_once('lw_common.php');
require_once('lw_team.php');


class LW_Marker  {

    private $uid;
    private $courses;
    private $coursesids;
    private $table;
    private $historytable;
    private $markable;
    public  $error;
    public  $helper;
    public  $type;

    public function __construct($uid=null, $type)  {
        if (is_numeric($uid) && ($uid>0)) {
            $this->uid = $uid;
        } else {
            return null;
        }
        $this->type = $type;
        switch($type){
            case LW_Common::STUDENT_MARKING:
                $this->table = 'lw_marking';
                $this->historytable = 'lw_marking_history';
                $this->markable = 'student';
                break;
            case LW_Common::TEAM_MARKING:
                $this->table = 'lw_team_marking';
                $this->historytable = 'lw_team_marking_history';
                $this->markable = 'team';
                break;
            case LW_Common::FEEDBACK:
                $this->table = 'lw_feedback';
                $this->markable = 'student';
                break;
            default:
                return null;
        }
        $this->error = new LW_Error();
        $this->helper = new LW_Common();
        $this->courses = array();
        $this->coursesids = array();
    }


    /**
     * Gets the courses the a marker has access to.
     * @return  array    List of courses objects
     * @todo filter courses based on courselist paramater
     */
    public function get_courses($course_list=null) {
        if (!count($this->courses)) {
            $this->load_courses();
        }
        return $this->courses;
    }

    /**
     * Gets the ids of courses the a marker has access to.
     * @return  array    List of courses objects
     */
    public function get_courses_ids() {
        if (!count($this->coursesids)) {
            $this->load_courses();
        }
        return $this->coursesids;
    }

    /**
     * Get a list of submissions that have been modified since a given date/time
     * @param   array|int $assignmentlist An array or id of assignments
     * @param   int       $timemodified   Modified time
     * @param   bool      $getall         The function will return all submission allow for this user.  You still to pass $assigmentlist ("0" is a good value)
     * @return  array     $submissions    Array of course objects
     * TODO remove getall parameter if not needed
     */
    function assignment_submissions_since($assignmentlist, $timemodified, $getall=false, $allstudents) {
        global $CFG, $DB;

        require_once("$CFG->dirroot/mod/assignment/lib.php");

        $rassignments = array();

        $checkassignmentids = array();
        $assignmentids = array();
        $assignmentstudents = array();

        //  Put all ids in an array
        if (is_array($assignmentlist)) {
            $checkassignmentids = $assignmentlist;
        }
        else if($assignmentlist) {
            $checkassignmentids[] = $assignmentlist;
        }

        foreach ($this->get_courses() as $course) {
            foreach ($course['assignments'] as $assignment) {
                if (in_array($assignment['id'], $checkassignmentids) || $getall)  {
                    $assignmentids[] = $assignment['id'];
                }
            }
        }

        //  Get all the students that have submitted work depends on 'allstudents' parameter for the requested assignments
        $assignmentstudents = $this->get_submitting_students_for_assignment($assignmentids, 0, $allstudents);

        foreach ($assignmentstudents as $assignstudent) {
            $rsubmissions = array();

            $cm = get_coursemodule_from_instance('assignment', $assignstudent['id']);
            $assignment = $DB->get_record('assignment', array('id'=>$cm->instance));
            $course = $DB->get_record('course', array('id'=>$assignment->course));

            if ( $this->is_allowed_assignment_type($assignment) ) {
                //  Get the assignment object
                require_once("$CFG->dirroot/mod/assignment/type/$assignment->assignmenttype/assignment.class.php");
                $assignmentclass = "assignment_$assignment->assignmenttype";
                $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

                foreach ($assignstudent['students'] as $student) {
                    //  Get the submission for this student
                    $submission = $assignmentinstance->get_submission($student['id']);

                    if ($submission && (($submission->timemodified > $timemodified) || ($submission->timemarked > $timemodified))) {

                        //  Set up the status flag
                        $data2 = 'draft';
                        if (($assignment->assignmenttype == 'uploadsingle') || ($submission->data2 == 'submitted')) {
                            $data2 = 'submitted';
                        }

                        $rsubmissions[] =   array(
                                'id'               =>$submission->id,
                                'userid'           =>$student['id'],
                                'timecreated'      =>$submission->timecreated,
                                'timemodified'     =>$submission->timemodified,
                                'numfiles'         =>$submission->numfiles,
                                'status'           =>$data2,
                                'grade'            =>$submission->grade,
                                'submissioncomment'=>$submission->submissioncomment,
                                'teacher'          =>$submission->teacher,
                                'timemarked'       =>$submission->timemarked
                        );
                    }
                }
            }

            if (count($rsubmissions)) {
                $rassignments['assignment'][] = array(
                        'assignmentid'          =>$assignstudent['id'],
                        'courseid'    =>$assignment->course,
                        'submissions' =>$rsubmissions
                );
            }
        }

        return $rassignments;

    }

    function is_allowed_assignment_type( $assignment ){
        return ($assignment->assignmenttype == LW_Common::UPLOAD_TYPE)
        || ($assignment->assignmenttype == LW_Common::UPLOADSINGLE_TYPE)
        || ($assignment->assignmenttype == LW_Common::TEAM_TYPE
        || ($assignment->assignmenttype == LW_Common::FEEDBACK_TYPE));
    }
    
    function is_tii_type_assignment($assignment){
    	return ($assignment->assignmenttype == LW_Common::UPLOAD_TYPE)
        || ($assignment->assignmenttype == LW_Common::UPLOADSINGLE_TYPE)
        || ($assignment->assignmenttype == LW_Common::TEAM_TYPE);
    }

    /**
     * Return submissions file records representing submissions for an assignment
     * @param   int       $assignmentid       the id of the Assignment
     * @param   array|int $assigmentsubmissionlist    An array or id of submission ids
     * @return  array     $rsubmissions       Array of submission file and turnitin records
     */

    function assignment_submission_files($assignmentid, $assignmentsubmissionlist) {
        global $CFG, $DB;

        require_once("$CFG->dirroot/mod/assignment/lib.php");
         
        $rsubmissionsandtiilinks = array('submissionfiles'=>array(), 'tiifiles'=>array());
        $assignmentsubmissionids = array();
        $idsfound = array();

        if (is_array($assignmentsubmissionlist)) {
            $assignmentsubmissionids = $assignmentsubmissionlist;
        } else if (isset($assignmentsubmissionlist)){
            $assignmentsubmissionids[] = $assignmentsubmissionlist;
        } else {
            return new soap_fault('Client', '', 'assignment_submission_files expects at least submission id');
        }

        if (!isset($assignmentid)){
            return new soap_fault('Client', '', 'assignment_submission_files expects an assignment id');
        }
        
        $assignmentsubmissions = $this->assignment_submissions_since($assignmentid,0,false, true);

        $cm = get_coursemodule_from_instance('assignment', $assignmentid);
        $assignment = $DB->get_record('assignment', array('id'=>$cm->instance));
        $course = $DB->get_record('course', array('id'=>$assignment->course));
        
        //  Get the assignment object
        require_once("$CFG->dirroot/mod/assignment/type/$assignment->assignmenttype/assignment.class.php");
        $assignmentclass = "assignment_$assignment->assignmenttype";
        $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

        include_once($CFG->libdir.'/ddllib.php');
        $tiifiletable = new XMLDBTable('tii_files');
        $useTii = $this->is_tii_type_assignment($assignment) && $DB->get_manager()->table_exists($tiifiletable);
                  
        $plagiarismsettings = false;
        if ($useTii) {
        	error_log('found tii_files table ');
            include_once($CFG->libdir.'/turnitinlib.php');
            $plagiarismsettings = get_settings();
        }
        if ( $this->is_allowed_assignment_type($assignment) ) {
        	// TODO fix so that this works for teams
        	$fs = get_file_storage();
            foreach ($assignmentsubmissions['assignment'][0]['submissions'] as $submissionarr) {
                if (in_array($submissionarr['id'], $assignmentsubmissionids)) {
                    $idsfound[] = $submissionarr['id'];
                    if ($assignment->assignmenttype == 'team'){
                        $team = $assignmentinstance->get_user_team($submissionarr['userid']);
                        if (!isset($team)) {
                            //We handle this by comparing the local and Moodle team 
                            //after finishing assignment file submission synchronization.
                            continue;
                        }
                        $basedir = $assignmentinstance->team_file_area_name($team->id);
                    } else {
                        $is_empty = $fs->is_area_empty($assignmentinstance->context->id,'mod_assignment','submission',$submissionarr['id']);                        
                    }
                    if (!$is_empty) {
                    	$files = $fs->get_area_files($assignmentinstance->context->id,'mod_assignment','submission',$submissionarr['id'],false);

                        $submission = array(
                                    'id'          => $submissionarr['id'],
                                    'fileref'     => $submissionarr['userid'].'/'. md5(uniqid(time())) .'.zip',
                                    'mime'        => 'application/zip',
                                    'status'      => $submissionarr['status'],
                                    'timemodified'=> $submissionarr['timemodified'],
                                    'files'       => array()
                        );

                        foreach ($files as $file) {
                            $submission['files'][$file->get_filename()] = $file;
                            if ($useTii && $plagiarismsettings) {
                                $tiifile = get_record_select('tii_files', "course='".$course->id.
                                                "' AND module='".get_field('modules', 'id',array('name'=>'assignment')).
                                                "' AND instance='".$cm->instance.
                                                "' AND userid='".$submissionarr['userid'].
                                                "' AND filename='".$file."'");
                                     
                                if (isset($tiifile->tiiscore) && $tiifile->tiicode=='success' ) {
                                    $tiilink = tii_get_report_link($tiifile) ;
                                    $rsubmissionsandtiilinks['tiifiles']['tiifile'][] = array( 'id' => $tiifile->id,
                               	            'submissionid' => $submissionarr['id'],
                                            'filename' => $tiifile -> filename,
                                            'tiiscore' => $tiifile -> tiiscore,
                                            'tiicode' => $tiifile -> tiicode,
                                            'tiilink' => $tiilink );
                                }
                            }
                        }
                        $rsubmissionsandtiilinks['submissionfiles']['submissionfile'][] = $submission;
                    }
                }
            }
        }
        else {
            $this->error->add_error('Submission', $submissionarr['id'], 'nosupportedassigntype');
        }

        $notfoundids = array_diff($assignmentsubmissionids, $idsfound);

        foreach ($notfoundids as $id) {
            $this->error->add_error('Submission', $id, 'nosubmissionfound');
        }
        return $rsubmissionsandtiilinks;
    }

    /**
     * Get a list of course objects that have been modified since a given date/time
     * @param   array|int $courselist    An array or id of courses
     * @param   int       $timemodified  Modified time
     * @return  array     $courses       Array of course objects
     */
    function courses_modified_since($courselist, $timemodified) {
        $courses = array();
        $courseids = array();

        //  Put all ids in an array
        if (is_array($courselist)) {
            $courseids = $courselist;
        }
        else if($courselist) {
            $courseids[] = $courselist;
        }

        //  Get all the courses that requested
        if (count($courseids)) {
            foreach ($this->get_courses() as $course) {
                // filter with id(s)
                if (in_array($course['id'], $courseids)) {
                    $assignments = array();
                    // check if there are any assignments
                    if (count($course['assignments'])) {
                        // check if any assignments have been modified
                        foreach ($course['assignments'] as $assignment) {
                            if ($assignment['timemodified'] > $timemodified) {
                                $assignments['assignment'][]= $assignment;
                            }
                        }
                    } else {
                        continue;
                    }

                    $course['assignments'] = $assignments;
                    // if course or assignments modified return the current course record
                    if (count($assignments) || ($course['timemodified'] > $timemodified)) {
                        $courses['course'][] = $course;
                    }
                }
            }
        } else {
            //  if no id(s) specified then return all courses that the user has access to
            foreach ($this->get_courses() as $course) {
                $assignments = array();
                // check if there are any assignments
                if (count($course['assignments'])) {
                    // check if any assignments have been modified
                    foreach ($course['assignments'] as $assignment) {
                        if ($assignment['timemodified'] > $timemodified) {
                            $assignments['assignment'][]= $assignment;
                        }
                    }
                } else {
                    continue;
                }
                $course['assignments'] = $assignments;
                // if course or assignments modified return the current course record
                if (count($assignments) || ($course['timemodified'] > $timemodified)) {
                    $courses['course'][] = $course;
                }
            }
        }

        return $courses;
    }

    /**
     * Load an array of course objects for the user and save them and the course ids
     * @return  bool    true if user has access
     * @todo the call to get_user_courses_by_cap is too general, need to narrow to mod/assignment:manage or something
     */
    private function load_courses() {
        $returnvalue = false;
        $fields = 'sortorder,shortname, idnumber,fullname,timecreated,timemodified';

        $courseids = array();
        $courses = new object();
        
        $courses = get_user_capability_course(LW_Common::CAP_MARKLWSUBMISSIONS,$this->uid,false,$fields,'sortorder ASC');

        // now get all the ids from those courses and save those against this Marker user object
        $extrafields='m.id, m.course, m.name, m.assignmenttype, m.grade, m.timedue, m.timemodified';
        $coursearray = array();
        foreach ($courses as $k1 => $v1) {
            // save the IDs only for the ->courseids property which is a simple array of them
            $courseids[]= $courses[$k1]->id;
            $assignmentarray = array();
            // get a list of assignments for the course
            if ($assignments = get_coursemodules_in_course('assignment', $courses[$k1]->id, $extrafields)) {
                // need to convert the course and assignment objects to arrays for the serialisation of the SOAP payload later
                foreach ($assignments as $assignment) {
                    $assignmentarray[]= array('id'=>$assignment->id,
                                              'course'=>$assignment->course,
                                              'name'=>$assignment->name,
                                              'timedue'=>$assignment->timedue,
                                              'assignmenttype'=>$assignment->assignmenttype,
                                              'grade'=>$assignment->grade,
                                              'timemodified'=>$assignment->timemodified
                    );
                }
            }

            $coursearray[]= array('id'=>$courses[$k1]->id,
                                  'fullname'=>$courses[$k1]->fullname,
                                  'shortname'=>$courses[$k1]->shortname,
                                  'timemodified'=>$courses[$k1]->timemodified,
                                  'assignments'=>$assignmentarray
            );
        }
        $this->courses = $coursearray;
        $this->courseids = $courseids;
        $returnvalue = true;

        return $returnvalue;
    }

    /**
     * Return students that have submitted work and that this marker can mark grouped by assignment.
     * @param array|int $assignment    An array or id of assignments
     * @param int       $timemodified Modified time
     * @return array with assigments and list of student for each assigment
     */
    private function get_submitting_students_for_assignment($assignments, $timemodified, $allstudents) {
        $returnassignments = array();
        $assignmentids = array();
        $markerassigments = array();
        $students = array();

        //  Put all ids in an array
        if (is_array($assignments)) {
            $assignmentids = $assignments;
        }
        else if($assignments) {
            $assignmentids[] = $assignments;
        }

        //  Get all the assigment the were asked for (if marker have access)
        $courses = $this->get_courses();
        foreach ($courses as $course) {
            $coursecontext;
            foreach ($course['assignments'] as $assignment) {
                if (in_array($assignment['id'], $assignmentids)) {
                    if (!isset($coursecontext)){
                        $coursecontext = get_context_instance(CONTEXT_COURSE, $course['id']); 
                    }
                    $assignarr = array();
                    if ($allstudents == 0) {
                        $students = $this->load_submitted_students($coursecontext, $assignment['id'], $timemodified);
                    } else {
                    	$students = $this->load_draft_and_submitted_students($coursecontext, $assignment['id'], $timemodified);
                    }
                    if (has_any_capability(array(LW_Common::CAP_MANAGELWMARKERS, LW_Common::CAP_MARKLWSUBMISSIONS), 
                                           $coursecontext, 
                                           $this->uid, 
                                           false)){
                        $assignarr['id'] = $assignment['id'];
                        $assignarr['students'] = $students;
                        $returnassignments[] = $assignarr;
                    }
                }
            }
        }
        return $returnassignments;
    }
    
    /**
     * Returns the students that have submitted work to an assignment.
     * @param $context
     * @param $roles
     * @param $assignmentid
     * @param $timemodified
     * @return unknown_type
     */
    private function load_submitted_students($context, $assignmentid, $timemodified=0) {
        global $CFG, $DB;        
        $participants = array();
        $students = array();
        
        if ($roles = get_roles_with_capability('mod/assignment:submit', CAP_ALLOW, $context)) {
            foreach ($roles as $role) {
                    $roleids[]= $role->id;
            }
            $rolesql = ' AND ra.roleid IN ('. implode(',', $roleids) . ')';
        }
                              
        // get student participants who have submitted at least one assignment
        $sql = '';
        $assignment = $DB->get_record('assignment', array('id'=>$assignmentid));
        if ($assignment->assignmenttype == LW_Common::FEEDBACK_TYPE){
            $sql = "SELECT u.id,u.username,u.idnumber,u.firstname,u.lastname,u.timemodified,ra.roleid".
                   " FROM {user} u INNER JOIN".
                   " {role_assignments} ra on u.id=ra.userid".
                   " WHERE ra.contextid = '$context->id'".
                   " AND u.id IN (SELECT sb.userid FROM {feedback_submission} fs INNER JOIN ".
                                  "{assignment_submissions} sb on fs.submission = sb.id INNER JOIN ".
                                  "{assignment} a on sb.assignment=a.id ".
                                  "WHERE a.id= '$assignmentid' AND fs.timefirstsubmitted IS NOT NULL)".
                   $rolesql." AND (ra.timemodified >= '$timemodified' OR u.timemodified >= '$timemodified')";
        } else if (($assignment->assignmenttype == LW_Common::UPLOAD_TYPE || $assignment->assignmenttype == LW_Common::TEAM_TYPE)
                    && $assignment->var4 == 1){ // Advanced upload or team with send for marking set to yes
            $sql = "SELECT u.id,u.username,u.idnumber,u.firstname,u.lastname,u.timemodified,ra.roleid".
                   " FROM {user} u INNER JOIN".
                   " {role_assignments} ra on u.id=ra.userid".
                   " WHERE ra.contextid = '$context->id'".
                   " AND u.id IN (SELECT sb.userid FROM {assignment_submissions} sb INNER JOIN ".
                                  "{assignment} a on sb.assignment=a.id ".
                                  "WHERE a.id= '$assignmentid' AND sb.data2 = 'submitted')".
                   $rolesql." AND (ra.timemodified >= '$timemodified' OR u.timemodified >= '$timemodified')"; 
        } else {
            $sql = "SELECT u.id,u.username,u.idnumber,u.firstname,u.lastname,u.timemodified,ra.roleid".
                   " FROM {user} u INNER JOIN".
                   " {role_assignments} ra on u.id=ra.userid".
                   " WHERE ra.contextid = '$context->id'".
                   " AND u.id IN (SELECT sb.userid FROM {assignment_submissions} sb INNER JOIN ".
                                  "{assignment} a on sb.assignment=a.id ".
                                  "WHERE a.id= '$assignmentid')".
                   $rolesql." AND (ra.timemodified >= '$timemodified' OR u.timemodified >= '$timemodified')";	
        }
        if ($students = $DB->get_records_sql($sql)) {
            foreach ($students as $participant) {
                $tempparticipant = array();
                foreach ($participant as $k1 => $v1) {
                    $tempparticipant[$k1] = $v1;
                }
                $tempparticipant['capabilitycode'] = '';
                $participants[] = $tempparticipant;
            }
        }
        
        return $participants;
    }
    
 /**
     * Returns the students that have submitted or draft work to an assignment.
     * @param $context
     * @param $roles
     * @param $assignmentid
     * @param $timemodified
     * @return unknown_type
     */
    private function load_draft_and_submitted_students($context, $assignmentid, $timemodified=0) {
        global $CFG, $DB;        
        $participants = array();
        $students = array();
        
        if ($roles = get_roles_with_capability('mod/assignment:submit', CAP_ALLOW, $context)) {
            foreach ($roles as $role) {
                    $roleids[]= $role->id;
            }
            $rolesql = ' AND ra.roleid IN ('. implode(',', $roleids) . ')';
        }
                              
        // get student participants has draft submissions or final submitted submissions.
        $sql = "SELECT u.id,u.username,u.idnumber,u.firstname,u.lastname,u.timemodified,ra.roleid".
                   " FROM {user} u INNER JOIN".
                   " {role_assignments} ra on u.id=ra.userid".
                   " WHERE ra.contextid = '$context->id'".
                   " AND u.id IN (SELECT sb.userid FROM {assignment_submissions} sb INNER JOIN ".
                                  "{assignment} a on sb.assignment=a.id ".
                                  "WHERE a.id= '$assignmentid' )".
                   $rolesql." AND (ra.timemodified >= '$timemodified' OR u.timemodified >= '$timemodified')"; 
        
        if ($students = $DB->get_records_sql($sql)) {
            foreach ($students as $participant) {
                $tempparticipant = array();
                foreach ($participant as $k1 => $v1) {
                    $tempparticipant[$k1] = $v1;
                }
                $tempparticipant['capabilitycode'] = '';
                $participants[] = $tempparticipant;
            }
        }
        
        return $participants;
    }

    /**
     * Retrieve a list of the marking records assigned to this marker for an assignment
     * @return  array   An array of marking allocations for this marker
     */
    private function get_marking_allocation($assignmentId) {
        global $CFG, $DB;
        $allocation = $DB->get_records_sql("SELECT id,$this->markable as markable
                                FROM {".$this->table."}
                                WHERE marker = '$this->uid' AND activity = '$assignmentId'");
        return $allocation;
    }

    /**
     * Retieve a list of participant who have been added to the course since a given time
     * or their record has been modified since a given time
     *
     * Fetches a list of roles that have $capability for the given course context and
     * checks the timemodified property of the records in the role_assignments table
     * for each of those roles and the given context id
     * TODO consider moving this method to a more appropriate location, e.g mdl_soapserver.class.php
     *
     * @param   int     $courseid
     * @param   int     $timemodified
     * @param   string  $capability
     * @return  array   of participant objects
     */
    function course_participants_since($courseid, $timemodified=0, $capability=null) {
        global $CFG, $DB;

        $rolesql = '';
        $roleids = array();
        $participants = array();
        $context = get_context_instance(CONTEXT_COURSE, $courseid);

        if ($capability) {
            if ($roles = get_roles_with_capability($capability, CAP_ALLOW, $context)) {
                foreach ($roles as $role) {
                    $roleids[]= $role->id;
                }
                $rolesql = ' AND ra.roleid IN ('. implode(',', $roleids) . ')';
            }
        }

        if ($roleparticipants = $DB->get_records_sql("SELECT u.id,u.username,u.idnumber,u.firstname,u.lastname,u.timemodified,ra.roleid ".
                                "FROM {user} u INNER JOIN ".
                                "{role_assignments} ra on u.id=ra.userid ".
                                "WHERE ra.contextid = '$context->id' ".
                                "AND (ra.timemodified >= '$timemodified' OR u.timemodified >= '$timemodified')".
        $rolesql
        )) {
            foreach ($roleparticipants as $participant) {
                $tempparticipant = array();
                foreach ($participant as $k1 => $v1) {
                    $tempparticipant[$k1] = $v1;
                }
                if (has_capability(LW_Common::CAP_MANAGELWMARKERS , $context, $tempparticipant['id'], false)) {
                    $tempparticipant['capabilitycode'] = 'MG';
                } else if(has_capability(LW_Common::CAP_MARKLWSUBMISSIONS , $context, $tempparticipant['id'], false)) {
                    $tempparticipant['capabilitycode'] = 'MA';
                } else {
                    $tempparticipant['capabilitycode'] = '';
                }
                $participants[] = $tempparticipant;
            }
        }

        return $participants;
    }
    
    /**
     * Returns the students that have submitted at least once to an assignment on this course.
     * @param $courseid
     * @param $timemodified
     * @return unknown_type
     */
    function course_submitting_students_since($courseid, $timemodified=0) {
        global $CFG, $DB;        
        $participants = array();
        $students = array();
        
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        
        if ($roles = get_roles_with_capability('moodle/legacy:student', CAP_ALLOW, $context)) {
            foreach ($roles as $role) {
                    $roleids[]= $role->id;
            }
            $rolesql = ' AND ra.roleid IN ('. implode(',', $roleids) . ')';
        }
                              
        // get student participants who have submitted at least one assignment
        if ($students = $DB->get_records_sql("SELECT u.id,u.username,u.idnumber,u.firstname,u.lastname,u.timemodified,ra.roleid".
                                " FROM {user} u INNER JOIN".
                                " {role_assignments} ra on u.id=ra.userid".
                                " WHERE ra.contextid = '$context->id'".
                                " AND u.id IN (SELECT sb.userid FROM {assignment_submissions} sb INNER JOIN ".
                                             "{assignment} a on sb.assignment=a.id ".
                                             "WHERE a.course= '$courseid' AND sb.data2 = 'submitted')".
                                $rolesql.
                                " AND (ra.timemodified >= '$timemodified' OR u.timemodified >= '$timemodified')" )) {
            foreach ($students as $participant) {
                $tempparticipant = array();
                foreach ($participant as $k1 => $v1) {
                    $tempparticipant[$k1] = $v1;
                }
                $tempparticipant['capabilitycode'] = '';
                $participants[] = $tempparticipant;
            }
        }
        
        return $participants;
    }

    /**
     * Saves making sheets in to the database
     * @param  array     $markings    An array of makings to be saved in to the database
     * @param  boolean   $releaseMarking true if the marks are to be released
     * @param  int       $allstudents if 1(true) then all students are included. If 0(false) only
     * students who have submitted work (they have a record in mdl_assignment_submissions for an
     * assignment belonging to the course) are included.
     * @return array     $rmarkings   An array of successfully added/updated making sheets
     */
    public function save_marking($assignmentmarkings, $releaseMarking = false, $allstudents) {
        global $CFG, $DB;
        require_once("$CFG->libdir/gradelib.php");
        $rmarkings = array();
        foreach ($assignmentmarkings as $activity => $markings) {             
            $markingrecords = $this->get_marking_allocation($activity);
            $assignment = $DB->get_record('assignment', array('id'=>$activity));
            if (!$assignment){
                $this->error->add_error('Marking', $activity, 'noassignmentfound');
                continue;
            }
            if ($releaseMarking){
                $assignment_grading_info = grade_get_grades($assignment->course, 'mod', 'assignment', $assignment->id, null);
                if ($assignment_grading_info->items[0]->locked){
                    error_log('grade locked');
                    $this->error->add_error('Marking', $activity, 'assignmentgradeitemlocked');
                    continue;
                }
                if (!$this->is_allowed_assignment_type($assignment)){
                    error_log('assignment type is not allowed');
                    $this->error->add_error('Marking', $activity, 'unsupportedassignmenttype');
                    continue;
                }
            }
            $context = get_context_instance(CONTEXT_COURSE, $assignment->course);
            $ismanager = has_capability(LW_Common::CAP_MANAGELWMARKERS , $context, $this->uid, false);
            $ismarker = has_capability(LW_Common::CAP_MARKLWSUBMISSIONS , $context, $this->uid, false);
            $studentparticipants = array();
            if ($allstudents == 0){
                $studentparticipants = $this->load_submitted_students($context, $assignment->id, 0);
            } else {
                $studentparticipants = $this->course_participants_since($assignment->course, 0, 'moodle/legacy:student');
            }
            $markerparticipants = $this->course_participants_since($assignment->course, 0, LW_Common::CAP_MARKLWSUBMISSIONS);
             
            foreach ($markings as $marking) {
                if (!$marking['marker']) {
                    $this->error->add_error('Marking', $marking['activity'], 'errsysaddmarking');
                    continue; // cant save without a marker id
                }
                if (!$marking['markable']) {
                    $this->error->add_error('Marking', $marking['activity'], 'errsysaddmarking');
                    continue; // cant save without a student id
                }
                if (!$marking['activity']) {
                    $this->error->add_error('Marking', $marking['activity'], 'errsysaddmarking');
                    continue; // cant save without an activity
                }
                if (!$marking['rubric']) {
                    $this->error->add_error('Marking', $marking['activity'], 'errsysaddmarking');
                    continue; // cant save without a rubric id
                }
                if (!$marking['statuscode']) {
                    $this->error->add_error('Marking', $marking['activity'], 'errsysaddmarking');
                    continue; // cant save without a statuscode
                }
                if (strpos($marking['xmltextref'], 'cid') === 0) {
                    // This is an error because at this point xmltextref should have been overwritten with the actual data
                    $this->error->add_error('Marking', $marking['markable'], 'nomarkingassignment');
                    continue;
                }
                $grading_info = null;
                if ($releaseMarking){
                    $grading_info = grade_get_grades($assignment->course, 'mod', 'assignment', $assignment->id, $marking['markable']);
                    if ($grading_info->items[0]->grades[$marking['markable']]->locked){
                        error_log('student grade item locked id ->'.$marking['markable']);
                    	$this->error->add_error('Marking', $marking['markable'], 'studentgradeitemlocked');
                        continue;
                    }
                    if ($grading_info->items[0]->grades[$marking['markable']]->overridden){
                        error_log('student grade item overridden id ->'.$marking['markable']);
                    	$this->error->add_error('Marking', $marking['markable'], 'studentgradeitemoverridden');
                        continue;
                    }
                }
                //Get the corresponding records for the foregn keys
                $rubric = $DB->get_record('lw_rubric',array('lwid'=>$marking['rubric'],'activity'=>$marking['activity']));
                $marker = $DB->get_record('user',array('id'=>$marking['marker']));
                $student = null;
                if ($this->markable == "student") {
                    $student = $DB->get_record('user',array('id'=>$marking['markable']));
                } elseif($this->markable == 'team') {
                    //check team existing 
                    $student = $DB->get_record('team', array('id'=>$marking['markable'], 'assignment'=>$marking['activity']));
                } else {
                    $this->error->add_error('Type', $marking['markable'], 'typeerror');
                }

                $statuscode = $DB->get_record('lw_marking_status', array('statuscode'=>$marking['statuscode']));


                $markercanupdate = false;

                if ($ismanager || (($this->uid == $marking['marker']) && $ismarker)) {
                    if (!$ismanager) {
                        if ($marking['timemodified'] != 0) {

                            foreach ($markingrecords as $markingrecord) {
                                if ($markingrecord->markable == $marking['markable']) {
                                    $markercanupdate = true;
                                    break;
                                }
                            }

                            if (!$markercanupdate) {
                                if ($this->markable == 'student') {
                                    $this->error->add_error('Student', $marking['markable'], 'nomarkingstudent');
                                } elseif ($this->markable == 'team') {
                                    $this->error->add_error('Team', $marking['markable'], 'nomarkingteam');
                                } else {
                                    $this->error->add_error('Type', $marking['markable'], 'typeerror');
                                }
                                 
                            }
                        }
                        else {
                            $this->error->add_error('Marking', $marking['activity'], 'noaddmarkingassignment');
                        }
                    }
                    else if ($marking['timemodified'] == 0) {
                        /*****  Do Insert *****/
                        // check for referential integrity before doing insert
                        if ($rubric && $marker && $student && $assignment && $statuscode) {
                            // Is the student or marker currently a course participant. If not, return an error
                            $isstudentacourseparticipant = $this ->is_course_participant($studentparticipants, $student, $activity);
                            $ismarkeracourseparticipant = false;
                             
                            foreach ($markerparticipants as $markerparticipant){
                                if ($markerparticipant['id'] == $marker->id){
                                    $ismarkeracourseparticipant = true;
                                    break;
                                }
                            }
                            if (!$isstudentacourseparticipant){
                                $this->error->add_error('Student', $marking['markable'], 'studentnotcourseparticipanterror');
                            }
                            if (!$ismarkeracourseparticipant){
                                $this->error->add_error('Marker', $marking['marker'], 'markernotcourseparticipanterror');
                            }
                            if (!$ismarkeracourseparticipant || !$isstudentacourseparticipant){
                                // Don't try to save this record
                                continue;
                            }
                             
                            $markupdate = new object();
                            $markupdate->marker =  clean_param($marking['marker'], PARAM_INT);
                            if ($this->markable == 'student') {
                                $markupdate->student = clean_param($marking['markable'], PARAM_INT);
                            } elseif ($this ->markable == 'team') {
                                $markupdate->team = clean_param($marking['markable'], PARAM_INT);
                            } else {
                                $this->error->add_error('Type', $marking['markable'], 'typeerror');
                            }
                            $markupdate->activity = clean_param($marking['activity'], PARAM_INT);
                            $markupdate->activitytype = clean_param($marking['activitytype'], PARAM_INT);
                            $markupdate->rubric = clean_param($marking['rubric'], PARAM_INT);
                            $markupdate->xmltext = $marking['xmltextref'];
                            $markupdate->statuscode = clean_param($marking['statuscode'], PARAM_ALPHA);
                            $markupdate->deleted = clean_param($marking['deleted'], PARAM_INT);
                            $markupdate->timemodified = time();

                            if (!$DB->insert_record($this->table,$markupdate,false)) {
                                $this->error->add_error('Marking', $marking['activity'], 'errsysaddmarking');
                            }
                            else {
                                //  Insert marking history
                                $rmarkinghistories = array();
                                if (isset($marking['markinghistory'])){
                                    foreach($marking['markinghistory'] as $markinghistory) {
                                        // check for referential integrity before doing insert
                                        $insertedMark = $DB->get_record($this->table,array('marker'=>$marking['marker'],$this->markable=>$marking['markable'],'activity'=>$marking['activity']));
                                        if ($insertedMark && $statuscode) {
                                            $markhistoryupdate = new object();
                                            $markhistoryupdate->lwid        = clean_param($markinghistory['lwid'], PARAM_INT);
                                            $markhistoryupdate->marker      = $markupdate->marker;
                                            if ($this->markable == 'student') {
                                                $markhistoryupdate->student     = $markupdate->student;
                                            } else {
                                                $markhistoryupdate->team        = $markupdate->team;
                                            }
                                            $markhistoryupdate->rubric      = $markupdate->rubric;
                                            $markhistoryupdate->activity    = $markupdate->activity;
                                            $markhistoryupdate->statuscode  = clean_param($markinghistory['statuscode'], PARAM_ALPHA);
                                            $markhistoryupdate->comment     = clean_param($markinghistory['comment'], PARAM_CLEAN);
                                            $markhistoryupdate->timemodified = $markupdate->timemodified;

                                            //  Insert the history record
                                            if (!$DB->insert_record($this->historytable,$markhistoryupdate,false)) {
                                            	error_log("insert history record fail: markable id ->".$marking['markable']." assignment id ->".$markhistoryupdate->activity." status code -> ".$markhistoryupdate->statuscode);
                                                $this->error->add_error('MarkingHistory', $markinghistory['lwid'], 'errsysaddmarkinghistory');
                                            }
                                            //  Add the history record to the response payload
                                            else {
                                                $rmarkinghistories[] = array( 'lwid' => $markhistoryupdate->lwid,
                                                                      'timemodified' => $markhistoryupdate->timemodified);
                                            }
                                        }
                                        else {
                                        	error_log("insert history record fail: markable id ->".$marking['markable']." assignment id ->".$marking['activity']." status code -> ".$markinghistory['statuscode']);
                                            $this->error->add_error('MarkingHistory', $markinghistory['lwid'], 'errsysaddmarkinghistory');
                                        }
                                    }
                                }

                                //  Check that it is a release
                                if ($releaseMarking) {
                                    $this->release_marking($marking, $grading_info);
                                }

                                $rmarkings[] = array('marker'       => $markupdate->marker,
                                                 'markable'      => clean_param($marking['markable'], PARAM_INT),
                                                 'activity'     => $markupdate->activity,
                                                 'rubric'       => $markupdate->rubric,
                                                 'activitytype' => $markupdate->activitytype,
                                                 'xmltext'      => '',
                                                 'statuscode'   => $markupdate->statuscode,
                                                 'deleted'      => $markupdate->deleted,
                                                 'timemodified' => $markupdate->timemodified,
                                                 'markinghistoryresponse' => $rmarkinghistories
                                );
                            }
                        }
                        else {
                            $this->error->add_error('Marking', $marking['activity'], 'errsysaddmarking');
                        }
                    }

                    if (($ismanager || $markercanupdate) && ($marking['timemodified'] != 0)) {
                        /*****  Do update *****/
                        // Check referential integrity and that record exists before update.
                        // Note that the get_record call does not include the rubric since only 3 fields can
                        // be added as parameters. However, only 1 rubric is allowed/assignment so this is ok.
                       // error_log('do update');
                        if ($rubric && $marker && $student && $assignment && $statuscode
                        && $markupdate = $DB->get_record($this->table,array('marker'=>$marking['marker'], $this->markable=>$marking['markable'],'activity'=>$marking['activity']))) {

                            // Is the student currently a course participant. If this is not a release, send
                            // an error message as a warning but continue with the update. If it's a release, abort with an error

                            $isstudentacourseparticipant = $this ->is_course_participant($studentparticipants, $student, $activity);
                            $ismarkeracourseparticipant = false;

                            foreach ($markerparticipants as $markerparticipant){
                                if ($markerparticipant['id'] == $marker->id){
                                    $ismarkeracourseparticipant = true;
                                    break;
                                }
                            }
                            if (!$isstudentacourseparticipant){
                                if ($releaseMarking){
                                    $this->error->add_error('Student', $marking['markable'], 'studentnotcourseparticipanterror');
                                }
                                else {
                                    if ($this->markable == 'team') {
                                        $this->error->add_error('Team', $marking['markable'], 'teamnotcourseparticipanterror');
                                    } else {
                                        $this->error->add_error('Student', $marking['markable'], 'studentnotcourseparticipantwarning');
                                    }
                                }

                            }
                            if (!$ismarkeracourseparticipant){
                                if ($releaseMarking){
                                    $this->error->add_error('Marker', $marking['marker'], 'markernotcourseparticipanterror');
                                }
                                else {
                                    $this->error->add_error('Marker', $marking['marker'], 'markernotcourseparticipantwarning');
                                }
                            }
                            if ($releaseMarking && (!$ismarkeracourseparticipant || !$isstudentacourseparticipant)){
                                // Don't try to save this record
                                continue;
                            } 
                           // error_log('pass student validating');
                            $markupdate->rubric = clean_param($marking['rubric'], PARAM_INT);
                            $markupdate->xmltext = $marking['xmltextref'];
                            $markupdate->statuscode = clean_param($marking['statuscode'], PARAM_ALPHA);
                            $markupdate->deleted = clean_param($marking['deleted'], PARAM_INT);
                            $markupdate->timemodified = time();
                           // error_log('update  marking');
                            if (!$DB->update_record($this->table,$markupdate)) {
                                error_log('update fail id ->'.$marking['markable']);
                                $this->error->add_error('Marking', $marking['activity'], 'errsysupdatemarking');
                            }
                            else {
                                //  Insert marking history
                               // error_log ('insert marking history');
                                $rmarkinghistories = array();
                                if (array_key_exists('markinghistory', $marking)) {
                                    foreach($marking['markinghistory'] as $markinghistory) {
                                        $markhistoryupdate = new object();
                                        $markhistoryupdate->lwid        = clean_param($markinghistory['lwid'], PARAM_INT);
                                        $markhistoryupdate->marker      = $markupdate->marker;
                                        if ($this->markable == 'student') {
                                            $markhistoryupdate->student     = $markupdate->student;
                                        } else {
                                            $markhistoryupdate->team     = $markupdate->team;
                                        }          
                                        $markhistoryupdate->rubric      = $markupdate->rubric;
                                        $markhistoryupdate->activity    = $markupdate->activity;
                                        $markhistoryupdate->statuscode  = clean_param($markinghistory['statuscode'], PARAM_ALPHA);
                                        $markhistoryupdate->comment     = clean_param($markinghistory['comment'], PARAM_CLEAN);
                                        $markhistoryupdate->timemodified = $markupdate->timemodified;

                                        //  Insert the history record
                                        if (!$DB->insert_record($this->historytable,$markhistoryupdate,true)) {
                                           // error_log('insert marking history fails');
                                            error_log("insert history record fail: markable id ->".$marking['markable']." assignment id ->".$markhistoryupdate->activity." status code -> ".$markhistoryupdate->statuscode);
                                            $this->error->add_error('MarkingHistory', $markinghistory['lwid'], 'errsysaddmarkinghistory');
                                        }
                                        //  Add the history record to the response payload
                                        else {
                                            $rmarkinghistories[] = array( 'lwid' => $markhistoryupdate->lwid,
                                                                      'timemodified' => $markhistoryupdate->timemodified
                                            );
                                        }
                                    }
                                }
                                //  Check that it is a release
                                if ($releaseMarking) {
                                    $this->release_marking($marking, $grading_info);
                                }

                                $rmarkings[] = array(
                                        'marker'       => $markupdate->marker,
                                        'markable'     => $marking['markable'],
                                        'activity'     => $markupdate->activity,
                                        'rubric'       => $markupdate->rubric,
                                        'activitytype' => $markupdate->activitytype,
                                        'xmltext'      => '',
                                        'statuscode'   => $markupdate->statuscode,
                                        'deleted'      => $markupdate->deleted,
                                        'timemodified' => $markupdate->timemodified,
                                        'markinghistoryresponse' => $rmarkinghistories
                                );
                            }
                        }
                        else {
                            $this->error->add_error('Marking', $marking['activity'], 'nomarkingfound');
                        }
                    }
                }
                else {
                    $this->error->add_error('Marking', $marking['activity'], 'nomarkingassignment');
                }
            }
        }

        return array('markingresponse'=>$rmarkings);
    }

    private function is_course_participant($studentparticipants, $markable, $activity) {
        if ($this->markable == 'student') {
            foreach ($studentparticipants as $studentparticipant){
                if ($studentparticipant['id'] == $markable->id){
                    return true;
                }
            }
        } elseif ($this->markable =='team') {
            //if this team has one of valid team member , return ture.
            $members = get_members_from_team ($markable->id);
            if ($members && is_team_in_assignment($markable->id, $activity)) {
                foreach ($members as $member) {
                    foreach ($studentparticipants as $studentparticipant){
                        if ($studentparticipant['id'] == $member->student) {
                           // error_log('team has a valid member: '.$member->student);
                            return true;
                        }
                    }
                }
            }
            return  false;
        }
        return false;
    }

    /**
     * Release a making sheet in to the database
     * @param  array     $marking An array with making details to be saved in to the database
     * @param  Object    $grading_info An object containing the grading information for the student
     * @return void
     */
    private function release_marking($marking, $grading_info) {
        global $CFG, $LW_CFG, $DB;
        require_once("$CFG->libdir/gradelib.php");
        require_once("$CFG->dirroot/lib/uploadlib.php");
        require_once("$CFG->dirroot/mod/assignment/lib.php");

        $cm = get_coursemodule_from_instance('assignment', $marking['activity']);
        $assignment = $DB->get_record('assignment', array('id'=>$cm->instance));
        $course = $DB->get_record('course', array('id'=>$assignment->course));

        if ($this->is_allowed_assignment_type($assignment)) {
            /*****  Update the Grade *****/
            //  Get the assignment object
            require_once("$CFG->dirroot/mod/assignment/type/$assignment->assignmenttype/assignment.class.php");
            $assignmentclass = "assignment_$assignment->assignmenttype";
            $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

            //  We are not doing outcomes, but if we do later on, here is where we will need to updated
            //  store outcomes if needed
            //  $assignmentinstance->process_outcomes($marking['student']);

            $submission = $assignmentinstance->get_submission($marking['markable'], true);  // Get or make one

            if (!$grading_info->items[0]->grades[$marking['markable']]->locked && !$grading_info->items[0]->grades[$marking['markable']]->overridden) {
                if ($assignment->assignmenttype != LW_Common::FEEDBACK_TYPE){
                    if ($LW_CFG->isDecimalPointMarkingEnabled){
                        $submission->grade = clean_param($marking['grade'], PARAM_NUMBER);
                    } else {
                        $submission->grade = clean_param($marking['grade'], PARAM_INT);
                        if ($submission->grade - $marking['grade'] != 0){
                            $this->error->add_error('Marking', $marking['markable'], 'rounddownmarkwarning'); 
                        }
                    }
                }
                $submission->submissioncomment  = clean_param($marking['submissioncomment'], PARAM_CLEAN);
                $submission->teacher            = clean_param($marking['marker'], PARAM_INT);
                $submission->timemarked         = time();

                unset($submission->data1);  // Don't need to update this.
                unset($submission->data2);  // Don't need to update this.

                if (! $DB->update_record('assignment_submissions', $submission)) {
                	error_log('update submission fail student id ->'.$marking['markable']);
                    $this->error->add_error('Marking', $marking['markable'], 'noupdatemarkingrelease');
                    return false;
                }

                // triger grade event
                $assignmentinstance->update_grade($submission);

                add_to_log($assignmentinstance->course->id, 'assignment', 'update grades','submissions.php?id='.$assignmentinstance->assignment->id.'&user='.$marking['markable'], $marking['markable'], $assignmentinstance->cm->id);


                /*****  Upload the file *****/
                if (isset($marking['annotated_records'])) {
                    $destination = $CFG->dataroot.'/'.$assignmentinstance->file_area_name($marking['markable']).'/responses';
                    return $this->upload_annotated_files($marking['annotated_records'], $destination,  $assignmentinstance,  $marking['markable']);
                }
            }
        } else {
        	error_log('assignment is not allowed type assignmentId->'.$assignment->id);
        }
        return true;
    }

    public function release_team_user_marking($marking, $user, $releaseteam = false) {
        global $CFG, $LW_CFG, $DB;

        require_once("$CFG->libdir/gradelib.php");
        require_once("$CFG->dirroot/lib/uploadlib.php");
        require_once("$CFG->dirroot/mod/assignment/lib.php");

        $cm = get_coursemodule_from_instance('assignment', $marking['activity']);
        $assignment = $DB->get_record('assignment', array('id'=>$cm->instance));
        $course = $DB->get_record('course', array('id', $assignment->course));
        $context = get_context_instance(CONTEXT_COURSE, $assignment->course);
          
        if (isset($user)) {
            $sql = get_student_participant_sql($user->student, $context->id);
            if (!$participant = $DB->get_records_sql($sql)) {
                $this->error->add_error('TeamMarking', $user->student, 'studentnotcourseparticipanterror');
                return false;
            }    
        }
        
        if ($assignment->assignmenttype == 'team') {
            /*****  Update the Grade *****/
            //  Get the assignment object
            require_once("$CFG->dirroot/mod/assignment/type/$assignment->assignmenttype/assignment.class.php");
            $assignmentclass = "assignment_$assignment->assignmenttype";
            $assignmentinstance = new $assignmentclass($cm->id, $assignment, $cm, $course);

            //if release team marking is true, upload the annotated files and return.
            if ($releaseteam) {
                /*****  Upload team annotated files *****/
                if (isset($marking['annotated_records'])) {
                    $destination = $assignmentinstance->team_file_area($marking['markable']).'/responses';
                    return $this->upload_annotated_files($marking['annotated_records'], $destination , $assignmentinstance, $marking['markable']);
                }
                return true;
            }

            $grading_info = grade_get_grades($assignmentinstance->course->id, 'mod', 'assignment', $assignmentinstance->assignment->id, $user->student);

            //  We are not doing outcomes, but if we do later on, here is where we will need to updated
            //  store outcomes if needed
            //  $assignmentinstance->process_outcomes($marking['student']);

            $submission = $assignmentinstance->get_submission($user->student, true);  // Get or make one

            if (!$grading_info->items[0]->grades[$user->student]->locked && !$grading_info->items[0]->grades[$user->student]->overridden) {
                if ($LW_CFG->isDecimalPointMarkingEnabled){
                    $submission->grade = clean_param($marking['grade'], PARAM_NUMBER);
                } else {
                    $submission->grade = clean_param($marking['grade'], PARAM_INT);
                    if ($submission->grade - $marking['grade'] != 0){
                        $this->error->add_error('Marking', $marking['markable'], 'rounddownmarkwarning'); 
                    }
                }
                $submission->grade              = clean_param($marking['grade'], PARAM_INT);
                $submission->submissioncomment  = clean_param($marking['submissioncomment'], PARAM_CLEAN);
                $submission->teacher            = clean_param($marking['marker'], PARAM_INT);
                $submission->timemarked         = time();
                 
                unset($submission->data1);  // Don't need to update this.
                unset($submission->data2);  // Don't need to update this.

                if (! $DB->update_record('assignment_submissions', $submission)) {
                    $this->error->add_error('Marking', $user->student, 'noupdatemarkingrelease');
                    return false;
                }

                // triger grade event
                $assignmentinstance->update_grade($submission);

                add_to_log($assignmentinstance->course->id, 'assignment', 'update grades','submissions.php?id='.$assignmentinstance->assignment->id.'&user='.$user->student, $marking['markable'], $assignmentinstance->cm->id);


                /*****  Upload the file *****/
                if (isset($marking['annotated_records'])) {
                    $destination = $CFG->dataroot.'/'.$assignmentinstance->file_area_name($user->student).'/responses';
                    return $this->upload_annotated_files($marking['annotated_records'], $destination , $assignmentinstance, $user->student);
                }
            }
        }
        return true;
    }

    function upload_annotated_files($annotatedRecords, $destination,  $assignmentinstance,  $ownerid) {
        global $CFG;
        require_once("$CFG->dirroot/lib/uploadlib.php");
       // error_log('start to upload file');
        foreach($annotatedRecords as $annotatedRecord) {

            //  Create the file
            $tmpfile = $CFG->dataroot.'/'.md5(uniqid(time())).'_'.$annotatedRecord['filename'];

            $file = fopen($tmpfile, "w");
            if ($file) {
                $status = fwrite ($file, $this->helper->sanitise_for_msoffice2007($tmpfile, $annotatedRecord['data']));
                fclose($file);
            }
            else {
                $this->error->add_error('Marking', $ownerid, 'nofileopenmarkingrelease');
                return false;
            }

            //  Upload the file as a response/feedback file
            check_dir_exists($destination, true, true);
           // error_log('file destination :'.$destination);
            $um = new upload_manager('newfile',false,true,$assignmentinstance->course,false,0,true);

            $um->files['newfile'] = array( 	'name'      => $annotatedRecord['filename'],
                                'type'      => substr($annotatedRecord['contenttype'],0,strpos($annotatedRecord['contenttype'],';')),
                                'tmp_name'  => $tmpfile,
                                'error'     => 0,
                                'size'      => filesize($tmpfile));

            $um->files['newfile']['uploadlog'] = '';
            $newname = clean_filename($um->files['newfile']['name']);

            if ($newname != $um->files['newfile']['name']) {
                $a->oldname = $um->files['newfile']['name'];
                $a->newname = $newname;
                $um->files['newfile']['uploadlog'] .= get_string('uploadrenamedchars','moodle', $a);
            }

            $um->files['newfile']['name'] = $newname;
            $um->files['newfile']['clear'] = true;
            $um->config->somethingtosave = true;
            $um->status = true;

            if ($um->config->handlecollisions) {
                $um->handle_filename_collision($destination, $um->files['newfile']);
            }
            if (rename($um->files['newfile']['tmp_name'], $destination.'/'.$um->files['newfile']['name'])) {
                chmod($destination .'/'. $um->files['newfile']['name'], $CFG->directorypermissions);
                $um->files['newfile']['fullpath'] = $destination.'/'.$um->files['newfile']['name'];
                $um->files['newfile']['uploadlog'] .= "\n".get_string('uploadedfile');
                $um->files['newfile']['saved'] = true;
            }
            else {
                $this->error->add_error('Marking', $ownerid, 'nofilemarkingrelease');
                return false;
            }

        }
        return true;
    }

    /**
     * Retrieve marking history for an array of marking
     * primary keys (markerid, studentid, rubricid, and activityid)
     * @param   array Array of arrays containing markerid, markableid, rubricid, and activityid
     * @return  array  of associative arrays containing marking history data
     */
    public function get_marking_history($historyids) {
        global $CFG, $DB;
        $activitytype = 1;  // assignment
        $wherein = '';
        $rmarkinghistories = array('markinghistory'=>array());

        foreach ($historyids as $id) {
            $sql = "SELECT id,lwid,statuscode,timemodified,comment".
                " FROM {".$this->historytable."} ".
                " WHERE marker = ".$id['marker'].
                " AND   $this->markable = ".$id['markable'].
                " AND   rubric = ".$id['rubric'].
                " AND   activity = ".$id['activity'].
                " ORDER by timemodified";
            if ($items = $DB->get_records_sql($sql)) {
                foreach ($items as $item) {
                    $rmarkinghistories['markinghistory'][] = array(
                        'lwid'         => $item->lwid,
                        'statuscode'      => $item->statuscode,
                        'comment'         => $item->comment,
                        'timemodified'    => $item->timemodified,
                        'marker'          => $id['marker'],
                        'markable'        => $id['markable'],
                        'rubric'          => $id['rubric'],
                        'activity'        => $id['activity']
                    );
                }
            }
        }
        return $rmarkinghistories;
    }

    /**
     * Retrieve a list of rubrics from a list of activity ids
     *
     * @param   array|int $activities    An array or id of activities to get marking records for
     * @param   int       $timemodified  Modified time
     * @return  array     Array of activity/marking objects
     */
    public function get_rubric($assignmentid,$timemodified=0) {
        global $CFG, $DB;
        $activitytype = 1;  // assignment
        $wherein = '';
        $ractivities = array();
        $activityids = array();

        if (is_array($assignmentid)) {
            $activityids = $assignmentid;
        } else if (is_numeric($assignmentid) && ($assignmentid>0)) {
            $activityids[] = $assignmentid;
        } else {
            return null;
        }

        $wheretime = ($timemodified > 0) ? " AND timemodified > $timemodified " : '';
        if (count($activityids)) {
            $wherein = ' AND activity IN ('. implode(',',$activityids) .')';
        }
        $sql = "SELECT id,lwid,activity,activitytype,xmltext,complete,deleted,timemodified".
               " FROM {lw_rubric} ".
               " WHERE activitytype = '$activitytype' ".
        $wherein.$wheretime.
               " ORDER BY activity,lwid";


        if ($items = $DB->get_records_sql($sql)) {
            $activityid = '';
            $rubrics = array();

            foreach ($items as $item) {
                if (($item->activity != $activityid) && ($activityid != '')) {
                    $ractivities[] = array('id'=>$activityid, 'rubric'=>$rubrics);
                    $rubrics = array();
                }
                $xmlTextRef = "analyticrubric-" . $item->lwid . "-" . $item->activity;
                $activityid = $item->activity;
                $rubrics[] = array(
                        'id'            => $item->lwid,
                        'activity'      => $item->activity,
                        'activitytype'  => $item->activitytype,
                        'xmltextref'    => $xmlTextRef,
                        'complete'      => $item->complete,
                        'deleted'       => $item->deleted,
                        'timemodified'  => $item->timemodified,
                        'xmltext'       => $item->xmltext
                );
            }

            // save the final record
            $ractivities[] = array('id'=>$activityid, 'rubric'=>$rubrics);
        }

        return $ractivities;
    }
    
    /**
     * 
     * @param $assignmentids
     * @param $timemodified
     * @return int the number of marking records modified since $timemodified
     */
    public function getModifiedMarkingCount($assignmentids, $timemodified){
        global $CFG, $DB;
        $sql = "SELECT COUNT(*) AS count from {".$this->table."}".
               " WHERE timemodified > $timemodified".
               " AND activity IN (".implode(',', $assignmentids).")";
        $item = $DB->get_record_sql($sql);
        return $item->count;    
    }

    /**
     * Gets the marking records for this marker for an activity id
     * @param   int      $activity    Activity id to get marking records for
     * @param   int      $timemodified  Modified time
     * @return  array    Array of marking records
     */
    public function get_marking($activityid, $timemodified=0, $allstudents) {
        global $CFG, $DB;

        $activitytype = 1;  // assignment
        $rmarkings = array();

        if (is_null($activityid)) {
            $this->error->add_error('Marking', 0, 'noidsmarking');
            return $rmarkings;
        }

        $assignment = $DB->get_record('assignment', array('id'=>$activityid));
        if (!$assignment){
            $this->error->add_error('Marking', $activityid, 'noassignmentfound');
            return $rmarkings;
        }

        $context = get_context_instance(CONTEXT_COURSE, $assignment->course);
        $ismanager = has_capability(LW_Common::CAP_MANAGELWMARKERS , $context, $this->uid, false);
        $ismarker = has_capability(LW_Common::CAP_MARKLWSUBMISSIONS , $context, $this->uid, false);

        //  Is the user allowed to do anything?
        if ($ismanager || $ismarker) {
            $query = '';
            $submittingstudentsql = '';
            $wheretime = ($timemodified > 0) ? " AND timemodified > $timemodified " : '';

            $sql = "SELECT id,marker,$this->markable as markable,rubric,activity,activitytype,xmltext,deleted,statuscode,timemodified ".
                " FROM {".$this->table."} WHERE activitytype = '$activitytype' ";

            $where = " AND activity = '$activityid'";
            
            if (($allstudents == 0) && ($this->type != LW_Common::TEAM_MARKING)){
                $submittingstudentsql = " AND $this->markable ".$this->helper->get_submitting_assignment_students_only_sql($activityid); 
            }

            if ($ismarker && !$ismanager) {
                $where .= " AND marker = '{$this->uid}'";
            }

            $query = $sql.$wheretime.$submittingstudentsql.$where.' ORDER BY activity,'.$this->markable;
            if ($items = $DB->get_records_sql($query)) {
                foreach ($items as $item) {
                    $xmlTextRef = "marking-".$item->marker."-".$item->markable."-".$item->activity."-".$item->rubric;
                    $rmarkings['marking'][] = array(
                            'marker'       => $item->marker,
                            'markable'      => $item->markable,
                            'rubric'       => $item->rubric,
                            'activity'     => $item->activity,
                            'activitytype' => $item->activitytype,
                            'xmltextref'   => $xmlTextRef,
                            'deleted'      => $item->deleted,
                            'statuscode'   => $item->statuscode,
                            'timemodified' => $item->timemodified,
                            'xmltext'      => $item->xmltext
                    );
                }
            }
        }
        else {
            $this->error->add_error('Marking', $activityid, 'nomarkingassignment');
        }

        return $rmarkings;
    }
     
}

?>