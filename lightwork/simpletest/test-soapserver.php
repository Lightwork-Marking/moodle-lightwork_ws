<?php
/**
 * Unit tests for LW_soapserver
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package nusoap
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}

class lw_soap_test extends UnitTestCase {

    private $soapproxy;

    function test_login() {
        $this->client = new soapclient($this->WS_WSDL_URL, true);
        $this->proxy  = $this->client->getProxy();
        $result = $this->proxy->mdl_soapserver__login($this->WS_LOGIN_USERNAME, $this->WS_LOGIN_PASSWORD);
        $this->mykey = $result['sessionkey'];
    }

    function test_session() {
        $this->assertEqual(strlen($this->mykey),32);
    }

    function test_get_courses() {

        $courseids = array();

        if ($ids = explode(',', $this->courseids)) {
            foreach ($ids as $id) {
                $courseids['courseid'][] = $id;
            }
        }

        // get courses
        $coursesResult = $this->proxy->mdl_soapserver__getCourses($this->mykey, $courseids, $this->timemodified);

        // check if there is a result
        $this->assertNotNull($coursesResult);

        // loop through result and see if course node exists
        foreach ($coursesResult['courses']['item'] as $course) {
            // count number of nodes in courses array
            $this->assertEqual(count($course), 6);
            // check if node exists
            $this->assertTrue(array_key_exists('id',$course));
            $this->assertTrue(array_key_exists('fullname',$course));
            $this->assertTrue(array_key_exists('shortname',$course));
            $this->assertTrue(array_key_exists('idnumber',$course));
            $this->assertTrue(array_key_exists('timemodified',$course));
            $this->assertTrue(array_key_exists('assignments',$course));

            // check if the result's course id is the same as the courseid in local var
            $this->assertTrue(in_array($course['id'], $courseids['courseid']));

            // if there more than one assignment
            if (array_key_exists('assignments',$course) &&
                    isset($course['assignments']['item']) &&
                    count($course['assignments']['item']) > 0
                ){
                //pick one assignment array to test
                $assignment = array_pop($course['assignments']['item']);

                // count number of nodes in assignments array
                $this->assertEqual(count(array_keys($assignment)), 7);

                // check if node exists
                $this->assertTrue(array_key_exists('id',$assignment));
                $this->assertTrue(array_key_exists('course',$assignment));
                $this->assertTrue(array_key_exists('name',$assignment));
                $this->assertTrue(array_key_exists('timedue',$assignment));
                $this->assertTrue(array_key_exists('description',$assignment));
                $this->assertTrue(array_key_exists('assignmenttype',$assignment));
                $this->assertTrue(array_key_exists('timemodified',$assignment));
            }
        }
    }

    function test_get_participants() {
        $courseids = array();

        if ($ids = explode(',', $this->courseids)) {
            foreach ($ids as $id) {
                $courseids['courseid'][] = $id;
            }
        }

        // get participants
        $paricipantsResult = $this->proxy->mdl_soapserver__getCourseParticipants($this->mykey, $courseids, $this->timemodified);

        // check if there is a result
        $this->assertNotNull($paricipantsResult);
        // loop through result and see if course node exists
        foreach ($paricipantsResult['courses']['course'] as $course) {
            // count number of nodes in participants array
            $this->assertEqual(count($course), 2);

            // check if node exists
            $this->assertTrue(isset($course['id']));
            $this->assertTrue(isset($course['user']));

            // check if the result's course id is the same as the courseid in local var
            $this->assertTrue(in_array($course['id'], $courseids['courseid']));

            // if there more than one user
            if (count($course['user'])){
                // pick one user array to test
                $user = array_pop($course['user']);

                // count number of nodes in user array
                $this->assertEqual(count($user), 8);

                // check if node exists
                $this->assertTrue(array_key_exists('id',$user));
                $this->assertTrue(array_key_exists('username',$user));
                $this->assertTrue(array_key_exists('idnumber',$user));
                $this->assertTrue(array_key_exists('firstname',$user));
                $this->assertTrue(array_key_exists('lastname',$user));
                $this->assertTrue(array_key_exists('roleid',$user));
                $this->assertTrue(array_key_exists('capabilitycode',$user));
                $this->assertTrue(array_key_exists('timemodified',$user));
            }
        }

    }

    function test_get_submissions() {
        $assignmentids = array();

        if ($ids = explode(',', $this->assignmentids)) {
            foreach ($ids as $id) {
                $assignmentids['assignmentid'][] = $id;
            }
        }

        // get submissions
        $submissionResult = $this->proxy->mdl_soapserver__getSubmissions($this->mykey, $assignmentids, $this->timemodified);
        //print_r($submissionResult);
        // check if there is a result
        $this->assertNotNull($submissionResult);

        // check if is only one record or more
        if(array_key_exists('id', $submissionResult['assignments']['assignment'])) {
            $assignment = $submissionResult['assignments']['assignment'];
        }
        else {
            $assignment = array_pop($submissionResult['assignments']['assignment']);
        }


        // check if node exists
        $this->assertTrue(isset($assignment['id']));
        $this->assertTrue(isset($assignment['submission']));

        // check if the result's course id is the same as the courseid in local var
        $this->assertTrue(in_array($assignment['id'], $assignmentids['assignmentid']));

        // check if is only one record or more
        if(array_key_exists('id', $assignment['submission'])) {
            $submission = $assignment['submission'];
        }
        else {
            $submission = array_pop($assignment['submission']);
        }



        // count number of nodes in user array
        $this->assertEqual(count($submission), 10);

        // check if node exists
        $this->assertTrue(array_key_exists('id',                $submission));
        $this->assertTrue(array_key_exists('userid',            $submission));
        $this->assertTrue(array_key_exists('timecreated',       $submission));
        $this->assertTrue(array_key_exists('timemodified',      $submission));
        $this->assertTrue(array_key_exists('numfiles',          $submission));
        $this->assertTrue(array_key_exists('data2',             $submission));
        $this->assertTrue(array_key_exists('grade',             $submission));
        $this->assertTrue(array_key_exists('submissioncomment', $submission));
        $this->assertTrue(array_key_exists('teacher',           $submission));
        $this->assertTrue(array_key_exists('timemarked',        $submission));
    }

    public function setUp() {
        global $CFG;

        require_once($CFG->dirroot.'/local/FAT/ws/lib/nusoap.php');

        include("local-vars.php");
        foreach ($testVals as $k1 => $v1) {
                $this->{$k1} = $v1;
        }
    }

    public function tearDown() {
        $this->soapproxy = null;
    }


}
?>