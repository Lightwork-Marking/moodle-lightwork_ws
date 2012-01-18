<?php

/**
 * Non-standard authentication class for web services services layer
 *
 * Used when the authentication method for the remote LW client user is an external custom
 * one which often means redirecting to a 3rd party host somewhere to retrieve a token of
 * some sort then return to Moodle to have that validated.
 *
 * A Moodle isntance can have multiple authentication methods enabled, but a user is
 * usually associated with a primary one. There are two approaches to hooking this
 * class to do that authentication where the method is custom...
 * 
 * 1. either get the primary method name from the user profile during the call to
 *    login() in the nusoap server.class.php and if it isnt a standard method
 *    instantiate a class like this one to handle the custom login.
 *
 * 2. modify the main custom auth/yourauthnamehere/ login methods to handle the
 *    redirection there, instantiating an instance of this class if neccessary
 *
 * @package Web Services - LW_Auth
 * @uses    curl library for HTTP posts
 * @author  Dean Stringer (deans@waikato.ac.nz)
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

class LW_Auth_yourauthname {

    public $username;

    // ------------------------------------------------------------------------------
    // set the custom POST fields, endpoint URL and cookiename that we required for
    // our custom/remote http authentication service
    // ------------------------------------------------------------------------------
    private static $cookiename = 'YourCookieName';
    private static $url = "https://path.to.your.authwebsite/cgi-bin/Login";
    private static $postdata = array(
            'http_ref' => '',
            'login' => 'Login',
    );


    /**
     * authentication a user
     *
     * Take a username and password and authenticate a user using the neccessary local mechanisms
     * and return true if successful, false if not
     *
     * @param   string      $username   username to authenticate
     * @param   string      $password   password to use for authentication
     * @return  bool        true if ok, false if not (default)
     * @access public
     */
    public function login($username, $password) {
        if (!function_exists('curl_init') ) {
            error_log(get_string('nocurl', 'mnet', '', NULL, true));
            return false;
        }
        self::$postdata['username'] = $username;
        self::$postdata['password'] = $password;
        $output = self::do_http_post(self::$url, self::$postdata);
        $response = self::parse_http_response($output);
        if ($cookieval = self::get_cookie($response, self::$cookiename)) {
            if (self::authenticate_token($cookieval)) {
                if ($user = get_complete_user_data('username', $username)) {
                    return $user;
                } else {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            return false; // fail if the cookie we're expecting isnt there
        }
    }


    /**
     * Use curl to do an HTTP post to the remote authentication method
     *
     * @param   string      $responsedata   from the HTTP post
     * @param   string      $cookiename     name of the cookie we're looking for
     * @return  string    value of the cookie, if found
     * @access  private
     */
    private function get_cookie($responsedata, $cookiename) {
        if (array_key_exists('Set-Cookie', $responsedata['header'])) {
            $cookie = $responsedata['header']['Set-Cookie'];
            if (preg_match('/'.$cookiename.'=([0-9a-f]+);/',$cookie, $matches)) {
                return $matches[1];
            } else {
                return false;
            }
        }
    }


    /**
     * Use curl to do an HTTP post to the remote authentication method
     *
     * @param   string  $url    the endpoint that does our http auth
     * @param   string  $postdata   the fields/data expected in the post
     * @return  string  text    output from the http call, headers and body
     * @access  private
     */
    private function do_http_post($url, $postdata) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);    // include headers in output
        curl_setopt($ch, CURLOPT_POST, 1);  // do a POST
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:')); // supress the "100 (Continue)" duplicate header
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }


    /**
     * Take a token and confirm it is valid and contains an authenticated username
     *
     * @param   $cookie   the cookie/token to validate
     * @return  bool      true if ok, false if not (default)
     * @access  private
     */
    private function authenticate_token($cookie='') {
        if (!empty($cookie)) {
            // Make sure is a waikcookie and not something else
            if (!ctype_xdigit($cookie)) {
                return false;
            } else {
                $wuser = exec("/path/to/your/cookie/checker $cookie");
            }
            //  If the Cookie has errored (i.e. badcookie) redirect to login page
            if (strtoupper($wuser) == 'BADCOOKIE') {
                return false;
            } else {
                $this->username = $wuser;
                return true;
            }
        }
        return false;
    }


    /**
     * Take an HTTP response string and parse into response code, headers and body
     *
     * @param   $this_response   raw string containing HTTP response
     * @return  array      (code, headers, body)
     * @access  public
     */
    public function parse_http_response($this_response) {
        // Split response into header and body sections
        list($response_headers, $response_body) = explode("\r\n\r\n", $this_response, 2);
        $response_header_lines = explode("\r\n", $response_headers);

        // First line of headers is the HTTP response code
        $http_response_line = array_shift($response_header_lines);
        if(preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@',$http_response_line, $matches)) {
            $response_code = $matches[1];
        }

        // put the rest of the headers in an array
        $response_header_array = array();
        foreach($response_header_lines as $header_line) {
            list($header,$value) = explode(': ', $header_line, 2);
            if (array_key_exists($header, $response_header_array)) {
                $response_header_array[$header] .= $value."\n";
            }  else {
                $response_header_array[$header] = $value."\n";
            }
        }
        return array("code" => $response_code, "header" => $response_header_array, "body" => $response_body);
    }

}

?>
