<?php

/**
 * External lib for Local Lightwork WebServices.
 *
 * @package    local-lightwork
 * @copyright  2011-2012 Massey University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_lightworkws_common_external extends external_api {
        
    protected static function errors_structure() {
        return new external_multiple_structure(
            new external_single_structure( array(
    				'element'	=> new external_value(PARAM_TEXT, 'error element'),
    				'id'		=> new external_value(PARAM_INT, 'error id'),
    				'errorcode' => new external_value(PARAM_TEXT, 'error code'),
    				'errormessage' => new external_value(PARAM_TEXT, 'error message')
                ), 'errorRecord'), 'list of errorRecords'
        );
    }
        
}