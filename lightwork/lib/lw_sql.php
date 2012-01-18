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

function get_assignment_resource_id($courseid, $assignmentid) {
    return "SELECT cm.id ".
            "FROM {course_modules} cm, {modules} md, {resource} rs ".
            "WHERE cm.course = {$courseid} ". 
            "AND cm.instance = rs.id ". 
            "AND md.name = 'resource' ". 
            "AND md.id = cm.module ".
            "AND cm.section = (".
              "SELECT cm.section ".
              "FROM {course_modules} cm ".
              "JOIN {modules} md ON md.id = cm.module ".
              "JOIN {assignment} a ON a.id = cm.instance ".
              "WHERE a.id = " . $assignmentid . " AND md.name = 'assignment')";
}

function find_file_contextid($courseid, $filepath, $filename) {
    return "SELECT f.contextid ". 
		   "FROM {files} f, {context} ctx, {course_modules} modl, {modules} md ".
           "WHERE f.contextid = ctx.id ".
           "AND ctx.instanceid = modl.id ".
           "AND modl.course = {$courseid} ".
           "AND modl.module = md.id ". 
           "AND md.name = 'resource' ".
           "AND f.component = 'mod_resource' ".
           "AND f.filearea = 'content' ".
           "AND f.filepath = '{$filepath}' ".
           "AND f.filename = '{$filename}'";
}

function get_assignment_resource($courseid, $assignmentid, $filename) {
    return "SELECT rs.* ".
            "FROM {course_modules} cm, {modules} md, {resource} rs ".
            "WHERE cm.course = {$courseid} ". 
            "AND cm.instance = rs.id ". 
            "AND md.name = 'resource' ". 
            "AND md.id = cm.module ".
            "AND rs.name = '{$filename}' ".
            "AND cm.section = (".
              "SELECT cm.section ".
              "FROM {course_modules} cm ".
              "JOIN {modules} md ON md.id = cm.module ".
              "JOIN {assignment} a ON a.id = cm.instance ".
              "WHERE a.id = " . $assignmentid . " AND md.name = 'assignment')";
}

?>
