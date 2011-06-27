<?php
/**
 * Unit tests for LW_Document.
 *
 * WARNING: This test case will create folder under <i>&lt;moodle_data&gt;</i> for testing purposes using
 * <b>98765</b> as the course id and <b>99999, 88888, 77777, 11111, 22222, and 33333</b> as assignment id.
 *
 * @author wirianto
 * @version $Revision$
 * @license http://www.gnu.org/copleft/gpl.html GNU Public License
 */

global $CFG;
require_once('../../../config.php');
require_once($CFG->dirroot . '/local/lightwork/lib/lw_document.php');

class testlw_document extends UnitTestCase  {

    function test_relative_path_default() {
        $doc = new LW_document();
        $this->assertEqual($doc->relative_path(), '0/lightwork_documents/assignment/0');
    }

    function test_relative_path_with_param() {
        $doc = new LW_document(98765, 99999);
        $this->assertEqual($doc->relative_path(), '98765/lightwork_documents/assignment/99999');
    }

    function test_rscandir_single_file() {
        global $CFG;
        $doc = new LW_document(98765, 99999);
        $this->assertEqual(1, count($doc->rscandir($CFG->dataroot.'/'.$doc->relative_path())));
    }

    function test_rscandir_multiple_files() {
        global $CFG;
        $doc = new LW_document(98765, 88888);
        $this->assertEqual(3, count($doc->rscandir($CFG->dataroot.'/'.$doc->relative_path())));
    }

    function test_rscandir_nested_multiple_files() {
        global $CFG;
        $doc = new LW_document(98765, 77777);
        $this->assertEqual(3, count($doc->rscandir($CFG->dataroot.'/'.$doc->relative_path())));
    }

    function test_rscandir_annotated_with_single_file() {
        global $CFG;
        $doc = new LW_document(98765, 11111);
        $this->assertEqual(1, count($doc->rscandir($CFG->dataroot.'/'.$doc->relative_path())));
    }

    function test_rscandir_annotated_with_multiple_files() {
        global $CFG;
        $doc = new LW_document(98765, 22222);
        $this->assertEqual(3, count($doc->rscandir($CFG->dataroot.'/'.$doc->relative_path())));
    }

    function test_rscandir_annotated_with_nested_multiple_files() {
        global $CFG;
        $doc = new LW_document(98765, 33333);
        $this->assertEqual(5, count($doc->rscandir($CFG->dataroot.'/'.$doc->relative_path())));
    }

    function test_document_links_without_file() {
        $doc = new LW_document();
        $this->assertEqual($doc->document_links(), '');
    }

    function test_document_links_with_single_file() {
        $doc = new LW_document(98765, 99999);
        $this->assertPattern('/test.txt/', $doc->document_links());
    }

    function test_document_links_with_multiple_files() {
        $doc = new LW_document(98765, 88888);
        $result = $doc->document_links();
        $has_test1 = preg_match('/test1\.txt/', $result);
        $has_test2 = preg_match('/test2\.doc/', $result);
        $has_test3 = preg_match('/test3\.pdf/', $result);
        $this->assertEqual($has_test1 . $has_test2 . $has_test3, '111');
    }

    function test_document_links_with_nested_files() {
        $doc = new LW_document(98765, 77777);
        $result = $doc->document_links();
        $has_test1 = preg_match('/test1\.txt/', $result);
        $has_test2 = preg_match('/12345\/test2\.doc/', $result);
        $has_test3 = preg_match('/67890\/test3\.pdf/', $result);
        $this->assertEqual($has_test1 . $has_test2 . $has_test3, '111');
    }

    function test_dopcument_links_with_annotated_dir_and_single_file() {
        $doc = new LW_document(98765, 11111);
        $result = $doc->document_links();
        $has_txt = preg_match('/one\.txt/', $result);
        $has_annotated = preg_match('/annotated/', $result);
        $this->assertEqual($has_txt . $has_annotated, '10');
    }

    function test_document_links_with_annotated_dir_and_multiple_files() {
        $doc = new LW_document(98765, 22222);
        $result = $doc->document_links();
        $has_txt = preg_match('/two\.txt/', $result);
        $has_pdf = preg_match('/duo\.pdf/', $result);
        $has_doc = preg_match('/deux\.doc/', $result);
        $no_annotated = preg_match('/annotated/', $result);

        $this->assertEqual($has_txt . $has_pdf . $has_doc . $no_annotated, '1110');
    }

    function test_document_links_with_annotated_dir_and_multiple_nested_files() {
        $doc = new LW_document(98765, 33333);
        $result = $doc->document_links();
        $has_txt = preg_match('/three\.txt/', $result);
        $has_pdf = preg_match('/pdf\/tri\.pdf/', $result);
        $has_build_xml = preg_match('/misc\/build\.xml/', $result);
        $has_main_java = preg_match('/misc\/src\/main\.java/', $result);
        $has_test_java = preg_match('/misc\/src\/test\.java/', $result);
        $no_annotated = preg_match('/annotated/', $result);

        $this->assertEqual($has_txt.$has_pdf.$has_build_xml.$has_main_java.$has_test_java.$no_annotated, '111110');
    }

    function test_document_metadata_download_no_file() {
        $doc = new LW_document();
        $result = $doc->document_metadata_download();
        $error = $doc->error->get_errors();

        $this->assertNotNull($result);
        $this->assertIsA($result, 'array');
        $this->assertEqual(count($result), 0);

        $this->assertNotNull($error);
        $this->assertIsA($error, 'array');
        $this->assertEqual(count($error), 1);
    }

    function test_document_metadata_download_single_file() {
        $doc = new LW_document(98765, 99999);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $this->assertEqual(count($result['metadata']), 1);
    }

    function test_document_metadata_download_multiple_files() {
        $doc = new LW_document(98765, 88888);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $this->assertEqual(count($result['metadata']), 3);
    }

    function test_document_metadata_download_nested_multiple_files() {
        $doc = new LW_document(98765, 77777);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $this->assertEqual(count($result['metadata']), 3);
    }

    function test_document_metadata_download_annotated_single_file() {
        $doc = new LW_document(98765, 11111);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $this->assertEqual(count($result['metadata']), 2);
    }

    function test_document_metadata_download_annotated_multiple_files() {
        $doc = new LW_document(98765, 22222);
        $result = $doc->document_metadata_download();


        $this->assertNotNull($result['metadata']);
        $this->assertEqual(count($result['metadata']), 6);
    }

    function test_document_metadata_download_annotated_nestsed_multiple_files() {
        $doc = new LW_document(98765, 33333);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $this->assertEqual(count($result['metadata']), 10);
    }

    function test_document_save_file() {
        $doc = new LW_document(98765, 55555);

        global $CFG;

        $filename = $CFG->dataroot . '/' . $doc->relative_path() . '/saved_file.txt';
        if (file_exists($filename)) trigger_error('File \'' . $filename . '\' already exist');

        $doc->document_save_file('dummy data content', 'saved_file.txt');

        $this->assertTrue(file_exists($filename));
        $this->assertEqual(filesize($filename), 18);

        @unlink($filename);
    }

    function test_document_save_file_nested_file() {
        $doc = new LW_document(98765, 55555);

        global $CFG;

        $filename = $CFG->dataroot . '/' . $doc->relative_path() . '/child/saved_file.txt';
        if (file_exists($filename)) trigger_error('File \'' . $filename . '\' already exist');

        $doc->document_save_file('dummy data content', 'child/saved_file.txt');

        $this->assertTrue(file_exists($filename));
        $this->assertEqual(filesize($filename), 18);

        @unlink($filename);
        @rmdir($CFG->dataroot . '/' . $doc->relative_path() . '/child');
    }

    function test_document_save_file_deep_nested_file() {
        $doc = new LW_document(98765, 55555);

        global $CFG;

        $filename = $CFG->dataroot . '/' . $doc->relative_path() . '/child/grandchild/saved_file.txt';
        if (file_exists($filename)) trigger_error('File \'' . $filename . '\' already exist');

        $doc->document_save_file('dummy data content', 'child/grandchild/saved_file.txt');

        $this->assertTrue(file_exists($filename));
        $this->assertEqual(filesize($filename), 18);

        @unlink($filename);
        @rmdir($CFG->dataroot.'/'.$doc->relative_path().'/child/grandchild');
        @rmdir($CFG->dataroot.'/'.$doc->relative_path().'/child');
    }

    function test_get_assignment_file_no_file() {
        $doc = new LW_document();

        $this->expectError();
        $result = $doc->get_assignment_file('dummy.cs');
        $this->assertNotNull($result);
        $this->assertNotNull($result['error']);
        $error = $result['error'];
        $this->assertEqual($error['element'], 'Document');
        $this->assertEqual($error['id'], 0);
        $this->assertEqual($error['errorcode'], 'cannotopenfile');
    }

    function test_get_assignment_file_standard_file() {
        $doc = new LW_document(98765, 99999);

        $result = $doc->get_assignment_file('test.txt');
        $this->assertNotNull($result);
        $this->assertEqual($result['filename'], 'test.txt');
        $this->assertEqual(strlen($result['data']), $result['filesize']);
    }

    function test_get_assignment_file_nested_file() {
        $doc = new LW_document(98765, 11111);

        $result = $doc->get_assignment_file('annotated/annotated_one.txt');
        $this->assertNotNull($result);
        $this->assertEqual($result['filename'], 'annotated/annotated_one.txt');
        $this->assertEqual(strlen($result['data']), $result['filesize']);
    }
    
    function setUp() {
        global $CFG;
        foreach (testlw_document::$TEST_DIRS as $dir) {
            @mkdir($CFG->dataroot . '/' . $dir);
        }
        foreach (testlw_document::$TEST_FILES as $file) {
            @touch($CFG->dataroot . '/' . $file);
        }
    }

    function tearDown() {
        global $CFG;
        foreach (testlw_document::$TEST_FILES as $file) {
            @unlink($CFG->dataroot . '/' . $file);
        }
        foreach (@array_reverse(testlw_document::$TEST_DIRS) as $dir) {
            @rmdir($CFG->dataroot . '/' . $dir);
        }
    }

    private static $TEST_DIRS = array (
        "98765",
        "98765/lightwork_documents",
        "98765/lightwork_documents/assignment",
        "98765/lightwork_documents/assignment/99999",
        "98765/lightwork_documents/assignment/88888",
        "98765/lightwork_documents/assignment/77777",
        "98765/lightwork_documents/assignment/77777/12345",
        "98765/lightwork_documents/assignment/77777/67890",
        "98765/lightwork_documents/assignment/11111",
        "98765/lightwork_documents/assignment/11111/annotated",
        "98765/lightwork_documents/assignment/22222",
        "98765/lightwork_documents/assignment/22222/annotated",
        "98765/lightwork_documents/assignment/33333",
        "98765/lightwork_documents/assignment/33333/pdf",
        "98765/lightwork_documents/assignment/33333/misc",
        "98765/lightwork_documents/assignment/33333/misc/src",
        "98765/lightwork_documents/assignment/33333/annotated",
        "98765/lightwork_documents/assignment/33333/annotated/pdf",
        "98765/lightwork_documents/assignment/33333/annotated/misc",
        "98765/lightwork_documents/assignment/33333/annotated/misc/src",
        "98765/lightwork_documents/assignment/55555",
    );

    private static $TEST_FILES = array(
        "98765/lightwork_documents/assignment/99999/test.txt",
        "98765/lightwork_documents/assignment/88888/test1.txt",
        "98765/lightwork_documents/assignment/88888/test2.doc",
        "98765/lightwork_documents/assignment/88888/test3.pdf",
        "98765/lightwork_documents/assignment/77777/test1.txt",
        "98765/lightwork_documents/assignment/77777/12345/test2.doc",
        "98765/lightwork_documents/assignment/77777/67890/test3.pdf",
        "98765/lightwork_documents/assignment/11111/one.txt",
        "98765/lightwork_documents/assignment/11111/annotated/annotated_one.txt",
        "98765/lightwork_documents/assignment/22222/two.txt",
        "98765/lightwork_documents/assignment/22222/duo.pdf",
        "98765/lightwork_documents/assignment/22222/deux.doc",
        "98765/lightwork_documents/assignment/22222/annotated/annotated_two.txt",
        "98765/lightwork_documents/assignment/22222/annotated/annotated_duo.pdf",
        "98765/lightwork_documents/assignment/22222/annotated/annotated_deux.doc",
        "98765/lightwork_documents/assignment/33333/three.txt",
        "98765/lightwork_documents/assignment/33333/pdf/tri.pdf",
        "98765/lightwork_documents/assignment/33333/misc/build.xml",
        "98765/lightwork_documents/assignment/33333/misc/src/main.java",
        "98765/lightwork_documents/assignment/33333/misc/src/test.java",
        "98765/lightwork_documents/assignment/33333/annotated/annotated_three.txt",
        "98765/lightwork_documents/assignment/33333/annotated/pdf/annotated_tri.pdf",
        "98765/lightwork_documents/assignment/33333/annotated/misc/annotated_build.xml",
        "98765/lightwork_documents/assignment/33333/annotated/misc/src/annotated_main.java",
        "98765/lightwork_documents/assignment/33333/annotated/misc/src/annotated_test.java",
    );
}
?>
