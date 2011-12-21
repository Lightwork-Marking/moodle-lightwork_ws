<?php
/**
 * Unit tests for LW_Rubric
 *
 * @package LW_Rubric
 * @version $Id$
 * @author Dean Stringer <deans@waikato.ac.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.'); //  It must be included from a Moodle page
}
 
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/lightwork/lib/lw_rubric.php'); // Include the code to test
require_once($CFG->dirroot . '/local/lightwork/lib/lw_error.php'); // Need the lw_error class lib too
 
class lw_rubric_test extends UnitTestCase {


    function test_construct() {
        $this->assertIsA($this->rubric, 'LW_Rubric');
        $this->assertEqual($this->rubric->xmltext, '');
        $this->assertEqual($this->rubric->activity, 0);
    }


    function test_savenew() {
        // remove the test record if its there already    
        if ($this->rubric->get_byactivity($this->activityid,$this->activitytype)) {
        	$this->assertNotNull($this->rubric->delete());
        }
        // load up with some valid values first
        $this->rubric->xmltext = $this->xmltext;
        $this->rubric->activitytype = $this->activitytype;
        $this->rubric->activity = $this->activityid;
        $this->rubric->deleted = 0;
        // now check cant save without an activity id
        $this->rubric->activity = 0;
        $this->assertNull($this->rubric->save());
        // cant save without an xml val
        $this->rubric->xmltext = '';
        $this->rubric->activity = $this->activityid;
        $this->assertNull($this->rubric->save());
        // cant save without an activitytype val
        $this->rubric->xmltext = $this->xmltext;
        $this->rubric->activitytype = 0;
        $this->assertNull($this->rubric->save());
        // should be able to save now        
        $this->rubric->activitytype = $this->activitytype;
        $this->savedID = $this->rubric->save();
        $this->assertNotEqual($this->rubric->id, 0);
        $this->assertEqual($this->rubric->id, $this->savedID);
        $this->assertEqual($this->rubric->activity, $this->activityid);
    }


    function test_getbyid() {
        // shouldnt be able to fetch rubric unless id is integer of greater than 0 value
        $this->assertNull($this->rubric->get_byid(0));
        $this->assertNull($this->rubric->get_byid(-1));
        $this->assertNull($this->rubric->get_byid('fred'));
        $this->assertNull($this->rubric->get_byid(null));
        // fetch the rubric we saved earlier
        $this->rubric->get_byid($this->savedID);
        $this->assertNotEqual($this->rubric->id, null);
        $this->assertEqual($this->rubric->id, $this->savedID);
        $this->assertEqual($this->rubric->xmltext, $this->xmltext);
        $this->assertEqual($this->rubric->activity, $this->activityid);
    }

    function test_getbyactivity() {
        // shouldnt be able to fetch rubric unless have valid activity and type ids
        $this->assertNull($this->rubric->get_byactivity(0,-1));
        $this->assertNull($this->rubric->get_byactivity(0,0));
        $this->assertNull($this->rubric->get_byactivity($this->savedID,0));
        $this->assertNull($this->rubric->get_byactivity(0,$this->activitytype));
        $this->assertEqual($this->rubric->get_byactivity($this->activityid,$this->activitytype), $this->savedID);
    }

    function test_update() {
        $this->rubric->get_byid($this->savedID);
        $this->rubric->xmltext = '<a>B</a>';
        $newSavedID = $this->rubric->save();
        // must be the same saved ID
        $this->assertEqual($newSavedID, $this->savedID);
        $this->assertEqual($this->rubric->xmltext, '<a>B</a>');
    }

    function test_toHTML() {
        $this->rubric->get_byid($this->savedID);
        $this->assertEqual($this->rubric->to_html(), '<p>rubric</p>');
    }

    function test_toPDF() {
        $this->rubric->get_byid($this->savedID);
        $this->assertEqual($this->rubric->to_pdf(), 'PDF');
    }
  
    public function setUp() {
        $this->rubric = new LW_Rubric();
        include("local-vars.php");
        foreach ($testVals as $k1 => $v1) {
            $this->{$k1} = $v1;
        }
    }

    public function tearDown() {
        $this->rubric = null;
    }

}
?>