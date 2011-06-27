<?php

/**
 * Unit tests for LW_Marker
 *
 * @package LW_Marker
 * @version $Id$
 * @author yyin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once('../../../config.php');
// Make sure the code being tested is accessible.
require_once($CFG->dirroot . '/local/lightwork/lib/lw_common.php'); // Include the code to test

class lw_common_test extends UnitTestCase {

    function testSanitiseXml() {
        $this->assertEqual($this->helper->sanitiseXml("simpletest"), "simpletest");
        $this->assertEqual($this->helper->sanitiseXml("simpletest o'reilly"), "simpletest o\'reilly");
        $this->assertEqual($this->helper->sanitiseXml("simpletest\x96test"), "simpletest-test");
    }

    public function setUp() {
        $this->helper = new LW_Common();
    }

    public function tearDown() {
        $this->helper = null;
    }

}

?>