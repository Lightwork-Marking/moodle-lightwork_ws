<?php

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
require_once('../../../config.php');
require_once($CFG->dirroot . '/local/lightwork/ws/mdl_soapserver.class.php');

class mdl_soapserver_test extends UnitTestCase {
    
    public function setUp() {
        $this->server = new mdl_soapserver();
    }
    
    public function tearDown() {
        $this->server = null;
    }
    
    public function test_isMoodleInstanceCompatibleWithDecimalPointMarking() {
        $result = $this->server->isMoodleInstanceCompatibleWithDecimalPointMarking();
        $this->assertNoErrors();
    }
    
}

?>