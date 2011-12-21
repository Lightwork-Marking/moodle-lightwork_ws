<?php

/**
 * Class for handling querying and manipulating LW PreReading records.
 *
 * PHP version 5
 *
 * @package lightwork.lib
 * @version $Id$
 * @author Paul Charsley
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */


/**
 * Class for handling querying and manipulating individual PreReading records. Checking their
 * properties, inserting, updating deleting etc.
 */
require_once('lw_common.php');
require_once('lw_config.php');

class LW_Feedback  {
    private $uid;
    public  $error;
    public  $helper;

    public function __construct($uid=null)  {
        if (is_numeric($uid) && ($uid>0)) {
            $this->uid = $uid;
        } else {
            return null;
        }
        $this->error = new LW_Error();
        $this->helper = new LW_Common();
    }
    
    /**
     * Gets the feedback submission records for this marker for an assignment id
     * @param   int      $assignment    assignment id to get feedback submission records for
     * @param   int      $timemodified  Modified time
     * @return  array    Array of feedback submission records
     */
    public function get_feedback_submissions($assignmentid, $timemodified=0) {
        global $CFG, $DB;

        $rfeedbacksubmissions = array();

        if (is_null($assignmentid)) {
            $this->error->add_error('Feedback', 0, 'noidsmarking');
            return $rfeedbacksubmissions;
        }

        $assignment = $DB->get_record('assignment', array('id'=>$assignmentid));
        if (!$assignment){
            $this->error->add_error('Feedback', $assignmentid, 'noassignmentfound');
            return $rfeedbacksubmissions;
        }

        $context = get_context_instance(CONTEXT_COURSE, $assignment->course);
        $iscoordinator = has_capability(LW_Common::CAP_MANAGELWMARKERS , $context, $this->uid, false);
        $ismarker = has_capability(LW_Common::CAP_MARKLWSUBMISSIONS , $context, $this->uid, false);

        //  Is the user allowed to do anything?
        if ($iscoordinator || $ismarker) {
            $query = '';
            $sql = '';
            $wheretime = ($timemodified > 0) ? " AND fs.timemodified > $timemodified " : '';

            if ($iscoordinator) {
                $sql = "SELECT fs.id,fs.submission,fs.paper,fs.duedate,fs.topic,fs.wordlimit,".
                "fs.referencingstyle,fs.questions,fs.difficulties,fs.timefirstsubmitted,fs.timemodified".
                " FROM {FEEDBACK_SUBMISSION} fs INNER JOIN {ASSIGNMENT_SUBMISSIONS} a".
                " ON fs.submission = a.id".
                " WHERE fs.timefirstsubmitted IS NOT NULL".
                " AND a.assignment = '$assignmentid'";
            } else {
                $sql = "SELECT fs.id,fs.submission,fs.paper,fs.duedate,fs.topic,fs.wordlimit,".
                "fs.referencingstyle,fs.questions,fs.difficulties,fs.timefirstsubmitted,fs.timemodified".
                " FROM {FEEDBACK_SUBMISSION} fs INNER JOIN {ASSIGNMENT_SUBMISSIONS} a".
                " ON fs.submission = a.id".
                " INNER JOIN {LW_FEEDBACK} f".
                " ON (a.assignment = f.activity AND a.userid = f.student)".
                " WHERE a.assignment = '$assignmentid'".
                " AND f.marker = '{$this->uid}'";
            }

            $query = $sql.$wheretime;
            if ($items = $DB->get_records_sql($query)) {
                foreach ($items as $item) {
                    $rfeedbacksubmissions['feedbackSubmission'][] = array(
                            'id'                  => $item->id,
                            'submission'          => $item->submission,
                            'paper'               => $item->paper,
                            'duedate'             => $item->duedate,
                            'topic'               => $item->topic,
                            'wordlimit'           => $item->wordlimit,
                            'referencingstyle'    => $item->referencingstyle,
                            'questions'           => $item->questions,
                            'difficulties'        => $item->difficulties,
                            'timefirstsubmitted'  => $item->timefirstsubmitted,
                            'timemodified'        => $item->timemodified
                    );
                }
            }
        }
        else {
            $this->error->add_error('Feedback', $assignmentid, 'feedbackaccessforbidden');
        }

        return $rfeedbacksubmissions; 
    }
    
    public function get_demographics($useridswithtimemodified, $assignmentid){
        global $CFG, $LW_CFG, $DB;
        
        $demographics = array();
        $userids = array();
        $timemodified = array();

        if (is_null($assignmentid)) {
            $this->error->add_error('Demographics', 0, 'noidsmarking');
            return $demographics;
        }

        $assignment = $DB->get_record('assignment', array('id'=>$assignmentid));
        if (!$assignment){
            $this->error->add_error('Demographics', $assignmentid, 'noassignmentfound');
            return $demographics;
        }
        
        $category = $DB->get_record('user_info_category', array('name'=>$LW_CFG->user_info_category));
        if (!$category){
            $this->error->add_error('Demographics', 0, 'nouserinfocategory');
            return $demographics;
        }

        $context = get_context_instance(CONTEXT_COURSE, $assignment->course);
        $iscoordinator = has_capability(LW_Common::CAP_MANAGELWMARKERS , $context, $this->uid, false);
        $isprereader = has_capability(LW_Common::CAP_MARKLWSUBMISSIONS , $context, $this->uid, false);
        
        if (isset($useridswithtimemodified['userid']['userid'])) {
            $userids[] = $useridswithtimemodified['userid']['userid'];
            $timemodified[$useridswithtimemodified['userid']['userid']] = $useridswithtimemodified['userid']['timemodified'];
        }
        else {
            foreach($useridswithtimemodified['userid'] as $useridwithtimemodified) {
                $userids[] = $useridwithtimemodified['userid'];
                $timemodified[$useridwithtimemodified['userid']] = $useridwithtimemodified['timemodified'];
            }
        }

        //  Is the user allowed to do anything?
        if ($iscoordinator || $isprereader) {
            $submittedstatus = LW_Common::SUBMITTED;
            $sql = '';
            if ($iscoordinator){
                $sql = "SELECT uid.id,u.id AS userid,uid.data,uif.shortname,u.timemodified FROM {USER_INFO_DATA} uid".
                " INNER JOIN {USER_INFO_FIELD} uif ON uid.fieldid = uif.id".
                " INNER JOIN {USER} u ON uid.userid = u.id".
                " INNER JOIN {ASSIGNMENT_SUBMISSIONS} s ON u.id=s.userid".
                " WHERE s.assignment = '$assignmentid'".
                " AND s.data2 = '$submittedstatus'".
                " AND uif.categoryid = '$category->id'".
                " AND u.id IN (".implode(',', $userids).") order by u.id";
            } else { // pre reader
                $sql = "SELECT uid.id,u.id AS userid,uid.data,uif.shortname,u.timemodified FROM {USER_INFO_DATA} uid".
                " INNER JOIN {USER_INFO_FIELD} uif ON uid.fieldid = uif.id".
                " INNER JOIN {USER} u ON uid.userid = u.id".
                " INNER JOIN {ASSIGNMENT_SUBMISSIONS} s ON u.id=s.userid".
                " INNER JOIN {LW_FEEDBACK} fb ON s.userid=fb.student".
                " WHERE s.assignment = '$assignmentid'".
                " AND s.data2 = '$submittedstatus'".
                " AND fb.statuscode != 'RL'".
                " AND uif.categoryid = '$category->id'".
                " AND u.id IN (".implode(',', $userids).") order by u.id";
            }

            if ($items = $DB->get_records_sql($sql)) {
                foreach ($items as $item) {
                    if (!empty($item->data)){
                        $demographics['demographic'][] = array(
                            'userid'                 => $item->userid,
                            'data'                   => $item->data,
                            'shortname'              => $item->shortname,
                            'timemodified'           => $item->timemodified
                        );
                    } 
                }
            }
        }
        else {
            $this->error->add_error('Demographics', $assignmentid, 'feedbackaccessforbidden');
        }

        return $demographics; 
    }

}

?>