<?php

/**
 * local_lightwork upgrade code
 *
 * Sometimes, changes between versions involve
 * alterations to database structures and other
 * major things that may break installations.
 *
 * The upgrade function in this file will attempt
 * to perform all the necessary actions to upgrade
 * your older installation to the current version.
 *
 * If there's something it cannot do itself, it
 * will tell you what you need to do.
 *
 * The commands in here will all be database-neutral,
 * using the methods of database_manager class
 *
 * Please do not forget to use upgrade_set_timeout()
 * before any action that may take longer time to finish.
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_lightwork_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();
	   	
    if ($oldversion < 2010041600) {
        // Add table lw_team_marking
        $table1 = new XMLDBTable('lw_team_marking');
        $table1->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);
        $table1->addFieldInfo('marker', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'id');
        $table1->addFieldInfo('team', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'marker');
        $table1->addFieldInfo('xmltext', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null, 'team');
        $table1->addFieldInfo('activitytype', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'xmltext');
        $table1->addFieldInfo('activity', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'activitytype');
        $table1->addFieldInfo('statuscode', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null, null, null, 'activity');
        $table1->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'statuscode');
        $table1->addFieldInfo('rubric', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'timemodified');
        $table1->addFieldInfo('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'rubric');
        $table1->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $table1->addKeyInfo('marker', XMLDB_KEY_FOREIGN, array('marker'), 'user', array('id'));
        $table1->addKeyInfo('team', XMLDB_KEY_FOREIGN, array('team'), 'team', array('id'));
        $table1->addKeyInfo('activity', XMLDB_KEY_FOREIGN, array('activity'), 'assignment', array('id'));
        $table1->addKeyInfo('rubric', XMLDB_KEY_FOREIGN, array('rubric'), 'lw_rubric', array('lwid'));
        $table1->addKeyInfo('statuscode', XMLDB_KEY_FOREIGN, array('statuscode'), 'lw_marking_status', array('statuscode'));
        $table1->addIndexInfo('marker-team-activity-rubric', XMLDB_INDEX_UNIQUE, array('marker', 'team', 'activity', 'rubric'));            
        
        if (!$dbman->table_exists($table1)) {
            $dbman->create_table($table1);
        }
        
        // Add table lw_team_marking_history
        $table2 = new XMLDBTable('lw_team_marking_history');
        $table2->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);
        $table2->addFieldInfo('lwid', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, null, 'id');
        $table2->addFieldInfo('marker', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'lwid');
        $table2->addFieldInfo('team', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'marker');
        $table2->addFieldInfo('xmltext', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, null, null, 'team');
        $table2->addFieldInfo('activity', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'xmltext');
        $table2->addFieldInfo('statuscode', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null, null, null, 'activity');
        $table2->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'statuscode');
        $table2->addFieldInfo('rubric', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'timemodified');
        $table2->addFieldInfo('comment', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, null, null, 'rubric');
        $table2->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $table2->addKeyInfo('marker', XMLDB_KEY_FOREIGN, array('marker'), 'user', array('id'));
        $table2->addKeyInfo('team', XMLDB_KEY_FOREIGN, array('team'), 'team', array('id'));
        $table2->addKeyInfo('activity', XMLDB_KEY_FOREIGN, array('activity'), 'assignment', array('id'));
        $table2->addKeyInfo('rubric', XMLDB_KEY_FOREIGN, array('rubric'), 'lw_rubric', array('lwid'));
        $table2->addKeyInfo('statuscode', XMLDB_KEY_FOREIGN, array('statuscode'), 'lw_marking_status', array('statuscode'));
        $table2->addIndexInfo('lwid-marker-team-activity-rubric', XMLDB_INDEX_UNIQUE, array('lwid', 'marker', 'team', 'activity', 'rubric'));            
        
        if (!$dbman->table_exists($table2)) {
            $dbman->create_table($table2);
        }
        
        upgrade_plugin_savepoint(true, 2010041600, 'local', 'lightwork', false);
        
    }
        
    if ($oldversion < 2010111200) {
        // Add table lw_feedback
        $table1 = new XMLDBTable('lw_feedback');
        $table1->addFieldInfo('id', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null, null, null);
        $table1->addFieldInfo('marker', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'id');
        $table1->addFieldInfo('student', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'marker');
        $table1->addFieldInfo('xmltext', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null, null, null, 'student');
        $table1->addFieldInfo('activitytype', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'xmltext');
        $table1->addFieldInfo('activity', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'activitytype');
        $table1->addFieldInfo('statuscode', XMLDB_TYPE_CHAR, '2', null, XMLDB_NOTNULL, null, null, null, null, 'activity');
        $table1->addFieldInfo('timemodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'statuscode');
        $table1->addFieldInfo('rubric', XMLDB_TYPE_INTEGER, '3', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'timemodified');
        $table1->addFieldInfo('deleted', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, null, null, 'rubric');
        $table1->addKeyInfo('primary', XMLDB_KEY_PRIMARY, array('id'), null, null);
        $table1->addKeyInfo('marker', XMLDB_KEY_FOREIGN, array('marker'), 'user', array('id'));
        $table1->addKeyInfo('student', XMLDB_KEY_FOREIGN, array('student'), 'user', array('id'));
        $table1->addKeyInfo('activity', XMLDB_KEY_FOREIGN, array('activity'), 'assignment', array('id'));
        $table1->addKeyInfo('rubric', XMLDB_KEY_FOREIGN, array('rubric'), 'lw_rubric', array('lwid'));
        $table1->addKeyInfo('statuscode', XMLDB_KEY_FOREIGN, array('statuscode'), 'lw_marking_status', array('statuscode'));
        $table1->addIndexInfo('marker-student-activity-rubric', XMLDB_INDEX_UNIQUE, array('marker', 'student', 'activity', 'rubric'));
        $table1->addIndexInfo('timemodified', XMLDB_INDEX_NOTUNIQUE, array('timemodified'));            
        
        if (!$dbman->table_exists($table1)) {
            $dbman->create_table($table1);
        }

        // Add new index to lw_marking
        $table2 = new XMLDBTable('lw_marking');
        $index = new XMLDBIndex('timemodified');
        $index->setAttributes(XMLDB_INDEX_NOTUNIQUE, array('timemodified'));
        
        if (!$dbman->index_exists($table2, $index)){
            $dbman->add_index($table2, $index);
        }
            
        if (!record_exists('lw_marking_status', 'statuscode', 'AR')){
            $markingstatus = new object();
            $markingstatus->statuscode = 'AR';
            $markingstatus->shortdescription = 'Archived';
            $markingstatus->longdescription = 'Archived';
            $DB->insert_record('lw_marking_status',$markingstatus); 
        }
        
        upgrade_plugin_savepoint(true, 2010111200, 'local', 'lightwork', false);
    }
    
    return true;
}


?>