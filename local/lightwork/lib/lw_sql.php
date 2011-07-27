<?php

/**
 *
 *
 * PHP version 5
 *
 * @package LW_sql
 * @version 
 * @author  yyin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */
if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once('lw_common.php');

function marking_selection_sql($marking, $assignment) {
    return "marker = {$marking->marker} ".
           "AND student = {$marking->student} ".
           "AND rubric = {$marking->rubric} ".
           "AND activity = ".$assignment->id ;
}

function get_marking_deletion($marking, $assignment) {
    return array('markable'     =>$marking->student,
                 'marker'      =>$marking->marker,
                 'activity'    =>$assignment->id,
                 'rubric'      =>$marking->rubric);
}

function get_marker_participant_sql($marking, $context, $markingroles) {
    return "SELECT u.id FROM {user} u INNER JOIN ".
           "{role_assignments} ra on u.id=ra.userid ".
           "WHERE u.id = {$marking->marker} ".
           "AND ra.contextid = {$context->id} ".
           "AND ra.roleid IN ".$markingroles;
}

function get_student_participant_sql($userid, $contextid) {
    $studentrole = LW_Common::STUDENT_ROLE;
    return "SELECT u.id FROM {user} u INNER JOIN ".
           "{role_assignments} ra on u.id=ra.userid ".
           "WHERE u.id = {$userid} ".
           "AND ra.contextid = {$contextid} ".
           "AND ra.roleid = {$studentrole}";
    
}

?>