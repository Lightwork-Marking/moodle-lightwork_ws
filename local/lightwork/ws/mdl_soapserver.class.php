<?php // $Id$

/**
 * Base class for SOAP protocol-specific server layer.
 *
 * Original class file authored by OpenKnowlegdeTechnologies, subsequently
 * extended by members of the Lightwork project team from Waikato University
 *
 * @package LW (Lightwork)
 * @author Dean Stringer <deans@waikato.ac.nz>
 * @author David Vega Morales <davidvm@waikato.ac.nz>
 * @author Yoke Chui <yokec@waikato.ac.nz>
 * @author Justin Filip (original author) <jfilip@oktech.ca>
 */


require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('server.class.php');
require_once('lib/nusoap.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_error.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_feedback.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_marker.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_rubric.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_pkey.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_document.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_team.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_common.php');
require_once(dirname(dirname(__FILE__)).'/lib/lw_sql.php');

define('MOODLE_WS_NAMESPACE', 'http://www.massey.ac.nz/lightwork/ws');

if (DEBUG) ini_set('soap.wsdl_cache_enabled', '0');


/**
 * The SOAP server class.
 */
class mdl_soapserver extends server {

    public $fault;
    public $session;
    const UNAUTHORISED_MESSAGE ='Unauthorised access, user does not have Lightwork Capability';
    const SERVICE_VERSION = '3.1.1';

    /**
     * Constructor method.
     *
     * @param none
     * @return none
     */
    function mdl_soapserver() {
        /// Necessary for processing any DB upgrades.
        parent::server();

        if (DEBUG) $this->debug_output('    Version: ' . $this->version);
        if (DEBUG) $this->debug_output('    Session Timeout: ' . $this->sessiontimeout);
    }

    /**
     * Initializes a new server and calls the dispatch function upon an
     * incoming client request.
     *
     * @uses $CFG
     * @param mixed $httpdata HTTP_REQUEST data.
     * @return none
     */
    function main($httpdata) {
        global $CFG;

        if (DEBUG) $debug = 1;

        /// Start new SOAP server.
        if (strstr($_SERVER['QUERY_STRING'], 'wsdl') === false) {
            /* NEW */
            global $debug;
            $debug = 1;
            /********/
            $sserver = new soap_server($CFG->wwwroot . '/local/lightwork/ws/wsdl.php');

            /// Start a new SOAP server and register all the data types and service methods
            /// for dynamic WSDL creation.
        }

        /// Use the request to (try to) invoke the service.
        $sserver->service($httpdata);
    }

    /**  =====  OVERRIDE SERVER METHODS NEEDING SOAP-SPECIFIC HANDLING  ======  **/

    /**
     * Validate the client using the session key
     *
     * @param string $sesskey The client session key.
     *
     */
    function validate_session($sesskey) {
        $returnvalue = true;

        $this->session = get_record('webservices_sessions', 'sessionkey', $sesskey, 'verified', '1');

        //  Check that is a valid session
        if (!$this->session) {
            $this->fault = new soap_fault('Client', '', 'Invalid client connection.');
            $returnvalue = false;
        }
        //  Check that the session hasn't timeout
        else if ((time() - $this->session->sessionbegin) > $this->sessiontimeout) {
            parent::logout($this->session->id, $this->session->sessionkey);
            $this->fault = new soap_fault('Client', '', 'Session (' . $this->session->sessionkey . ') expired');
            $returnvalue = false;
        }
        //  If all ok, make sure that we update last access time
        else {
            set_field('webservices_sessions','sessionbegin',time(),'id',$this->session->id);
        }

        return $returnvalue;
    }

    /**
     * An overload of the parent function logout to accept only a session key
     * as a paramenter (we are not using the client id)
     *
     * @param string $sesskey      The client session key.
     * @return boolean True if successfully logged out, false otherwise.
     *
     */
    function logout($sesskey) {

        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }

        return parent::logout($this->session->id, $this->session->sessionkey);
    }
    
    /** Get a list of feedback submission records for a given assignment id
     * @param string $sesskey      The client session key.
     * @param int    $id           assignment id
     * @param int    $timemodified Modified time
     * @return array $feedbacksubmissions    Associative array containing feedbacksubmissions and/or errors arrays
     */
    function getFeedbackSubmissions($sesskey, $id, $timemodified) {
        global $NUSOAP_SERVER, $CFG;
        $feedbacksubmissions = array();
        $timemodified = (empty($timemodified) ? 0 : $timemodified);
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        } else {
            $marker = new LW_Feedback($this->session->userid);
            //lightwork capability checks are done in the get_prereading method.
            $feedbacksubmissions['feedbackSubmissions'] = $marker->get_feedback_submissions($id, $timemodified);
            $feedbacksubmissions['errors'] = $marker->error->get_errors();
        }
        return $feedbacksubmissions;
    }
    
    /**
     * @param $sesskey
     * @param $id assignment id
     * @param $timemodified
     * @return array $demographics Associative array containing demographics and/or errors arrays
     * for students who have submitted feedback for this feedback assignment type
     */
    function getDemographics($sesskey, $useridswithtimemodified, $assignmentid){
        $demographics = array();
        if (!$this->validate_session($sesskey)) {
            return $this->fault; 
        } else {
            $marker = new LW_Feedback($this->session->userid);
            $demographics['demographics'] = $marker->get_demographics($useridswithtimemodified, $assignmentid);
            $demographics['errors'] = $marker->error->get_errors(); 
        }
        return $demographics;
    }
    
    /**
     * @param $sesskey
     * @param $ids assignment ids for which marking should be checked
     * @param $type Marking, Team or Feedback
     * @param $timemodified
     * @return int the number of marking records modified since $timemodified
     */
    function getModifiedMarkingCount($sesskey, $ids, $type, $timemodified){
        $assignmentids = array();
        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }
        if (empty($timemodified)){
            return new soap_fault('Client', '', 'getModifiedMarkingCount expects a timemodified value'); 
        }
        if (is_array($ids['assignmentid'])) {
            $assignmentids = $ids['assignmentid'];
        } else if($ids['assignmentid']) {
            $assignmentids = array($ids['assignmentid']);
        } else {
            return new soap_fault('Client', '', 'getModifiedMarkingCount expects at less one assignment id');
        }
        $marker = new LW_Marker($this->session->userid, $type);
        return $marker->getModifiedMarkingCount($assignmentids, $timemodified); 
    }

    /** Get a list of marking records for a given activity id
     * @param string $sesskey      The client session key.
     * @param int    $id           activity id
     * @param int    $type         1 or 2 (student or team marking)
     * @param int    $timemodified Modified time
     * @param int    $allstudents if 1(true) then marking for all students is included. If 0(false) only
     * marking for students who have submitted work (they have a record in mdl_assignment_submissions 
     * for an assignment belonging to the course) is included.
     * @return array $rmarkings    Associative array containing markings and/or errors arrays
     */
    function getMarking($sesskey, $id, $type, $timemodified, $allstudents) {
        global $NUSOAP_SERVER, $CFG;

        $rmarkings = array();
        $timemodified = (empty($timemodified) ? 0 : $timemodified);

        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        } else {
            $marker = new LW_Marker($this->session->userid, $type);
            //lightwork capability checks are done in the get_marking method.
            $rmarkings['markings'] = $marker->get_marking($id, $timemodified, $allstudents);
            $rmarkings['errors'] = $marker->error->get_errors();
            if (isset($rmarkings['markings']['marking'])){
                foreach ($rmarkings['markings']['marking'] as $marking) {
                    $NUSOAP_SERVER->addAttachment($marking['xmltext'],$marking['xmltextref'].'-file','text/xml; charset=UTF-8',$marking['xmltextref']);
                }
            }
        }

        return $rmarkings;
    }
    
    /**
     * Get a list of marking histories with the specifed array of ids consisting of
     * a markerid, studentid, rubricid and activityid.
     * @param string $sesskey The client session key.
     * @param int    $type 1 or 2 (student or team marking)
     * @param array  $ids Array of arrays containing markerid, markableid, rubricid, and activityid
     */
    function getMarkingHistory($sesskey, $type, $ids) {
        global $NUSOAP_SERVER, $CFG;

        $rmarkinghistories = array();
        $historyids = array();
        
        if (isset($ids['markingKey'][0])){
          $historyids = $ids['markingKey']; 
        } else {
          $historyids = array($ids['markingKey']);
        }

        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        } else {
            $marker = new LW_Marker($this->session->userid, $type);               
            $rmarkinghistories = $marker->get_marking_history($historyids);
        }
        return $rmarkinghistories;
    }
    
    /**
     * Get a list of courses for a given course id(s) or user
     *
     * @param string $sesskey      The client session key.
     * @paran array  $ids          List of course id(s)
     * @param int    $timemodified Modified time
     *
     */
    function getCourses($sesskey, $timemodified) {
        global $USER;
        $ids = array('courseid'=>'');
        $rcourses = array();
        $errors = array();

        $timemodified = (empty($timemodified) ? 0 : $timemodified);
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        } else {
            $marker = new LW_Marker($this->session->userid, LW_Common::STUDENT_MARKING);
            //The lightwork user capability has been check by Lw_marker.
            $rcourses['courses'] = $marker->courses_modified_since($ids['courseid'],$timemodified);
            $errors = $marker->error->get_errors();
        }

        $rcourses['errors'] = $errors;
        return $rcourses;
    }

    /**
     * Get a list of courses for which a given user has the marker or marking manager capability
     *
     * @param string $sesskey The client session key.
     * @param int    $timemodified Modified time
     *
     */
    function getAllCourses($sesskey, $timemodified) {
        return $this->getCourses($sesskey, array('courseid'=>''), $timemodified);
    }

    /**
     * Get a list of participants for a given course id or ids
     *
     * @param string $sesskey      The client session key.
     * @param array  $ids      Of the courses to fetch students for
     * @param int    $timemodified Modified time to filter participants by
     * @param int    $allstudents if 1(true) then all students are included. If 0(false) only
     * students who have submitted work (they have a record in mdl_assignment_submissions for an
     * assignment belonging to the course) are included.
     */
    function getCourseParticipants($sesskey, $ids, $timemodified, $allstudents) {
        $error = new LW_Error();
        $rcourses = array();
        $requestedcourseids = array();
        $errors = array();
        $timemodified = (empty($timemodified) ? 0 : $timemodified);

        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }

        //  Initialize Marker and get all courses for which the marker is authorised
        $marker = new LW_Marker($this->session->userid, LW_Common::STUDENT_MARKING);

        //  put requested ids in one array -- easy to search
        if (is_array($ids['courseid'])) {
            $requestedcourseids = $ids['courseid'];
        }
        else if ($ids['courseid']) {
            $requestedcourseids = array($ids['courseid']);
        }
        
        // Check that the marker is still authorised for the course ids they are requesting
        foreach ($requestedcourseids as $requestedcourseid){
            if  ($this->is_lightworkuser_by_course($requestedcourseid, $this->session->userid)){
                $participants = array();
                if ($allstudents == 0){
                    $students = $marker->course_submitting_students_since($requestedcourseid,$timemodified);
                    $markers = $marker->course_participants_since($requestedcourseid,
                                                                       $timemodified, 
                                                                       LW_Common::CAP_MARKLWSUBMISSIONS);
                    $participants = array_merge($students, $markers);
                } else {
                    $participants = $marker->course_participants_since($requestedcourseid,$timemodified);
                }
                
                //  populate the return payload if we found something
                if (count($participants)) {
                    $rcourses['courseParticipants']['courseParticipant'][] = array('id'=>$requestedcourseid, 'user'=>$participants);
                }
                else {
                    $error->add_error('Course', $requestedcourseid, 'nostudentforcourse');
                }    
            } else {
                $error->add_error('Course', $requestedcourseid, 'unauthorisedaccess');
            }  
        }
        $errors = $error->get_errors();
        $rcourses['errors'] = $errors;
        return $rcourses;
    }


    /**
     * This method returns a list of submission by assignment
     *
     * @param string $sesskey      The client session key.
     * @param array    $ids        Array of assignment ids
     * @param int    $timemodified Modified time to filter submissions by
     */
    function getSubmissions($sesskey, $ids, $timemodified, $allstudents) {
        $rsubmissions = array();
        $assignmentids = array();
        $timemodified = (empty($timemodified) ? 0 : $timemodified);
        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }
        //  put requested ids in one array -- easy to search
        if (is_array($ids['assignmentid'])) {
            $assignmentids = $ids['assignmentid'];
        } else if($ids['assignmentid']) {
            $assignmentids = array($ids['assignmentid']);
        } else {
            return new soap_fault('Client', '', 'The getSubmission call expects at less one assignment id');
        }

        $marker = new LW_Marker($this->session->userid, LW_Common::STUDENT_MARKING);
        $rsubmissions['assignments'] = $marker->assignment_submissions_since($assignmentids, $timemodified, false, $allstudents);
        $rsubmissions['errors'] = $marker->error->get_errors();

        return $rsubmissions;
    }


    /**
     * This method returns submission files records plus zip file attachments containing
     * the submission files.
     * @param string $sesskey The client session key
     * @param string $assignmentid The assignment to which the submission ids belong
     * @param array  $ids Array with a key 'submissionid' that may have as its value an Array
     *                    of submission ids or an int representing a single submission id
     */
    function getSubmissionFiles($sesskey, $assignmentid, $ids) {
        global $NUSOAP_SERVER, $CFG;

        require_once($CFG->libdir.'/filelib.php');

        $rsubmissions = array();

        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }

        //check that the user is authorised to get the submission files
        $assignment = get_record("assignment", "id", $assignmentid);
        if (!$this -> is_lightworkuser_by_assignment($assignment, $this->session->userid)) {
            return new soap_fault('Client', '', self::UNAUTHORISED_MESSAGE);
        }
        $marker;
        if ($assignment->assignmenttype == 'team'){
            $marker = new LW_Marker($this->session->userid, LW_Common::TEAM_MARKING);   
        } else {
            $marker = new LW_Marker($this->session->userid, LW_Common::STUDENT_MARKING); 
        }
        $rsubmissions = $marker->assignment_submission_files($assignmentid, $ids['submissionid']);
        $rsubmissions['errors'] = $marker->error->get_errors();

        if (count($rsubmissions['submissionfiles']['submissionfile'])) {
            foreach ($rsubmissions['submissionfiles']['submissionfile'] as $submissionfile) {
                $this->zip_submission_file($submissionfile);
            }
        }
        return $rsubmissions;
    }

    private function zip_submission_file($submissionfile) {
        global $NUSOAP_SERVER, $CFG;
        $tmpzipfile = $CFG->dataroot .'/'. md5(uniqid(time())) .'.zip';
        zip_files($submissionfile['files'], $tmpzipfile);
        $data = '';
        if ($fd = fopen($tmpzipfile, 'rb')) {
            $data = fread($fd, filesize($tmpzipfile));
            fclose($fd);
        }
        $NUSOAP_SERVER->addAttachment($data,$tmpzipfile,$submissionfile['mime'], $submissionfile['fileref']);

        //  Delete the tmp zip file
        unlink($tmpzipfile);
    }

    /**
     * This method returns a list of assignment document files.
     * @param string $sesskey       The client session key
     * @param int    $course        The course id
     * @param int    $assignment    The assignment id
     * @param array  $filenames     Array of filename to be downloaded
     */
    function getAssignmentDocuments($sesskey, $course, $assignment, $filenames) {
        global $NUSOAP_SERVER, $CFG;

        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }

        if (!$this->is_lightworkuser_by_course($course, $this->session->userid)) {
            return new soap_fault('Client', '', self::UNAUTHORISED_MESSAGE);
        }

        $doc = new LW_document($course, $assignment);
        $result = array('files'=>array('fileInfo'=>array()), 'errors'=>array('error'=>array()));
        
        // If only 1 value of filename is sent by client, $filenames is simple array of string
        // If more then 1 values are sent by client, $filenames is hash where $filenames['filename'] contains the values
        if (is_array($filenames['filename'])) {
            $names = $filenames['filename'];
        } else {
            $names = $filenames;
        }
        foreach ($names as $filename) {
            $file = $doc->get_assignment_file($filename);
            if (!isset($file['error'])) {
                $NUSOAP_SERVER->addAttachment($file['data'], $file['filename'], 'application/octet-stream', $file['fileref']);
                unset($file['data']);
                $result['files']['fileInfo'][] = $file;
            } else {
                $result['errors']['error'][] = $file['error'];
            }
        }
        return $result;
    }

    /**
     * This method returns a list of rubrics by assignment
     *
     * @param string $sesskey          The client session key.
     * @param array  $ids              Array of assignment ids
     * @param int    $lasttimemodified Modified time to filter submissions by
     */
    function getMarkingRubrics($sesskey, $ids, $timemodified) {
        global $NUSOAP_SERVER, $CFG;

        $rrubrics = array();
        $rubric = array();
        $timemodified = (empty($timemodified) ? 0 : $timemodified);

        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }
        //check the lightwork user capability.
        $marker = new LW_Marker($this->session->userid, LW_Common::STUDENT_MARKING);
        if (!$this->islightworkuser($marker->get_courses(), $this->session->userid)) {
            return new soap_fault('Client', '', self::UNAUTHORISED_MESSAGE);
        }
        //  put requested ids in one array -- easy to search
        if (is_array($ids['assignmentid'])) {
            $assignmentids = $ids['assignmentid'];
        }
        else if ($ids['assignmentid']) {
            $assignmentids = array($ids['assignmentid']);
        }
        else {
            return new soap_fault('Client', '', 'The getMarkingRubrics call expects at less one assignment id');
        }

        $rrubrics['assignments']['assignment'] = $marker->get_rubric($assignmentids, $timemodified);
        $rrubrics['errors'] = $marker->error->get_errors();

        foreach ($rrubrics['assignments']['assignment'] as $assignment) {
            foreach ($assignment['rubric'] as $rubric) {
                $NUSOAP_SERVER->addAttachment($rubric['xmltext'],$rubric['xmltextref'].'-file','text/xml; charset=UTF-8',$rubric['xmltextref']);
            }
        }

        return $rrubrics;
    }

    /**
     * Insert/Update a list of rubrics
     * @param array  $saveMarkingRubrics    Array of rubric(s)
     * @param string $sesskey             The client session key.
     */
    function saveMarkingRubrics($saveMarkingRubrics, $sesskey) {
        global $NUSOAP_SERVER, $CFG;

        $rrubrics = array('markingrubricresponses'=>array('markingrubricresponse'));
        $markingrubrics = array('markingrubric'=>array());
        $rubrics = array();
        $timemodified = (empty($timemodified) ? 0 : $timemodified);
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        } else {
            $rubric = new LW_Rubric();

            if (isset($saveMarkingRubrics['markingRubric']['id'])) {
                $markingrubrics['markingrubric'][] = $saveMarkingRubrics['markingRubric'];
            }
            else {
                $markingrubrics = $saveMarkingRubrics;
            }

            $attachments = $NUSOAP_SERVER->getAttachments();

            foreach ($markingrubrics['markingrubric'] as $markingrubric) {
                //check the assignment exists and the lightwork user capability.
                $assignment = get_record("assignment", "id", $markingrubric['activity']);
                if (!$assignment){
                    $rubric->error->add_error('Assignment', $markingrubric['activity'], 'noassignmentfound');
                    continue;
                }
                if (!$this->is_lightworkuser_by_assignment($assignment, $this->session->userid)) {
                    $rubric->error->add_error('Assignment', $markingrubric['activity'], 'unauthorisedaccess');
                    continue;
                }

                $cid = '<'.$markingrubric['xmltextref'].'>';
                $data = '';

                foreach ($attachments as $attachment) {
                    if ($cid == $attachment['cid']) {
                        $data = $attachment['data'];
                        break;
                    }
                }

                $rubric->id           = $markingrubric['id'];
                $rubric->activity     = $markingrubric['activity'];
                $rubric->activitytype = $markingrubric['activitytype'];
                $rubric->xmltext      = $data;
                $rubric->complete     = $markingrubric['complete'];
                $rubric->deleted      = $markingrubric['deleted'];
                $rubric->timemodified = $markingrubric['timemodified'];

                // save rubric
                $rubricid = $rubric->savebytimemodified($rubric->timemodified);

                //  populate the return payload
                if ($rubricid !== false) {
                    $rrubrics['markingRubricResponses']['markingRubricResponse'][] = array('id'=>$rubric->lwid, 'activity'=>$rubric->activity, 'timemodified'=>$rubric->timemodified);
                }
            }

            //  Add any errors that we found
            $rrubrics['errors'] = $rubric->error->get_errors();
            
        }

        return $rrubrics;
    }
    
    /**
     * Insert/Update a list of markings
     *
     * @param string $sesskey             The client session key.
     * @param array  $marking             Array of makings(s)
     * @param int    $type
     * @param int    $allstudents if 1(true) then all students are included. If 0(false) only
     * students who have submitted work (they have a record in mdl_assignment_submissions for an
     * assignment belonging to the course) are included.
     */
    function saveMarking($sesskey, $markingsarr, $type, $allstudents) {
        global $NUSOAP_SERVER, $CFG;

        $rmarkings = array();
        $markings = $markingsarr['marking'];
        // Create array of markings keyed by assignment
        $assignmentmarkings = array();

        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }

        if (isset($markingsarr['marking']['marker'])) {
            $markings = array();
            $markings[] = $markingsarr['marking'];
        }

        $attachments = $NUSOAP_SERVER->getAttachments();

        foreach ($markings as $marking) {
            $cid = '<'.$marking['xmltextref'].'>';
            $index = 0;
            foreach ($attachments as $attachment) {
                if ($cid == $attachment['cid']) {
                    // xmltextref field is overwritten with the data
                    $marking['xmltextref'] = $attachment['data'];
                    array_splice($attachments, $index, 1);
                    break;
                }
                $index++;
            }
            if (isset($marking['markinghistory']) && strpos($marking['xmltextref'], 'cid') !== 0) {
                //  now each history
                if (isset($marking['markinghistory']['lwid'])) {
                    $marking['markinghistory'] = array($marking['markinghistory']);
                }

                for ($i = 0; $i < count($marking['markinghistory']); $i++) {
                    //  Use parent status if not set
                    if (!isset($marking['markinghistory'][$i]['statuscode']) || ($marking['markinghistory'][$i]['statuscode'] == '')) {
                        $marking['markinghistory'][$i]['statuscode'] = $marking['statuscode'];
                    }
                }
            }
            
            if (array_key_exists($marking['activity'], $assignmentmarkings)){
                $assignmentmarkings[$marking['activity']][] = $marking;
            } else {
                $assignmentmarkings[$marking['activity']] = array($marking);
            }
        }

        $marker = new LW_Marker($this->session->userid, $type);
        $rmarkings['markingresponses'] = $marker->save_marking($assignmentmarkings, false, $allstudents);
        $rmarkings['errors'] = $marker->error->get_errors();

        return $rmarkings;
    }

    /**
     * Release a list of markings
     *
     * @param string $sesskey             The client session key.
     * @param array  $marking             Array of makings(s)
     * @param int    $type
     */
    function releaseMarking($sesskey, $markingsarr, $type) {
        global $NUSOAP_SERVER, $CFG, $LW_CFG;

        $rmarkings = array();
        $markings = $markingsarr['marking'];
        $assignmentmarkings = array();
        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }
        if ($LW_CFG->isDecimalPointMarkingEnabled){
            if (!$this->isMoodleInstanceCompatibleWithDecimalPointMarking()){
                return new soap_fault('Server','','decimalpointmarkingerror'); 
            } 
        }

        if (isset($markingsarr['marking']['marker'])) {
            $markings = array();
            $markings[] = $markingsarr['marking'];
        }

        $attachments = $NUSOAP_SERVER->getAttachments();

        foreach($markings as $marking) {
            $cid = '<'.$marking['xmltextref'].'>';
            foreach ($attachments as $attachment) {
                if ($cid == $attachment['cid']) {
                    // xmltextref field is overwritten with the data
                    $marking['xmltextref'] = $attachment['data'];
                }
                else {
                    foreach ($marking['annotatedRecords'] as $annotatedRecord) {
                        if (is_array($annotatedRecord)) {
                            $annotatedFileCid = '<'.$annotatedRecord['fileref'].'>';
                        }
                        else {
                            $annotatedFileCid = '<'.$annotatedRecord.'>';
                        }
                        if ($annotatedFileCid == $attachment['cid']) {
                            $marking['annotated_records'][] = array(
                                    'data' => $attachment['data'],
                                    'filename' => $annotatedRecord['filename'],
                                    'contenttype' => $attachment['contenttype']
                            );
                            break;
                        }
                    }
                }
            }

            if (isset($marking['markinghistory']) && strpos($marking['xmltextref'], 'cid') !== 0) {
                //  now each history
                if (isset($marking['markinghistory']['lwid'])) {
                    $marking['markinghistory'] = array($marking['markinghistory']);
                }

                for ($i = 0; $i < count($marking['markinghistory']); $i++) {
                    //  Use parent status if not set
                    if (!isset($marking['markinghistory'][$i]['statuscode']) || ($marking['markinghistory'][$i]['statuscode'] == '')) {
                        $marking['markinghistory'][$i]['statuscode'] = $marking['statuscode'];
                    }
                }
            }

            if (array_key_exists($marking['activity'], $assignmentmarkings)){
                $assignmentmarkings[$marking['activity']][] = $marking;
            } else {
                $assignmentmarkings[$marking['activity']] = array($marking);
            }

        }

        $marker = new LW_Marker($this->session->userid, $type);
        $rmarkings['markingresponses'] = $marker->save_marking($assignmentmarkings, true, 0);
        $rmarkings['errors'] = $marker->error->get_errors();

        return $rmarkings;
    }

   /**
     *
     * @param $sesskey
     * @param $teammarkingsarr
     *
     */
   function releaseTeamMarking($sesskey, $teammarkingsarr, $type) {
        global $LW_CFG, $NUSOAP_SERVER;
        if ($LW_CFG->isDecimalPointMarkingEnabled){
            if (!$this->isMoodleInstanceCompatibleWithDecimalPointMarking()){
                return new soap_fault('Server','','decimalpointmarkingerror');
            } 
        }
        $rteamrelease = array();//initialize the return array.
        //1. save team markings
        $rsaveteammarkings = $this->saveMarking($sesskey, $teammarkingsarr, $type, 0);

        //build retrun type.
        $rteamrelease['markingresponses'] = $rsaveteammarkings['markingresponses'];
        $rteamrelease['errors'] = $rsaveteammarkings['errors'];

        
        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }

        $teammarkings = $teammarkingsarr['marking'];

        if (isset($teammarkingsarr['marking']['marker'])) {
            $teammarkings = array();
            $teammarkings[] = $teammarkingsarr['marking'];
        }

        //error_log('size of team marking '.count($teammarkings));

        $marker = new lw_marker($this->session->userid, LW_Common::TEAM_MARKING);
        if (isset($teammarkings)) {
            //load annotation files from attachment.
            $attachments = $NUSOAP_SERVER->getAttachments();
            foreach ($teammarkings as $teammarking) {
                if ( isset($teammarking) &&
                isset($teammarking['statuscode']) ) {
                    $cid = '<'.$teammarking['xmltextref'].'>';
                    $teamfiles = array();
                    $memberfiles = array();
                    
                    //get files from attachment.
                    foreach ($attachments as $attachment) {
                        if ($cid == $attachment['cid']) {
                            // xmltextref field is overwritten with the data
                            //this data contains the xml text field of Marking Record.
                            $teammarking['xmltextref'] = $attachment['data'];
                        }
                        else {
                            foreach ($teammarking['annotatedRecords'] as $annotatedRecord) {
                                if (is_array($annotatedRecord)) {
                                    $annotatedFileCid = '<'.$annotatedRecord['fileref'].'>';
                                }
                                else {
                                    $annotatedFileCid = '<'.$annotatedRecord.'>';
                                }
                                if ($annotatedFileCid == $attachment['cid']) {
                                    //error_log('add file: '.$annotatedFileCid);
                                    //error_log('owner: '.$annotatedRecord['owner']);
                                    if ($annotatedRecord['owner']=='') {
                                        $teamfiles[] = array(
                                                       'data' => $attachment['data'],
                                                       'filename' => $annotatedRecord['filename'],
                                                       'contenttype' => $attachment['contenttype']
                                                       );
                                    } else {
                                        $memberfiles[] = array(
                                                         'data' => $attachment['data'],
                                                         'filename' => $annotatedRecord['filename'],
                                                         'owner' => $annotatedRecord['owner'],
                                                         'contenttype' => $attachment['contenttype']
                                                        );
                                    }
                                    break;
                                }
                            }
                        }
                    }
                    
                    //2. upload team shared files
                    $teammarking['annotated_records'] = $teamfiles;
                    $marker ->release_team_user_marking($teammarking, null, true);

                    //3. upload members shared files and markings
                    $teammarking['annotated_records'] = $memberfiles;          
                    $teamid = $teammarking['markable'] ;
                    $users = get_members_from_team($teamid);

                    //store the team mark and use for late release new join student.
                    $teammark = $teammarking['grade'];
                    if (isset($teammarking['teammemberdeduction'])) {
                        $teammembersinfo = array();
                        //if team member is only one, put is into an array and make looping easy.
                        if (isset($teammarking['teammemberdeduction']['member'])) {
                            $teammembersinfo = array($teammarking['teammemberdeduction']);
                        } else {
                            $teammembersinfo = $teammarking['teammemberdeduction'];
                        }
                        if (isset($users)
                        && is_array($users)
                        && count($users)>0
                        && count($teammembersinfo)>0) {
                            $releasedids = array();
                            foreach ($teammembersinfo as $userinfo) {
                                //error_log('process member id: '.$userinfo['member']);
                               // error_log('validate team member ');
                                     
                                if ( $user = get_student_in_team($userinfo['member'], $users) ) {
                                    //error_log('start release user id: '.$userinfo['member']);
                                    $teammarking['grade'] = $userinfo['finalmark'];
                                    $teammarking['submissioncomment'] = $userinfo['releasecomment'];
                                    $files = $teammarking['annotated_records'];
                                    $ownerfiles = $this->extract_team_member_files($files, $userinfo['member']);
                                    $teammarking['annotated_records'] = $ownerfiles;
                                    $marker -> release_team_user_marking($teammarking, $user);
                                    $teammarking['annotated_records'] = $files;
                                    $releasedids[] = $user->student;
                                } else {
                                    //give LW client a warning message.
                                    //shows this team member is not in this team.
                                    //error_log('this student is not in the team');
                                    $rteamrelease['errors']['error'][] = array(
                                                                                 'element'     =>'teammarking',
                                                                                 'id'          =>$userinfo['member'],
                                                                                 'errorcode'   =>'studentleaveteam',
                                                                                 'errormessage'=>get_string('studentleaveteam', 'local')
                                                                                 );
                                }
                            }
                            
                            //check if there are new students joining the team. 
                            //give them team final mark without any deduction .
                            $assignment = get_record("assignment", "id", $teammarking['activity']);
                            $context = get_context_instance(CONTEXT_COURSE, $assignment->course);
                            //put the team total mark back
                            $teammarking['grade'] = $teammark;
                            foreach($users as $member) {
                                $id = $member->student;
                                if (!in_array($id, $releasedids)) {
                                    //which means there is a team member joins this team
                                    //error_log('find a new student joins this team studentid: '. $id) ;
                                    //check this student's course participant
                                    $sql = get_student_participant_sql($id, $context->id);
                                    if($participant = get_records_sql($sql)) {
                                        //error_log("this student is a course participant ");
                                        $released = $marker -> release_team_user_marking($teammarking, $member);
                                        //error_log("participant sql".$sql);
                                        if ($released) {
                                            //add warning message pass to LW .
                                            //inform marker manager a new student joins this team.
                                            $rteamrelease['errors']['error'][] = array(
                                                                                 'element'     =>'teammarking',
                                                                                 'id'          =>$id,
                                                                                 'errorcode'   =>'studentjointeam',
                                                                                 'errormessage'=>get_string('studentjointeam', 'local')
                                                                                 );
                                        }
                                    } else {
                                        error_log("this student is not a course participant, ignore release");
                                    }                               
                                }
                            }
                        } else {
                            error_log('error: no users are in this team');
                        }
                    }
                }
            }
        }

        //add release errors.
        $releaseerrors = $marker ->error ->get_errors();
        if (is_array($releaseerrors) && !empty($releaseerrors)) {
            $records = $releaseerrors['error'];
            foreach($records as $error) {
                $rteamrelease['errors']['error'][] = $error;
            }
        }
        return $rteamrelease;
    }
    
    private function extract_team_member_files($annotatedfiles, $student) {
        $refiles = array();
        if (is_array($annotatedfiles)) {
            foreach($annotatedfiles as $file) {
               // error_log('Student id: '. $student);
               // error_log('file owner id: '. $file['owner']);
                if ($file['owner']==$student) {
                   // error_log('add owner file :'.$file['owner']);
                    $refiles[] = $file;
                }
            }
        }
        return $refiles;
    }
        
    /**
     * Insert/Update a list of files to be associated with assignments
     *
     * At present we are only expecting a single file, a PDF rendering of
     * the assignment rubric to be presented to students through the Moodle
     * web interface. The PDF iself is rendered and managed in the LightWork
     * client.
     *
     * @param string $sesskey             The client session key
     * @param array  $assignmentfile      Array of assignment id(s) and the file(s) to be uploaded
     * @todo   check teach has access to actually upload these rubrics
     */
    function uploadAssignmentDocuments($sesskey, $assignmentfiles) {
        global $NUSOAP_SERVER, $CFG;

        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }

        $assignfiles = array();
        $attachments = $NUSOAP_SERVER->getAttachments();

        if (isset($assignmentfiles['assignmentfile']['assignmentid'])) {
            $assignfiles['assignmentfile'][] = $assignmentfiles['assignmentfile'];
        } else {
            $assignfiles = $assignmentfiles;
        }

        foreach ($assignfiles['assignmentfile'] as $assignfile) {
            $assignmentid = $assignfile['assignmentid'];
            //check lightwork user capability
            $assignment = get_record("assignment", "id", $assignmentid);
            if (!$this->is_lightworkuser_by_assignment($assignment, $this->session->userid)) {
                return new soap_fault('Client', '', self::UNAUTHORISED_MESSAGE);
            }

            $files = array();

            if (isset($assignfile['assignmentfileresponse']['fileref'])) {
                $files['assignmentfileresponse'][] = $assignfile['assignmentfileresponse'];
            } else {
                $files = $assignfile;
            }

            // move the following line out of the foreach loop because it will always be the same for the same
            // assignmentid. Also remove the line which calls get_record() again for the same assignmentid.
            // Still have to rethink if the overall logic is making sense or not.
            $lw_doc = new LW_document($assignment->course, $assignmentid);
            foreach ($files['assignmentfileresponse'] as $file) {
                $cid = '<'.$file['fileref'].'>';
                foreach ($attachments as $attachment) {
                    if ($cid == $attachment['cid']) {
                        $lw_doc->document_save_file($attachment['data'], $file['filename']);
                        break;
                    }
                }
            }
        }
        return array('errors'=>$lw_doc->error->get_errors());
    }

    /**
     * Get the current public key for lightwork clients to use against this Moodle instance
     *
     * Note: no params are required and no session key needs to be sent, this call needs to be publically available
     *
     * @todo consider whether we need error handling for this call
     */
    function getPublicKey() {
        $lwpkey = new LW_pkey();
        return $lwpkey->publickey;
    }

    /**
     * Get the current version of this service.
     *
     * Note: no params are required and no session key needs to be sent,
     *       this call needs to be publically available
     */
    function getServiceVersion() {
        return self::SERVICE_VERSION;
    }

    /**
     * Get assignment metadata for a given course id and assignment ids
     *
     * @param string $sesskey      The client session key.
     * @param int    $courseid     Of the course to fetch metadata for
     * @param int    $assignmentid Of the assignment to fetch metadata for
     */
    function downloadAssignmentDocumentsMetaData($sesskey, $courseid, $assignmentid, $includeannotatedfiles) {
        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }
        
        if (!$this->is_lightworkuser_by_course($courseid, $this->session->userid)) {
            return new soap_fault('Client', '', self::UNAUTHORISED_MESSAGE);
        }

        $document = new LW_document($courseid, $assignmentid);
        $attachments = array();
        if ($includeannotatedfiles == 0) {
            $attachments['documentmetadata'] = $document->document_metadata_download(false);
        } else {
            $attachments['documentmetadata'] = $document->document_metadata_download(true); 
        }
        $attachments['errors'] = $document->error->get_errors();

        return $attachments;
    }
    
    /**
     * Checks the LW_RUBRIC, LW_MARKING, LW_HISTORY tables for orphan records and deletes
     * any that it finds. These deletions are returned to the Lightwork client so that it can
     * update its local database.
     * TODO This code must also return deletion records for missing course participants, 
     * mdl_user and mdl_assignment records 
     * Logic must be as follows:
     * 1) Create array of assignment ids based on those uploaded and the current ones on Moodle that user can see
     * 2) Iterate the assignment ids - if the assignment doesn't exist then delete assignment and children
     * 3) No need to check the rubric since this is done when checking markings
     * 4) Iterate the LW_MARKING records for each assignment id. If it doesn't have a rubric, student user, marker
     *    user, or valid status code then delete it and all its histories. Create delete records as required
     *    for marking, user, marking history and rubric. Check that each student or user record
     *    is a course participant. If not, create a course participant delete record to return.
     * 5) Iterate the remaining LW_MARKING_HISTORY records for each assignment id. Check that they all have a
     *    marking record. If not, delete and create a marking history delete record.
     * 6) Iterate the LW_TEAM_MARKING . It is similar to validate LW_MARKING. In addition, validate team members. 
     *    Team members may leave the team or withdraw from this course.
     * 7}Iterate the LW_TEAM_MARKING_HISTORY. It is similar to validate LW_MARKING_HISTORY.
     * @param $sesskey
     * @param $ids array of assignment ids sent by the client for checking
     * @return RepairLightWorkDataReturn type containing deletion elements for all records that have been found deleted
     * or have been deleted by this method.
     */
    function repairLightworkData($sesskey, $ids) {
        global $CFG;
        
        $assignmentids = array(); // array to hold the input parameter assignment ids
        $currentassignments = array();  // associative array holding the assignment id and course id
        $currentassignmentids = array(); // array of assignment ids for easy searching
        $rResult = array();
        $assignmentdeletions = array();
        $userdeletions = array();
        $participantdeletions = array();
        $rubricdeletions = array();
        $markingdeletions = array();
        $markinghistorydeletions = array();
        $teamdeletions = array();
        $teamstudentdeletions = array();
        $teammarkingdeletions = array();
        $teammarkinghistorydeletions = array();
        $errors = array();
        $error = new LW_Error();
        
        //  validate the input and make sure it is in an array
        if (is_array($ids['assignmentid'])) {
            $assignmentids = $ids['assignmentid'];
        } else if($ids['assignmentid']) {
            $assignmentids = array($ids['assignmentid']);
        } else {
            return new soap_fault('Client', '', 'The repairLightworkData call expects at less one assignment id');
        }
        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }        
        // return all courses that this user has access to
        $rcourses = $this->getAllCourses($sesskey, 0);
        if (!count($rcourses['courses'])){
            return new soap_fault('Client', '', self::UNAUTHORISED_MESSAGE);    
        }       
        $courses = $rcourses['courses'];
        foreach ($courses as $course){
            foreach ($course['assignments'] as $assignment) {
                $currentassignmentids[] = $assignment['id'];
                $assignmentobject = new Object();
                $assignmentobject->id = $assignment['id'];
                $assignmentobject->courseid = $course['id'];
                $currentassignments[] = $assignmentobject;
            }
        }
        // check for deleted assignments
        foreach ($assignmentids as $assignmentid){
        	if (!in_array($assignmentid, $currentassignmentids)){
        		if (!get_record("assignment", "id", $assignmentid)){
        		    if (!delete_records('lw_marking_history', 'activity', $assignmentid)) {
                        $error->add_error('Marking History', $assignmentid, 'deletefailed');	
                    }
        		    if (!delete_records('lw_marking', 'activity', $assignmentid)) {
                        $error->add_error('Marking', $assignmentid, 'deletefailed');	
                    }
        		    if (!delete_records('lw_team_marking_history', 'activity', $assignmentid)) {
                        $error->add_error('Team Marking History', $assignmentid, 'deletefailed');	
                    }
        		    if (!delete_records('lw_team_marking', 'activity', $assignmentid)) {
                        $error->add_error('Team Marking', $assignmentid, 'deletefailed');	
                    }
        		    if (!delete_records('lw_feedback', 'activity', $assignmentid)) {
                        $error->add_error('Feedback', $assignmentid, 'deletefailed');	
                    }                   
        		    if (!delete_records('lw_rubric', 'activity', $assignmentid)) {
                        $error->add_error('Rubric', $assignmentid, 'deletefailed');	
                    }
                    //delete all team and team_student in this assignment
                    $teams = get_records_sql("SELECT id, assignment, name, membershipopen".
                                 " FROM {$CFG->prefix}team ".
                                 " WHERE assignment = ".$assignmentid);
                    if ($teams) {
                        //delete all team_student in this team
                        foreach ($teams as $team) {
                            if (! delete_records('team_student', 'team', $team->id)) {
                                $error->add_error('Team Student', $assignmentid, 'deletefailed');
                            }
                        }
                        //delete this team
                        if (! delete_records('team', 'assignment', $assignmentid)) {
                            $error->add_error('Team', $assignmentid, 'deletefailed');
                        }
                    }  
        		}
        		// create an assignment deletion record to return to the client
        		// no need to create individual deletion records for the children
        		$assignmentdeletions['assignmentdeletion'][] = array('id'=>$assignmentid);   			
        	}
        }
        // check for invalid marking, marking history, team marking, and team marking history records 
        $checkedparticipants = array();       
        foreach ($currentassignments as $assignment) {
            $courseid =  $assignment->courseid;
            $assignmentid = $assignment ->id;      
            $context = get_context_instance(CONTEXT_COURSE, $courseid);
            $markingroles = $this -> getmarkingroles($context);
            
            //validate all assignment submission's course participants and user deletion.
            
            if ($submissions = get_records_sql("SELECT id,  userid ".
                                                " From {$CFG->prefix}assignment_submissions ".
                                                 "WHERE assignment = ". $assignment -> id)) {
                foreach ($submissions as $submission) {
                    $checkedstudent = in_array($submission -> userid, $checkedparticipants);
                    if (!$checkedstudent && !$student = get_record('user', 'id', $submission->userid)) {
                        $userdeletions['userdeletion'][] = array('id'=>$submission->userid);
                        $checkedparticipants[] = $submission -> userid;
                    }
                    $sql = get_student_participant_sql($submission->userid, $context->id);
                    if ( $student && !$checkedstudent && !$participant = get_records_sql($sql)) {
                        $participantdeletions['participantdeletion'][] = array('id'=>$student ->id,
                                                                               'courseid'=>$assignment->courseid,
                                                                               'username'=>$student->username,
                                                                               'idnumber'=>$student->idnumber,
                                                                               'firstname'=>$student->firstname,
                                                                               'lastname'=>$student->lastname,
                                                                               'timemodified'=>$student->timemodified);  
                        $checkedparticipants[] = $submission -> userid;                           	
                    }	
                }          
            }//end of validating assignment submissions.
            
            //validate marking or feedback
            if ($assignment->assignmenttype == LW_Common::FEEDBACK_TYPE){
                $markingtablename = "lw_feedback";
            } else {
                $markingtablename = "lw_marking";
            }
            if ($markingRecords = get_records_sql("SELECT id, marker, student, statuscode, rubric".
                                                         " FROM {$CFG->prefix}$markingtablename ".
                                                         " WHERE activity = ".$assignment->id)){
                foreach ($markingRecords as $marking) {
                    //For each marking check the marker and student in the user table, and the rubric and
                    //statuscode for validity.
                    $marker = get_record('user','id',$marking->marker);
                    $student = get_record('user','id',$marking->student);
                    $statuscode = get_record('lw_marking_status', 'statuscode', $marking->statuscode);
                    $rubric = get_record('lw_rubric','lwid',$marking->rubric,'activity',$assignment->id);
                    
                    // sql string
                    $deletemarkingsql = marking_selection_sql($marking, $assignment);
                    $markerparticpantsql = get_marker_participant_sql($marking, $context, $markingroles);
                    $studentparticpantsql = get_student_participant_sql($marking->student, $context->id);
                    
                    if (!$marker || !$student || !$statuscode || !$rubric) {
                        
                        if (delete_records_select($markingtablename,$deletemarkingsql)){
                            $markingdeletions['markingdeletion'][] = get_marking_deletion($marking, $assignment);
                            // associated marking history is deleted below
                        } else {
                            $error->add_error('Marking', '0', 'deletefailed');               	
                        }
                        if (!$marker){
                            $userdeletions['userdeletion'][] = array('id'=>$marking->marker);	
                        }
                        if (!$student){
                            $userdeletions['userdeletion'][] = array('id'=>$marking->student);	
                        }
                        if (!$rubric){
                            $rubricdeletions['rubricdeletion'][] = array('id'=>$marking->rubric);	
                        }
                    }
                    if ($marker){
                    	if (!$participant = get_records_sql($markerparticpantsql)) {
                            $participantdeletions['participantdeletion'][] = array('id'=>$marking->marker,
                                                                                   'courseid'=>$assignment->courseid,
                                                                                   'username'=>$marker->username,
                                                                                   'idnumber'=>$marker->idnumber,
                                                                                   'firstname'=>$marker->firstname,
                                                                                   'lastname'=>$marker->lastname,
                                                                                   'timemodified'=>$marker->timemodified);   	
                        }	
                    }
                    if ($student){
                        if (!$participant = get_records_sql($studentparticpantsql)) {
                            $participantdeletions['participantdeletion'][] = array('id'=>$marking->student,
                                                                                   'courseid'=>$assignment->courseid,
                                                                                   'username'=>$student->username,
                                                                                   'idnumber'=>$student->idnumber,
                                                                                   'firstname'=>$student->firstname,
                                                                                   'lastname'=>$student->lastname,
                                                                                   'timemodified'=>$student->timemodified); 
                            
                        }		
                    }
                }
            }// end of validating marking
                
            //Pick up all the markingHistory records attached to this assignment
            if ($markingHistoryRecords = get_records_sql("SELECT id, lwid, student, marker, rubric".
                                                         " FROM {$CFG->prefix}lw_marking_history ".
                                                         " WHERE activity = ".$assignment->id)){
                foreach ($markingHistoryRecords as $markingHistory) {
                    //Check to make sure it has an associated marking with it, we don't need to check anything else
                    //because if the marking exists then the previous test checked it's validity.
                    $marking = get_record('lw_marking', 'marker', $markingHistory->marker, 'student', $markingHistory->student,
                                          'activity', $assignment->id);
                    if (!$marking) {
                        //Delete the marking history
                        if (delete_records_select('lw_marking_history',
                                           "lwid = {$markingHistory->lwid} ".
                                           "AND marker = {$markingHistory->marker} ".
                                           "AND student = {$markingHistory->student} ".
                                           "AND rubric = {$markingHistory->rubric} ".
                                           "AND activity = ".$assignment->id)) {
                            $markinghistorydeletions['markinghistorydeletion'][] = array(
                                                                 'lwid'        =>$markingHistory->lwid,
                                                                 'markable'     =>$markingHistory->student,
                                                                 'marker'      =>$markingHistory->marker,
                                                                 'activity'    =>$assignment->id,
                                                                 'rubric'      =>$markingHistory->rubric);
                        } else {
                            $error->add_error('Marking History', '0', 'deletefailed');	
                        }
                    }
                }
            }// end of validating marking history.
            
            //validate team and team_students in this assignment
            $teams = get_records_sql("SELECT id, assignment, name, membershipopen".
                                 " FROM {$CFG->prefix}team ".
                                 " WHERE assignment = ".$assignment->id);
            if ($teams) {
                foreach($teams as $team) {
                    $members = get_members_from_team($team->id);
                    if ($members) {
                        foreach ($members as $member) {
                            $student = get_record('user', 'id', $member->student);
                            if (!$student) {                                
                                if (!delete_records('team_student', 'student', $member->student)) {
                                    $error->add_error('Delete team student', $member->student, 'deletefailed');	
                                } else {
                                    $userdeletions['userdeletion'][] = array('id'=>$member->student);
                                    $teamstudentdeletions['teamstudentdeletion'][] = array('id'=> $member->id);
                                }
                            } else {
                                $sql = get_student_participant_sql($member->student,$context->id);
                                if (!$participant = get_records_sql($sql)) {
                                     $participantdeletions['participantdeletion'][] = array('id'=>$member->student,
                                                                                   'courseid'=>$assignment->courseid,
                                                                                   'username'=>$student->username,
                                                                                   'idnumber'=>$student->idnumber,
                                                                                   'firstname'=>$student->firstname,
                                                                                   'lastname'=>$student->lastname,
                                                                                   'timemodified'=>$student->timemodified); 
                                     if (!delete_records('team_student', 'student', $member->student)) {
                                         $error->add_error('Delete team student', $member->student, 'deletefailed');	
                                     } else {
                                         $teamstudentdeletions['teamstudentdeletion'][] = array('id'=> $member->id);
                                         //check any members is in this team.
                                         $newmembers = get_members_from_team($team->id);
                                         if (!$newmembers) {
                                             //this means no records can find in this team. we should delete this team
                                             if (! delete_records('team', 'id', $team->id,'assignment', $assignmentid)) {
                                                 $error->add_error('Team', $assignmentid, 'deletefailed');
                                             } else {           
                                                 $teamdeletions['teamdeletion'][] = array('id'=>$team->id);
                                             }
                                         }
                                     }
                                 }	
                             }
                          }
                      } else {
                          //this team do not have any team members delete this team.
                          if (! delete_records('team', 'id', $team->id,'assignment', $assignmentid)) {
                              $error->add_error('Team', $assignmentid, 'deletefailed');
                          } else {
                              $teamdeletions['teamdeletion'][] = array('id'=>$team->id);
                          }
                      }          
                }
            } //end of validate team.
            
            //validate team marking
            if ($teamMarkingRecords = get_records_sql("SELECT id, marker, activity, team, statuscode, rubric".
                                                         " FROM {$CFG->prefix}lw_team_marking ".
                                                         " WHERE activity = ".$assignment->id)){
                foreach ($teamMarkingRecords as $marking) {
                    //For each marking check the marker and student in the user table, and the rubric and
                    //statuscode for validity.
                    $marker = get_record('user','id',$marking->marker);
                    $team = get_record('team','id',$marking->team, 'assignment', $marking->activity);
                    $statuscode = get_record('lw_marking_status', 'statuscode', $marking->statuscode);
                    $rubric = get_record('lw_rubric','lwid',$marking->rubric,'activity',$assignment->id);
                    if (!$marker || !$team || !$statuscode || !$rubric) {
                        if (delete_records_select('lw_team_marking', 
                                           "marker = {$marking->marker} ".
                                           "AND team = {$marking->team} ".
                                           "AND rubric = {$marking->rubric} ".
                                           "AND activity = ".$assignment->id)){
                            $teammarkingdeletions['teammarkingdeletion'][] = array(
                                                            'markable'    =>$marking->team,
                                                            'marker'      =>$marking->marker,
                                                            'activity'    =>$assignment->id,
                                                            'rubric'      =>$marking->rubric);
                            // associated marking history is deleted below
                        } else {
                            $error->add_error('Marking', '0', 'deletefailed');               	
                        }
                        if (!$marker){
                            $userdeletions['userdeletion'][] = array('id'=>$marking->marker);	
                        }
                        if (!$team){
                            $teamdeletions['teamdeletion'][] = array('id'=>$marking->team);	
                        }
                        if (!$rubric){
                            $rubricdeletions['rubricdeletion'][] = array('id'=>$marking->rubric);	
                        }
                    }
                    if ($marker){
                        $sql = get_marker_participant_sql($marking, $context, $markingroles);
                    	if (!$participant = get_records_sql($sql)) {
                            $participantdeletions['participantdeletion'][] = array('id'=>$marking->marker,
                                                                                   'courseid'=>$assignment->courseid,
                                                                                   'username'=>$marker->username,
                                                                                   'idnumber'=>$marker->idnumber,
                                                                                   'firstname'=>$marker->firstname,
                                                                                   'lastname'=>$marker->lastname,
                                                                                   'timemodified'=>$marker->timemodified);
                        }	
                    }
                    
                    //team validation already done we do not do it here.
                }
            }//end of validating team marking.
            
            // validate team marking history
            if ($teamMarkingHistoryRecords = get_records_sql("SELECT id, lwid, team, marker, rubric".
                                                         " FROM {$CFG->prefix}lw_team_marking_history ".
                                                         " WHERE activity = ".$assignment->id)){
                foreach ($teamMarkingHistoryRecords as $markingHistory) {
                    //Check to make sure it has an associated marking with it, we don't need to check anything else
                    //because if the marking exists then the previous test checked it's validity.
                    $marking = get_record('lw_team_marking', 'marker', $markingHistory->marker, 'team', $markingHistory->team,
                                          'activity', $assignment->id);
                    if (!$marking) {
                        //Delete the marking history
                        if (delete_records_select('lw_team_marking_history',
                                           "lwid = {$markingHistory->lwid} ".
                                           "AND marker = {$markingHistory->marker} ".
                                           "AND team = {$markingHistory->team} ".
                                           "AND rubric = {$markingHistory->rubric} ".
                                           "AND activity = ".$assignment->id)) {
                            $teammarkinghistorydeletions['teammarkinghistorydeletion'][] = array(
                                                                 'lwid'        =>$markingHistory->lwid,
                                                                 'markable'    =>$markingHistory->team,
                                                                 'marker'      =>$markingHistory->marker,
                                                                 'activity'    =>$assignment->id,
                                                                 'rubric'      =>$markingHistory->rubric);
                        } else {
                            $error->add_error('Marking History', '0', 'deletefailed');	
                        }
                    }
                }
            }
           //end of validating team marking history
        }     
        $errors = $error->get_errors();
        $rResult['assignmentdeletions'] = $assignmentdeletions;
        $rResult['userdeletions'] = $userdeletions;
        $rResult['participantdeletions'] = $participantdeletions;
        $rResult['rubricdeletions'] = $rubricdeletions;
        $rResult['markingdeletions'] = $markingdeletions;
        $rResult['markinghistorydeletions'] = $markinghistorydeletions;
        $rResult['teamdeletions'] = $teamdeletions;
        $rResult['teamstudentdeletions'] = $teamstudentdeletions;
        $rResult['teammarkingdeletions'] = $teammarkingdeletions;
        $rResult['teammarkinghistorydeletions'] = $teammarkinghistorydeletions;
        $rResult['errors'] = $errors;
        return $rResult;
    }

    /**
     * Determines if a user is allowed to use light work
     *
     * @param int $courseid The id of the course
     * @param int $userid The id of the user that is being tested against.
     * @return boolean
     */
    function islightworkuser($courses, $userid) {
        foreach ($courses as $course) {
            if ($this->is_lightworkuser_by_course($course['id'], $userid)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the all teams for the given assignment ids that have changed since the provided timemodified.
     * or Get the teams from the give team ids in one single assignment.
     * Ideally timemodified should be the datetime of the last synchronisation of team data. This will
     * enable lightwork to obtain only that team data that has changed.
     * 
     * @param $sesskey
     * @param $ids
     * @param $timemodified
     * @param int    $allstudents if 1(true) then all students are included. If 0(false) only
     * team members who have submitted work (they have a record in mdl_assignment_submissions for an
     * assignment belonging to the course) are included.
     */
    function getAssignmentTeams( $sesskey, $ids, $teamids , $timemodified, $allstudents ){
        global $CFG;
        $assignmentids = array();
        $teamidsholder = array();
        $response = array();
        $timemodified = (empty($timemodified) ? 0 : $timemodified);
        //  Validate the session
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }
        
        $isallteams = false;
        //  put requested ids in one array -- easy to search
        if(is_array($ids['assignmentid'])) {
            $assignmentids = $ids['assignmentid'];
        } elseif($ids['assignmentid']) {
            $assignmentids = array($ids['assignmentid']);
        } else {
            return $this->error('The getAssignmentTeams call expects at least one assignment id');
        }
        //if lightwork set the teamids is 0, which means require to get
        // all teams from the give assignment.
        if (is_array($teamids['teamid'])) {
            //error_log('team ids is array');
            if(count ($teamids['teamid']) == 1 && $teamids['teamid'][0] == 0) {
                //error_log('this team ids array size 1 and has value 0');
                $isallteams = true;
            }else {
                //error_log('this team ids array size greater 1 or did have value 0');
                $teamidsholder = $teamids['teamid'];
            }
        } elseif($teamids['teamid'] == 0) {
            //error_log('this team id value is 0');
            $isallteams = true;
        }elseif($teamids['teamid']) {
            //error_log('this team id  value is not 0');
            $teamidsholder = array($teamids['teamid']);
                  
        } else {
            //error_log('team id required in getAssignmentTeams');
            return $this -> error ('team id required in getAssignmentTeams');
        }

        $errors = new LW_Error();
        foreach( $assignmentids as $assignment ) {
            //error_log('assingmentid:'. $assignment);
            $assignmentrec = get_record('assignment', 'id', $assignment);
            if (!$assignmentrec) {
                //error_log("this assignment is not in moodle id: ". $assignment);
                //$errors->add_error('assignment', $assignment, 'noassignmentfound');
                continue;
            }   
            $context = get_context_instance(CONTEXT_COURSE, $assignmentrec->course);
            $markingroles = $this -> getmarkingroles($context);
            
            $teams = array();
            $team_recs = array();
            if ($isallteams) {
                //error_log('get all teams from this assignment');
                $teamSQL = " SELECT id, name, membershipopen, timemodified FROM {$CFG->prefix}team WHERE assignment = " . $assignment;
                
                if( $timemodified > 0 )
                    $teamSQL .= ' AND timemodified > ' . $timemodified;
                $teamSQL .= ' ORDER BY assignment';
                $team_recs = get_records_sql( $teamSQL );
            } else {
                //error_log('extract teams from team table');
                foreach ($teamidsholder as $teamid) {
                    $teamrecord = get_record('team', 'id', $teamid, 'assignment', $assignment);
                    if(!$teamrecord) {
                       // error_log('team not in moodle'. $teamid);
                        //$errors->add_error('team', $teamid, 'wrnnoassignmentteams');
                        continue;
                    }
                   // error_log('add team into team_recs '.$teamid );
                    $team_recs[] = $teamrecord;
                }
            }           
            if( $team_recs && !empty($team_recs)) {
                foreach( $team_recs as $team ){
                    $students = array();
                    $student_recs = array();
                    if( $student_recs = get_records('team_student', 'team', $team->id)){
                        $submittingAssignmentStudentsOnlySql = '';
                        if ($allstudents == 0){
                            $submittingAssignmentStudentsOnlySql = " AND u.id IN (SELECT sb.userid FROM ".
                                             "{$CFG->prefix}assignment_submissions sb INNER JOIN ".
                                             "{$CFG->prefix}assignment a on sb.assignment=a.id ".
                                             "WHERE a.id= '$assignment' AND sb.data2 = 'submitted') ";
                        }
                        foreach( $student_recs as $student ){                     
                            if ($participant = get_records_sql("SELECT u.id FROM {$CFG->prefix}user u INNER JOIN ".
                                "{$CFG->prefix}role_assignments ra on u.id=ra.userid ".
                    	        "WHERE u.id = {$student->student} ".
                                $submittingAssignmentStudentsOnlySql.
                                "AND ra.contextid = {$context->id} ".
                                "AND ra.roleid NOT IN ".$markingroles)) {
                                $students['student'][] = array('id'=>$student->id, "studentid"=>$student->student, "timemodified"=>$student->timemodified); 
                            }                         
                        }
                    }
                    // Always return teams only this team has students element if needs be.
                    if (count($students ) > 0 ) {
                        $teams['team'][] = array(
                    	'id'=>$team->id, 
                    	'name'=>$team->name, 
                    	'membershipopen'=>$team->membershipopen, 
                    	'timemodified'=>$team->timemodified, 
                    	'students'=>$students); 
                    }
                     
                }
            } else {
                // No teams found
            }
            // Always return assignment, with empty teams element if needs be.
            $response['assignments']['assignment'][] = array('id'=>$assignment, 'teams'=>$teams);
        }        
        
        $response['errors'] = $errors->get_errors();
        return $response;
    }
    
    function is_lightworkuser_by_course($courseid, $userid) {
        return has_capability(LW_Common::CAP_MANAGELWMARKERS, get_context_instance(CONTEXT_COURSE, $courseid), $userid, false)||
                has_capability(LW_Common::CAP_MARKLWSUBMISSIONS, get_context_instance(CONTEXT_COURSE, $courseid), $userid, false);
    }

    function is_lightworkuser_by_assignment($assignment, $userid) {
        $context = get_context_instance(CONTEXT_COURSE, $assignment->course);
        if (has_capability(LW_Common::CAP_MANAGELWMARKERS , $context, $userid, false)
                ||has_capability(LW_Common::CAP_MARKLWSUBMISSIONS , $context, $userid, false)) {
            return true;
        }
        return false;
    }
    
    /**
     * Returns data concerning all student submissions for a course's assignments made
     * during the specified time period.
     * @param $sesskey
     * @param $courseid the course for which student submission data should be returned
     * @param $startdate the start date (inclusive) from which the submission data is required
     * @param $enddate the end date (inclusive) up to which the submission data is required
     * @return unknown_type
     */
    function getSubmissionReport($sesskey, $courseid, $startdate, $enddate){
        global $CFG;
        $response = array();
        $errors = new LW_Error();
        if (!$this->validate_session($sesskey)) {
            return $this->fault;
        }        
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        if (!has_capability(LW_Common::CAP_MANAGELWMARKERS , $context, $this->session->userid, false)){
            return new soap_fault('Client', '', self::UNAUTHORISED_MESSAGE);    
        }
        $starttimestamp = 0;
        $endtimestamp = 0;
        if (isset($startdate)){
            $starttimestamp = strtotime($startdate);    
        }
        if (isset($enddate)){
            $endtimestamp = strtotime($enddate."+1 day");    
        }
        $extrafields='m.id, m.name, m.assignmenttype, m.timedue';
        if ($assignments = get_coursemodules_in_course('assignment', $courseid, $extrafields)) {
            $submittedstatus = LW_Common::SUBMITTED;
            foreach ($assignments as $assignment) {
                if ($assignment->assignmenttype == LW_Common::FEEDBACK_TYPE){
                    $sql="SELECT u.id,u.idnumber,u.firstname,u.lastname,".
                             "f.paper,f.wordlimit,f.referencingstyle,f.duedate, f.timemodified, b.marker, b.statuscode". 
                             " FROM {$CFG->prefix}FEEDBACK_SUBMISSION f". 
                             " INNER JOIN {$CFG->prefix}ASSIGNMENT_SUBMISSIONS s on f.submission = s.id".
                             " INNER JOIN {$CFG->prefix}USER u on s.userid = u.id".
                             " LEFT JOIN {$CFG->prefix}LW_FEEDBACK b on s.userid = b.student and s.assignment = b.activity and b.deleted = 0 ".
                             " where s.assignment = '$assignment->id'".
                             " and s.data2 = '$submittedstatus'".
                             " and f.timemodified >= '$starttimestamp'";
                    if ($endtimestamp > 0){
                        $sql = $sql." and f.timemodified <= '$endtimestamp'";
                    }
                    
                    if ($items = get_records_sql($sql)) {
                        foreach ($items as $item) {
                            $markerfirstname = '';
                            $markerlastname = '';
                            $markerid = $item->marker;
                        	if (!empty($markerid)) {
                        		if ($marker = get_record('user', 'id', $markerid)) {
                        			$markerfirstname = $marker->firstname;
                        			$markerlastname = $marker->lastname;
                        		}
                        	}
                            $response['studentreportrecords']['studentreportrecord'][] = array(
                            'studentid'              => $item->idnumber,
                            'userid'                 => $item->id,
                            'assignmentid'           => $assignment->id,
                            'assignmentname'         => $assignment->name,
                            'firstname'              => $item->firstname,
                            'lastname'               => $item->lastname,
                            'paper'                  => $item->paper,
                            'duedate'                => $item->duedate,
                            'wordlimit'              => $item->wordlimit,
                            'referencingstyle'       => $item->referencingstyle,
                            'submissiondate'         => $item->timemodified,
                            'markerid'               => $markerid,
                            'markerfirstname'        => $markerfirstname,
                            'markerlastname'         => $markerlastname,
                            'status'                 => $item->statuscode
                            );
                        }
                    }
                } else {
                    $sql="SELECT u.id,u.idnumber,u.firstname,u.lastname, s.timemodified, m.marker, m.statuscode".
                             " FROM {$CFG->prefix}ASSIGNMENT_SUBMISSIONS s".
                             " INNER JOIN {$CFG->prefix}USER u on s.userid = u.id".
                             " LEFT JOIN {$CFG->prefix}LW_MARKING m on s.userid = m.student and s.assignment = m.activity  and m.deleted = 0 ".
                             " where s.assignment = '$assignment->id'".
                             " and s.data2 = '$submittedstatus'".
                             " and s.timemodified >= '$starttimestamp'";
                    if ($endtimestamp > 0){
                        $sql = $sql." and s.timemodified <= '$endtimestamp'";
                    }
                    
                    if ($items = get_records_sql($sql)) {
                        foreach ($items as $item) {
                            $markerfirstname = '';
                            $markerlastname = '';
                            $markerid = $item->marker; 
                        	if (!empty($markerid)) {
                        		if ($marker = get_record('user', 'id', $markerid)) {
                        			$markerfirstname = $marker->firstname;
                        			$markerlastname = $marker->lastname;
                        		}
                        	}
                            $response['studentreportrecords']['studentreportrecord'][] = array(
                            'studentid'              => $item->idnumber,
                            'userid'                 => $item->id,
                            'assignmentid'           => $assignment->id,
                            'assignmentname'         => $assignment->name,
                            'firstname'              => $item->firstname,
                            'lastname'               => $item->lastname,                            
                            'submissiondate'         => $item->timemodified,
                            'markerid'               => $markerid,
                            'markerfirstname'        => $markerfirstname,
                            'markerlastname'         => $markerlastname,
                            'status'                 => $item->statuscode
                            );
                        }
                    }
                }
            }
        }
        $response['errors'] = $errors->get_errors();
        return $response; 
    }
    
    private function getmarkingroles($context) {
        $markingroles = '';
        if ( $roles = get_roles_with_capability(LW_Common::CAP_MARKLWSUBMISSIONS, CAP_ALLOW, $context)) {
            $roleids = array();
            foreach ($roles as $role) {
                $roleids[]= $role->id;
            }
            $markingroles = '('. implode(',', $roleids) . ')';
        }
        return $markingroles; 
    }
    
    /**
     * This function checks if the Moodle database is compatible
     * with decimal point marking.
     * @return TRUE if the Moodle instance is compatible, otherwise return FALSE
     */
    private function isMoodleInstanceCompatibleWithDecimalPointMarking() {
        global $CFG, $LW_CFG;
        include_once($CFG->libdir.'/dmllib.php');
        $type = column_type('assignment_submissions', 'grade');
        if ($type == 'N'){
            return TRUE; 
        } elseif ($type == 'F') {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
}
?>