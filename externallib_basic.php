<?php

/**
 * External lib for Local Lightwork WebServices.
 *
 * @package    local-lightwork
 * @copyright  2011-2012 Massey University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_lightworkws_basic_external extends external_api {
    
    /**
    * Parameters for getServiceVersion method.
    * @return external_function_parameters
    */
    public static function get_service_version_parameters() {
        return new external_function_parameters(array());
    }
    
    public static function get_service_version_returns() {
        return new external_value(PARAM_TEXT, 'lightwork webservice version');
    }
    
    public static function get_service_version() {
        return "3.3.x" ;
    }
    
}