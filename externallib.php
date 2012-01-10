<?php

/**
 * External lib for Local Lightwork WebServices.
 *
 * @package    local-lightwork
 * @copyright  2011 Massey University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once($CFG->libdir . "/externallib.php");

class local_lightworkws_external extends external_api {
    
    /**
     * Parameters for getServiceVersion method.
     * @return external_function_parameters 
     */
    public static function get_service_version_parameters() {
        return new external_function_parameters(
            array()
        );
    }
    
    public static function get_service_version_returns() {
        return new external_value(PARAM_TEXT, 'version of Lightwork WebService module');
    }
    
    public static function get_service_version() {
        return "3.x" ;
    }
}