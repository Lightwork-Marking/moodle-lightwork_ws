<?php  /// Lightwork configuration file 

unset($LW_CFG);
// This setting will allow additional student information to be displayed
// This feature will be made available in a future release
//$LW_CFG->user_info_category = "SMS Demographic Data";
// This setting allows allows decimal fraction marks to be used.
// In order to enable students to be graded with marks that include a decimal
// point and a maximum of 2 digits to the right of the decimal point, the
// following changes must be made:
// 1) The grade field in the assignment_submissions table must be changed to NUMERIC(6,2)
// 2) The value of the isDecimalPointMarkingEnabled flag must be modified to read TRUE
$LW_CFG->isDecimalPointMarkingEnabled = FALSE;

?>