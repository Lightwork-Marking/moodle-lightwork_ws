<?php

/**
 *
 *
 * PHP version 5
 *
 * @package LW_Common
 * @version $Id: lw_common.php 2447 2010-04-21 23:04:16Z pcharsle $
 * @author  yyin
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

require_once('lw_document.php');

function get_members_from_team ($teamid) {
    global $CFG;
    return get_records_sql("SELECT id, student, timemodified".
                                 " FROM {$CFG->prefix}team_student ".
                                 " WHERE team = ".$teamid);
}

function is_in_markings($userid, $markings) {
    foreach ($markings as $marking) {
        if ($userid == $marking['student']) {
            return true;
        }
    }
    return false;
}

function get_teams_in_assignment($assignmentid) {
    global $CFG;
    return get_records_sql("SELECT id, assignment, name, membershipopen".
                                 " FROM {$CFG->prefix}team ".
                                 " WHERE assignment = ".$assignmentid);
}

function is_team_in_assignment($teamid, $assignmentid) {
    $teams = get_teams_in_assignment($assignmentid);
    if($teams) {
        foreach ($teams as $team) {
            if (($team -> id) == $teamid) {
                //error_log('team : '.$teamid.' is in assignment');
                return true;
            }
        }
    }
    //error_log('team is not in this assignment');
    return false;
}

function team_file_area_name($courseid, $assignmentid, $teamid) {
    global $CFG;
    return $courseid.'/'.$CFG->moddata.'/assignment/'.$assignmentid.'/'.'team/'.$teamid;
}

function team_member_file_area_name($courseid, $assignmentid, $userid) {
    global $CFG;
    return $courseid.'/'.$CFG->moddata.'/assignment/'.$assignmentid.'/'.$userid;
}

function get_student_in_team($studentid, $teamusers) {
    foreach ($teamusers as $user) {
        if ($user->student == $studentid) {
            return $user;
        }
    }
    return false;
}

?>