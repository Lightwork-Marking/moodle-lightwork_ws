<?php

/**
 * Non-standard mymassey authentication class for Lightwork web services services layer
 *
 * Used for the Massey Stream Moodle
 *
 * @package Web Services - LW_Auth
 * @uses    curl library for HTTP posts
 * @author  Dean Stringer (deans@waikato.ac.nz), Paul Charsley (p.charsley@massey.ac.nz)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

/**
 * Non-standard authentication class for web services services layer.
 *
 * The only method which method which needs to be public is the login() one, and the only property which
 * should be public is username. All other properties and functions are added as required to be able
 * to authenticate a user using the neccessary local mechanisms and return the validated username
 * back to server.class.php
 *
 * @package Web Services - LW_Auth
 */

class LW_Auth_mymassey {

    /**
     * Take a username and password and authenticate a user using the neccessary local mechanisms
     * and return true if successful, false if not
     *
     * @param   string      $username   username to authenticate
     * @param   string      $password   password to use for authentication
     * @return  user|false  A {@link $USER} object or false if error
     * @access public
     */
    public function login($username, $password) {

        $user=$username.CHR(001).$_SERVER["REQUEST_URI"].CHR(001).$_SERVER["REMOTE_ADDR"];

        $result = self::authenticate($user, $password);

        if ($result === true) {
            if ($user = get_complete_user_data('username', $username)) {
                return $user;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Authenticates a user string and password against the MU_WinClient
     *
     * @param   $user     user string
     * @param   $password password
     * @return  bool      true if ok, false if not (default)
     * @access  private
     */
    private function authenticate($user='', $password='') {
        if (!empty($user) && !empty($password)) {
            //Check if MU_Client on hub1 is up if not use hub2
            $sock = @fsockopen("hub1.massey.ac.nz", 49509, $errno, $errstr, 1);
            if (!$sock) {
                $sock = @fsockopen("hub2.massey.ac.nz", 49509, $errno, $errstr, 1);
            }

            $ok = false;

            if ($sock) {
                $buf = fgets($sock, 1024);
                fputs($sock, "USER $user\r\n");
                $buf = fgets($sock, 1024);
                if (ereg("^500 ", $buf)) {
                    $buf = ereg_replace("^500 ", "", $buf);
                }
                else {
                    fputs($sock, "PASS $password\r\n");
                    $buf = fgets($sock, 1024);
                }
                if (ereg("^200 ", $buf)) {
                    $ok = true;
                }
                else {
                    fputs($sock, "PIN $password\r\n");
                    $buf = fgets($sock, 1024);
                }
                if (ereg("^200 ", $buf)) {
                    $ok = true;
                }
                fclose($sock);
            }

            if ($ok === true) {
                return true;
            } else {
                return false;
            }

        }
        return false;
    }
}

?>