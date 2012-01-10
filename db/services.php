<?php

/**
 * Web service for local lightwork module. This allow Lightwork Marking application
 * to interact with Moodle.
 *
 * @package    local-lightworkws
 * @copyright  2011 Massey University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// We defined the web service functions to install.
$functions = array(
        'local_lightworkws_get_service_version' => array(
                'classname'   => 'local_lightworkws_external',
                'methodname'  => 'get_service_version',
                'classpath'   => 'local/lightworkws/externallib.php',
                'description' => 'Return the version number of Lightwork WebService module',
                'type'        => 'read',
        )
);

// We define the services to install as pre-build services. A pre-build service is not editable by administrator.
$services = array(
        'Lightwork WebServices' => array(
                'functions' => array ('local_lightworkws_get_service_version'),
                'restrictedusers' => 0,
                'enabled' => 1,
        )
);
