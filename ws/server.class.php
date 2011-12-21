<?php // $Id: server.class.php,v 1.1 2008/04/16 17:18:08 ppollet Exp $

/**
 * Base class for web services server layer.
 *
 * @package Web Services
 * @version $Id: server.class.php,v 1.1 2008/04/16 17:18:08 ppollet Exp $
 * @author Open Knowledge Technologies - http://www.oktech.ca/
 * @author Justin Filip <jfilip@oktech.ca>
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');


define('DEBUG', true);
define('LW_PASSWORD_ENCRYPTION', true);


/**
 * The main server class.
 *
 * This class is broken up into three main sections of methods:
 * 1. Methods that perform actions related to client requests.
 * 2. Methods that handle server setup, incoming client requests, and returning a
 *    response to the client.
 * 3. Utility functions that perform functions such as datetime format conversion or
 *    replication of Moodle library functions in a manner safe for usage within this
 *    web services implementatation.
 *
 * The only methods that need to be extended in a child class are main() and any of
 * the service methods which need special transport-protocol specific handling of
 * input and / or output data.
 *
 *
 * @package Web Services
 * @author Open Knowledge Technologies - http://www.oktech.ca/
 * @author Justin Filip <jfilip@oktech.ca>
 */
class server {

    var $version        = 2006050800;
    var $sessiontimeout = 1800;  // 30 minutes.
    var $using17;

    /**
     * Constructor method.
     *
     * @uses $CFG
     * @param none
     * @return none
     */
    function server() {
        global $CFG;

        if (DEBUG) $this->debug_output("Server init...");

        $this->using17 = file_exists($CFG->libdir . '/accesslib.php');

        /// Check for any upgrades.
        if (empty($CFG->webservices_version)) {
            $this->upgrade(0);
        } else if ($CFG->webservices_version < $this->version) {
            $this->upgrade($CFG->webservices_version);
        }
    }


    /**
     * Performs an upgrade of the webservices system.
     *
     * @uses $CFG
     * @param int $oldversion The old version number we are upgrading from.
     * @return boolean True if successful, False otherwise.
     */
    function upgrade($oldversion) {
        global $CFG;

        if (DEBUG) $this->debug_output('Starting WS upgrade to version ' . $oldversion);

        ob_start();

        $return = true;

        if ($this->using17) {
            require_once($CFG->libdir . '/ddllib.php');

            $return = install_from_xmldb_file($CFG->dirroot . '/local/lightwork/ws/db/install.xml');

        } else {
            if ($oldversion < 2006050800) {
                if ($CFG->dbtype == 'mysql') {
                    if ($return) {
                        $return = modify_database('', "
                                CREATE TABLE `prefix_webservices_clients_allow` (
                                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                    `client` VARCHAR(15) NOT NULL DEFAULT '0.0.0.0',
                                    PRIMARY KEY `id` (`id`)
                                );
                            ");
                    }

                    if ($return) {
                        $return = modify_database('', "
                                CREATE TABLE `prefix_webservices_sessions` (
                                    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                                    `sessionbegin` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                                    `sessionend` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                                    `sessionkey` VARCHAR(32) NOT NULL DEFAULT '',
                                    `userid` INT(10) UNSIGNED NOT NULL DEFAULT 0,
                                    `verified` TINYINT(1) NOT NULL DEFAULT 0,
                                    PRIMARY KEY `id` (`id`)
                                );
                            ");
                    }
                } else if ($CFG->dbtype == 'postgres7') {
                    if ($return) {
                        $return = modify_database('', "
                                CREATE TABLE prefix_webservices_clients_allow (
                                    id SERIAL PRIMARY KEY,
                                    client VARCHAR(15) NOT NULL DEFAULT '0.0.0.0'
                                );
                            ");
                    }

                    if ($return) {
                        $return = modify_database('', "
                                CREATE TABLE prefix_webservices_sessions (
                                    id SERIAL PRIMARY KEY,
                                    sessionbegin INTEGER NOT NULL DEFAULT 0,
                                    sessionend INTEGER NOT NULL DEFAULT 0,
                                    sessionkey VARCHAR(32) NOT NULL DEFAULT '',
                                    userid INTEGER NOT NULL DEFAULT 0,
                                    verified INTEGER NOT NULL DEFAULT 0
                                );
                            ");
                    }
                }
            }
        }

        if (ob_get_length() && trim(ob_get_contents())) {
            /// Return an error with  the contents of the output buffer.
            $this->debug_output('Database output: ' . trim(ob_get_clean()));
        }

        if ($return) {
            set_config('webservices_version', $this->version);

            if (DEBUG) $this->debug_output('Upgraded from ' . $oldversion . ' to ' . $this->version);
        } else {
            if (DEBUG) $this->debug_output('ERROR: Could not upgrade to version ' . $this->version);
        }

        ob_end_clean();

        return $return;
    }


    /**
     * Initializes a connection to a new client by generating a random session
     * key to be used for communications with this specific client.
     *
     * @param int $client The client session record ID.
     * @return object A new request object containing information the client
     *                needs for further communication or an error object.
     */
    function init($client) {
    	global $DB;
        if (DEBUG) $this->debug_output('Running INIT for client: ' . $client);

        /// Add this client's database record.
        if (!$sess = $DB->get_record('webservices_sessions', array('id'=>$client))) {
            if (DEBUG) $this->debug_output('No session');
            return new soap_fault('Client', '', 'Could not get validated client session (' . $client . ').');
        }

        $sess->sessionbegin = time();
        $sess->sessionend   = 0;
        $sess->sessionkey   = $this->add_session_key();

        if (!$DB->update_record('webservices_sessions', $sess)) {
            if (DEBUG) $this->debug_output('No update');
            return new soap_fault('Client', '', 'Could not initialize client session (' . $client . ').');
        }

        if (DEBUG) {
            $this->debug_output('Login successful.');
        }

        return $sess->sessionkey;
    }


    /**
     * Creates a new session key.
     *
     * @param none
     * @return string A 32 character session key.
     */
    function add_session_key() {
        $time    = (string)time();
        $randstr = (string)random_string(10);

        /// XOR the current time and a random string.
        $str  = $time;
        $str ^= $randstr;

        /// Use the MD5 sum of this random 10 character string as the session key.
        return md5($str);
    }


    /**
     * Gets the session key from the database for a particular client.
     *
     * @param int $client The client session record ID.
     * @return string|boolean The client's current session key or False.
     */
    function get_session_key($client) {
    	global $DB;
        if (!$sess = $DB->get_record('webservices_sessions', array('id'=>$client,
        'sessionend'=>0, 'verified'=>1))) {
            if (DEBUG) $this->debug_output('No session exists for client: ' . $client);
            return false;
        }

        return $sess->sessionkey;
    }


    /**
     * Get the userid from the database for a particular client's session.
     *
     * @param int $client the client session record ID.
     * @return int|boolean The client's current userid or False.
     */
    function get_session_user($client) {
    	global $DB;
        if (!$sess = $DB->get_record('webservices_sessions', array('id'=>$client,
        'sessionend'=>0, 'verified'=>1))) {
            if (DEBUG) $this->debug_output('No session exists for client: ' . $client);
            return false;
        }

        return $sess->userid;
    }


    /**
     * Validate's that a client has an existing session.
     *
     * @param int $client The client session ID.
     * @param string $sesskey The client session key.
     * @return boolean True if the client is valid, False otherwise.
     */
    function validate_client($client = 0, $sesskey = '') {
    	global $DB;
        /// We can't validate a session that hasn't even been initialized yet.
        if (!$sess = $DB->get_record('webservices_sessions', array('id'=>$client,
        'sessionend'=>0, 'verified'=>1))) {
            return false;
        }

        /// Validate this session.
        if ($sesskey != $sess->sessionkey) {
            return false;
        }

        return true;
    }


    /**
     * Validates a client session to determine whether it has expired or not.
     *
     * @param int $client The client session record ID.
     * @param object $request The request object from the client.
     * @return boolean True if session is valid, False otherwise.
     */
    function validate_session($client, $request) {
    	global $DB;
        if (!$sess = $DB->get_record('webservices_sessions', array('id'=>$client))) {
            return false;
        }

        if ($sess->sessionkey != $request->get_sessionkey()) {
            if (DEBUG) $this->debug_output('Invalid session key for client (' . $client . ').');
        }

        if ((time() - $sess->sessionbegin) > $this->sessiontimeout) {
            if (DEBUG) $this->debug_output('Session (' . $client . ') expired.');
            return false;
        }

        return true;
    }


    /**
     * Validate's a client's request.
     *
     * @param object $request The request object from the client.
     * @return boolean True if the request is valid, False otherwise.
     */
    function validate_request($request) {
        return $request->validate();
    }


    /**
     * Validates a client's login request.
     *
     * @uses $CFG
     * @param array $input Input data from the client request object.
     * @return array Return data (client record ID and session key) to be
     *               converted into a specific data format for sending to the
     *               client.
     */
    function login($username, $password) {
        global $CFG, $DB;
        if (LW_PASSWORD_ENCRYPTION) {
            $lwpkey = new LW_pkey();
            $password = $lwpkey->decrypt(base64_decode($password));
        }
        if (!$user_exists = $DB->get_record('user', array('username'=>$username))) {
            error_log('LightWork login failed. Invalid username: '.$username);
            return new soap_fault('Client', '', 'Invalid username.');
        }
        $standardauthnames = array('cas','db','email','fc','imap','ldap','manual','mnet','nntp','pam','pop3','radius','shibboleth');
        $userauthmethod = $user_exists->auth;
        if (in_array($userauthmethod, $standardauthnames)) {
            // use standard Moodle authentication
            $user = authenticate_user_login($username, $password);
        } else {
            // use custom authentication method
            $incfile = $CFG->dirroot.'/local/lightwork/lib/lw_auth_'.$userauthmethod.'.php';
            if (file_exists($incfile)) {
                require_once($incfile);
                $classname = "LW_Auth_$userauthmethod";
                $lw_auth = new $classname();    // waikcookie is the name of the Waikato auth method
                $user = $lw_auth->login($username, $password);
            } else {
                error_log('No such authentication class: '.$incfile.' for user: '.$username);
                return new soap_fault('Client', '', 'Invalid authentication method.');
            }
        }


        if ($user === false) {
            error_log('LightWork login failed. Invalid username or password for user: '.$username);
            return new soap_fault('Client', '', 'LightWork login failed. Invalid username or password for user: '.$username);
        } else {
            /// Verify that an active session does not already exist for this user.
            $sql = "SELECT s.*
                        FROM {$CFG->prefix}webservices_sessions s
                        WHERE s.userid = {$user->id} AND
                              s.verified = 1 AND
                              s.sessionend != 0 AND
                              (" . time() . " - s.sessionbegin) < " . $this->sessiontimeout;

            if ($DB->record_exists_sql($sql)) {
                return new soap_fault('Client', '', 'A session already exists for this user (' . $user->id . ')');
            }

            /// Login valid, create a new session record for this client.
            $sess = new stdClass;
            $sess->userid   = $user->id;
            $sess->verified = true;
            $sess->id = $DB->insert_record('webservices_sessions', $sess);
            // is a new session so log the login event
            add_to_log(SITEID, 'user', 'login', "view.php?id=$user->id&course=".SITEID, 'lightwork', 0, $user->id);

            return $this->init($sess->id);
        }
    }


    /**
     * Logs a client out of the system by removing the valid flag from their
     * session record and any user ID that is assosciated with their particular
     * session.
     *
     * @param integer $client The client record ID.
     * @param string $sesskey The client session key.
     * @return boolean True if successfully logged out, false otherwise.
     */
    function logout($client, $sesskey) {
    	global $DB;
        if (!$this->validate_client($client, $sesskey)) {
            return new soap_fault('Client', '', 'Invalid client connection.');
        }

        if ($sess = $DB->get_record('webservices_sessions', array('id'=>$client,'sessionend'=>0, 'verified'=>1))) {
            add_to_log(SITEID, 'user', 'logout', "view.php?id=$sess->userid&course=".SITEID, 'lightwork', 0, $sess->userid);
            $sess->userid   = 0;
            $sess->verified = 0;
            if ($DB->update_record('webservices_sessions', $sess)) {
                return $this->client_disconnect($client);
            } else {
                return false;
            }
        }

        return false;
    }


    /**
     * Closes a client's session on the system.
     *
     * @param int $client The client session record ID.
     * @return boolean True on success, False otherwise.
     */
    function client_disconnect($client) {
    	global $DB;
        if ($sess = $DB->get_record('webservices_sessions', array('id'=>$client,'sessionend'=>0, 'verified'=>1))) {
            $sess->sessionend = time();

            if (!$DB->update_record('webservices_sessions', $sess)) {
                return false;
            } else {
                return true;
            }
        }

        return false;
    }





    /**
     * Initializes a new server and calls the dispatch function upon an
     * incoming client request.
     *
     * @todo Override in protocol-specific server subclass.
     * @param none
     * @return none
     */
    function main() {
        /// Override in protocol-specific server subclass.
    }


    /**
     * Sends an error response back to the client.
     *
     * @todo Override in protocol-specific server subclass.
     * @param string $msg The error message to return.
     * @return An error message string.
     */
    function error($msg) {
        return $msg;  /// Override in protocol-specific subclass.
    }

    /**
     * Do server-side debugging output (to file).
     *
     * @uses $CFG
     * @param mixed $output Debugging output.
     * @return none
     */
    function debug_output($output) {
        global $CFG;

        $fp = fopen($CFG->dataroot . '/debug.out', 'a');
        fwrite($fp, "[" . time() . "] $output\n");
        fflush($fp);
        fclose($fp);
    }

}

?>
