<?php
/**
 * Unit tests for LW_Marker
 *
 * @package LW_Marker
 * @version $Id$
 * @author Dean Stringer <deans@waikato.ac.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
 
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}


// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/lightwork/lib/lw_marker.php'); // Include the code to test
require_once($CFG->dirroot . '/local/lightwork/lib/lw_error.php'); // Need the lw_error class lib too

class lw_marker_test extends UnitTestCase {


/*
    function test_construct_empty() {
        // shouldnt be able to create one without a valid UID in the constructor
        $this->marker = new LW_Marker();
        $this->assertIsA($this->marker, 'LW_Marker');
        $this->assertEqual($this->marker->uid, null);
        $this->assertEqual($this->marker->useraccess, null);
        // -1 also no good
        $this->marker = new LW_Marker(-1);
        $this->assertEqual($this->marker->uid, null);
        $this->assertEqual($this->marker->useraccess, null);
    }

    function test_construct_byid() {
        $this->marker = new LW_Marker($this->teacherID);
        $this->assertEqual($this->marker->uid, $this->teacherID);
        $this->assertNotEqual($this->marker->useraccess, null);
    }

    function test_courses() {
        $this->marker = new LW_Marker($this->teacherID);
        $this->assertEqual($this->marker->uid, $this->teacherID);
        $this->assertEqual(count($this->marker->get_courses()), $this->teacherCourses);
        $this->assertEqual(count($this->marker->courses_modified_since(array(), $this->timemodified)), $this->coursesmodifiedsince);
    }

    function test_get_marking() {
        // get list of marking records for this user
        $this->marker = new LW_Marker($this->teacherID);
        $ids = explode(',', $this->assignmentids);
        $marking = $this->marker->get_marking($ids, 0);
        $this->assertTrue(count($marking));
        $markinglist = array_pop($marking);
        $markingitem = array_pop($markinglist['marking']);
        // count number of nodes in marking item array
        $this->assertEqual(count($markingitem), 8);
        // check if each exists
        $this->assertTrue(array_key_exists('id', $markingitem));
        $this->assertTrue(array_key_exists('marker', $markingitem));
        $this->assertTrue(array_key_exists('student', $markingitem));
        $this->assertTrue(array_key_exists('activity', $markingitem));
        $this->assertTrue(array_key_exists('activitytype', $markingitem));
        $this->assertTrue(array_key_exists('xmltext', $markingitem));
        $this->assertTrue(array_key_exists('statuscode', $markingitem));
        $this->assertTrue(array_key_exists('timemodified', $markingitem));

    }
    
    function test_save_marking() {
    	$this->marker = new LW_Marker($this->teacherID);
    	$marking = array( 'marker' => 201,
               			  'student' => 132,
    					  'rubric' => 1,
    	                  'activity' => 1,
    	                  'activitytype' => 1265851933,
                          'xmltext' => '<and>...something...</and>',
    	                  'statuscode' => 'MA',
    	                  'deleted' => 0,
    	                  'timemodified' => 0 );
    	$markings = array();
    	$markings['marking'] = $marking;
    	$markingreturn = $this->marker->save_marking($markings,false);    	
    	$returnmarkinglist = array_pop($markingreturn);
        $returnmarkingitem = array_pop($returnmarkinglist['markingresponse']);
    	$this->assertTrue(array_key_exists('marker', $returnmarkingitem));
        $this->assertTrue(array_key_exists('student', $returnmarkingitem));
        $this->assertTrue(array_key_exists('activity', $returnmarkingitem));
    	
    	$this->marker = new LW_Marker($this->teacherID);
    	$marking = array( 'marker' => 201,
               			  'student' => 132,
    					  'rubric' => 1,
    	                  'activity' => 1,
    	                  'activitytype' => 1265851933,
                          'xmltext' => '<and>...something...</and>',
    	                  'statuscode' => 'MA',
    	                  'deleted' => 1,
    	                  'timemodified' => 1 );
    	$markings = array();
    	$markings['marking'] = $marking;
    	$markingreturn = $this->marker->save_marking($markings,false);    	
    	$returnmarkinglist = array_pop($markingreturn);
        $returnmarkingitem = array_pop($returnmarkinglist['markingresponse']);
    	$this->assertTrue(array_key_exists('marker', $returnmarkingitem));
        $this->assertTrue(array_key_exists('student', $returnmarkingitem));
        $this->assertTrue(array_key_exists('activity', $returnmarkingitem));
        
        
        $this->marker = new LW_Marker($this->teacherID);
    	$marking = array( 'marker' => 'wrong marker',
               			  'student' => 132,
    					  'rubric' => 1,
    	                  'activity' => 1,
    	                  'activitytype' => 1265851933,
                          'xmltext' => '<and>...something...</and>',
    	                  'statuscode' => 'MA',
    	                  'deleted' => 0,
    	                  'timemodified' => 0 );
    	$markings = array();
    	$markings['marking'] = $marking;
    	$markingreturn = $this->marker->save_marking($markings,false);    	
    	$returnerrorlist = array_pop($markingreturn['errors']);
        $returnerror = array_pop($returnerrorlist);
    	$this->assertNotTrue(array_key_exists('element', $returnmarkingitem));
        $this->assertNotTrue(array_key_exists('id', $returnmarkingitem));
        $this->assertNotTrue(array_key_exists('errorcode', $returnmarkingitem));
    	
    }
*/
    
    public function test_assignment_submission_files() {
        $marker = new LW_Marker(3, LW_Common::STUDENT_MARKING);
        
        $result = $marker->assignment_submission_files(1, array(1));
        
        debugging("result: " . var_export($result, true));
    }

    public function setUp() {
        include("local-vars.php");
        foreach ($testVals as $k1 => $v1) {
            	$this->{$k1} = $v1;
        }
    }
    
    public function tearDown() {
        $this->marker = null;
    }

}
?>