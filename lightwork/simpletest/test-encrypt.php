<?php
/**
 * Unit test for lw_pkey.php
 *
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

global $CFG;
// change the following path to be relative to where your local Moodle root dir is
require_once('../../../config.php');
require_once($CFG->dirroot . '/local/lightwork/lib/lw_pkey.php');

class lw_pkey_test extends UnitTestCase {

    private $lwpkey;
    private $message = 'original text to be encrypted';

    public function setUp() {
        $this->lwpkey = new LW_pkey();
    }

    public function tearDown() {
        $this->lwpkey = null;
    }

    public function test_encrypt_and_decrypt() {
        $encrypted = $this->lwpkey->encrypt($this->message);
        $this->assertNotNull($encrypted);

        $decrypted = $this->lwpkey->decrypt($encrypted);
        $this->assertEqual($decrypted, $this->message);
    }
}
?>
