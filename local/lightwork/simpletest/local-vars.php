<?php
/**
 * Unit tests variables for LW
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 * @package LW
 */

global $CFG;

$testVals = array(
    'teacherID' => 7,
    'teacherCourses' => 3,
    'rubricID' => 1,
    'timemodified' => 1048740147,
    'activityid' => 123,
    'activitytype' => 111,    // bogus type so doesnt collide with standard ones
    'assignmentids' => '',
    'courseids' => '1,2,3',
    'coursesmodifiedsince' => 3,
    'xmltext' => '<and>...something...</and>',
    'WS_LOGIN_USERNAME' => 'teacher',
    'WS_LOGIN_PASSWORD' => 'teacher',
    'WS_WSDL_URL' =>  $CFG->wwwroot.'/local/FAT/ws/wsdl.php?test=1',
);

 ?>