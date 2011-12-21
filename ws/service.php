<?php // $Id: service.php,v 1.1 2008/04/16 17:18:08 ppollet Exp $

/**
 * Core web services interface script.
 *
 * All client traffic passes through this script.
 *
 * @package Web Services
 * @version $Id: service.php,v 1.1 2008/04/16 17:18:08 ppollet Exp $
 * @author Open Knowledge Technologies - http://www.oktech.ca/
 * @author Justin Filip <jfilip@oktech.ca>
 */


require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once('lib/nusoap.php');
require_once('lib/nusoapmime.php');

global $NUSOAP_SERVER, $debug;
// this flag is to turn on/off debugging information being send back to
// the client at the end of SOAP message
$debug = 0;
$type   = required_param('type', PARAM_ALPHANUM);
@raise_memory_limit("256M");


/// Include protocol-specific server class file.
$type = strtolower($type);
$incfile = $CFG->dirroot . '/local/lightwork/ws/mdl_' . $type . 'server.class.php';

if (file_exists($incfile)) {
    require_once($incfile);
} else {
    die;
}

/// Initialize the nusoap server
if (strstr($_SERVER['QUERY_STRING'], 'wsdl') === false) {
    $NUSOAP_SERVER = new nusoap_server_mime($CFG->wwwroot . '/local/lightwork/ws/wsdl.php');

    /// Initialize server object.
    $srvtype = 'mdl_' . $type . 'server';

    if (!$server = new $srvtype()) {
        error('Attempting to initialize unavaialble service.');
    }

    /// Process SOAP request.
    $HTTP_RAW_POST_DATA = isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '';

    $NUSOAP_SERVER->service($HTTP_RAW_POST_DATA);
}

?>