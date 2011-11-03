<?php
/**
 * Unit tests for LW_Document.
 *
 * WARNING: This test case is NOT portable. Due to change of Moodle 2.0 File API, it is hard to automatically 
 * create test fixtures for this. So all the following test are based on manually setup data in local moodle instance.
 * 
 * @author wirianto
 * @version $Revision$
 * @license http://www.gnu.org/copleft/gpl.html GNU Public License
 */

if (!defined('MOODLE_INTERNAL')) {
 die('Direct access to this script is forbidden.');
}

global $CFG;
require_once('../../../config.php');
require_once($CFG->dirroot . '/local/lightwork/lib/lw_document.php');

class testlw_document extends UnitTestCase  {

    function test_document_links_without_file() {
        $doc = new LW_document();
        $this->assertEqual($doc->document_links(), '');
    }

    function test_document_links_with_single_file() {
        $doc = new LW_document(9, 22);
        $result = $doc->document_links();
        $this->assertPattern('/homework.xml/', $result);
    }

    function test_document_links_with_multiple_files() {
        $doc = new LW_document(9, 23);
        $result = $doc->document_links();
        $this->assertPattern('/sorter.js/', $result);
        $this->assertPattern('/svn_log.txt/', $result);
    }

    function test_document_links_with_nested_file() {
        $doc = new LW_document(9, 25);
        $result = $doc->document_links();
        $this->assertPattern('/rubric\/nested.sql/', $result);
        $this->assertPattern('/misc\/nested-sorter.js/', $result);
    }

    function test_dopcument_links_with_annotated_dir_and_single_file() {
        $doc = new LW_document(9, 24);
        $result = $doc->document_links();
        
        $this->assertPattern('/misc\/reg_nested.txt/', $result);
        
        $has_annotated = preg_match('/annotated/', $result);
        $this->assertEqual($has_annotated, 0);
    }

    function test_document_links_with_annotated_dir_and_multiple_nested_files() {
        $doc = new LW_document(9, 26);
        $result = $doc->document_links();
        
        $this->assertPattern('/top1\/nested1-1\/first.txt/', $result);
        $this->assertPattern('/top1\/nested1-2\/second.txt/', $result);
        $this->assertPattern('/top2\/nested\/third.txt/', $result);
        
        $has_annotated = preg_match('/annotated/', $result);
        $this->assertEqual($has_annotated, 0);
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
        $this->assertEqual(count($error), 0);
    }

    function test_document_metadata_download_single_file() {
        $doc = new LW_document(9, 22);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        
        $metas = $result['metadata'];
        $this->assertEqual(count($metas), 1);
        
        forEach($metas as $metadata) {
            $this->assertPattern('/homework.xml/', $metadata['metadatainformation']);
        }
    }

    function test_document_metadata_download_multiple_files() {
        $doc = new LW_document(9, 23);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $metas = $result['metadata'];
        $this->assertEqual(count($metas), 2);
        
        $this->assertPattern('/sorter.js/', $metas[0]['metadatainformation']);
        $this->assertPattern('/svn_log.txt/', $metas[1]['metadatainformation']);
    }

    function test_document_metadata_download_nested_multiple_files() {
        $doc = new LW_document(9, 25);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $metas = $result['metadata'];
        $this->assertEqual(count($metas), 2);
        
        $this->assertPattern('/rubric\/nested.sql/', $metas[0]['metadatainformation']);
        //debugging('metas[0]: ' . print_r($metas[0], true));
        $this->assertPattern('/misc\/nested-sorter.js/', $metas[1]['metadatainformation']);
        //debugging('metas[1]: ' . print_r($metas[1], true));
    }

    function test_document_metadata_download_annotated_single_file() {
        $doc = new LW_document(9, 24);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $metas = $result['metadata'];
        $this->assertEqual(count($metas), 2);
        
        $this->assertPattern('/annotated\/nested.sql/', $metas[0]['metadatainformation']);
        $this->assertPattern('/misc\/reg_nested.txt/', $metas[1]['metadatainformation']);
    }
    
    function test_document_metadata_download_nested_multiple_files_with_annotated_file() {
        $doc = new LW_document(9, 26);
        $result = $doc->document_metadata_download();

        $this->assertNotNull($result['metadata']);
        $metas = $result['metadata'];
        $this->assertEqual(count($metas), 4);
        
        $this->assertPattern('/top1\/nested1-1\/first.txt/', $metas[0]['metadatainformation']);
        $this->assertPattern('/top1\/nested1-2\/second.txt/', $metas[1]['metadatainformation']);
        $this->assertPattern('/annotated\/nested\/anno_file.txt/', $metas[2]['metadatainformation']);
        $this->assertPattern('/top2\/nested\/third.txt/', $metas[3]['metadatainformation']);
    }
/*
    function test_document_save_file() {
        $doc = new LW_document(9, 27);

        global $CFG, $DB;

        $filename = 'saved_file.txt';

        $doc->document_save_file('dummy data content', 'saved_file.txt', 201);

        $result = $DB->get_record("files", array("filename" => "saved_file.txt", "component" => "mod_resource"), '*', IGNORE_MULTIPLE);
        debugging('result: ' . print_r($result, true));
        $this->assertNotNull($result);
        $this->assertEqual($result->filename, 'saved_file.txt');
        $this->assertEqual($result->filepath, '/');
        $this->assertEqual($result->filesize, 18);
    }
*/
    function test_get_assignment_file_no_file() {
        $doc = new LW_document();

        $result = $doc->get_assignment_file('dummy.cs');
        $this->assertNotNull($result);
        $this->assertNotNull($result['error']);
        $error = $result['error'];
        $this->assertEqual($error['element'], 'Document');
        $this->assertEqual($error['id'], 0);
        $this->assertEqual($error['errorcode'], 'cannotopenfile');
    }

    function test_get_assignment_file_standard_file() {
        $doc = new LW_document(9, 25);

        $result = $doc->get_assignment_file('/misc/nested-sorter.js');
        //debugging('result: ' . var_export($result, true));
        $this->assertNotNull($result);
        $this->assertEqual($result['filename'], '/misc/nested-sorter.js');
        $this->assertEqual(strlen($result['data']), $result['filesize']);
    }
  
    function test_get_file_contextid_no_contextid() {
        $doc = new LW_document();
        
        $result = $doc->get_file_contextid('/', 'nofile.txt');
        $this->assertNull($result);
    }
    
    function test_get_file_contextid_standard_file() {
        $doc = new LW_document(9, 22);
        
        $result = $doc->get_file_contextid('/', 'homework.xml');
        $this->assertNotNull($result);
        $this->assertEqual($result, 91);
    }
    
    function test_get_file_contextid_nested_file() {
        $doc = new LW_document(9, 25);
        
        $result = $doc->get_file_contextid('/misc/', 'nested-sorter.js');
        $this->assertNotNull($result);
        $this->assertEqual($result, 121);
    }
}
?>
