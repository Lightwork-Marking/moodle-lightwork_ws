<?php
/**
 * Post installation and migration code.
 *
 * This file replaces:
 *   - STATEMENTS section in db/install.xml
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_lightwork_install() {
    global $CFG, $DB;
    // Insert marking status records
    $statusAL = new stdClass();
    $statusAL->statuscode       = 'AL'; 
    $statusAL->shortdescription = 'Allocated';
    $statusAL->longdescription  = 'Allocated';
    $DB->insert_record('lw_marking_status', $statusAL);
    
    $statusMA = new stdClass();
    $statusMA->statuscode       = 'MA';
    $statusMA->shortdescription = 'In Marking'; 
    $statusMA->longdescription  = 'In Marking';
    $DB->insert_record('lw_marking_status', $statusMA);
    
    $statusMF = new stdClass();
    $statusMF->statuscode       = 'MF';
    $statusMF->shortdescription = 'Marked';
    $statusMF->longdescription  = 'Marking Finished';
    $DB->insert_record('lw_marking_status', $statusMF);
    
    $statusRV = new stdClass();
    $statusRV->statuscode       = 'RV'; 
    $statusRV->shortdescription = 'In Review';
    $statusRV->longdescription  = 'In Review';
    $DB->insert_record('lw_marking_status', $statusRV);
    
    $statusRD = new stdClass();
    $statusRD->statuscode       = 'RD'; 
    $statusRD->shortdescription = 'Reviewed';
    $statusRD->longdescription  = 'Reviewed';
    $DB->insert_record('lw_marking_status', $statusRD);
    
    $statusRL = new stdClass();
    $statusRL->statuscode       = 'RL';
    $statusRL->shortdescription = 'Released';
    $statusRL->longdescription  = 'Released';
    $DB->insert_record('lw_marking_status', $statusRL);
    
    $statusED = new stdClass();
    $statusED->statuscode       = 'ED'; 
    $statusED->shortdescription = 'Inactive';
    $statusED->longdescription  = 'Inactive';
    $DB->insert_record('lw_marking_status', $statusED);
    
    $statusAR = new stdClass();
    $statusAR->statuscode       = 'AR'; 
    $statusAR->shortdescription = 'Archived';
    $statusAR->longdescription  = 'Archived';
    $DB->insert_record('lw_marking_status', $statusAR);
    
}

function xmldb_local_lightwork_install_recovery() {
    global $CFG, $DB;
    // TODO
    
}