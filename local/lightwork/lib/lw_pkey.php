<?php

/**
 * Class for handling public-key encryption/decryption of LW payload data
 *
 * This class is used for one-way encryption only, ie we are being passed public
 * key encrypted data (the login password initially) from the LW client but are
 * not returning it. This means we do not need to issue public/private pairs to
 * both parties. The Moodle server maintains its private key, and the LW client
 * is provided the Moodle server's public key on request which it uses to encrypt
 * sensitive data it needs to send to Moodle
 *
 * @package LW_pkey
 * @uses    openssl library for pkey encryption/decryption
 * @author  Dean Stringer (deans@waikato.ac.nz)
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 */

class LW_pkey {

    public $publickey = '';
    private $privatekey = '';   // does not need to be public ;-)

    function __construct() {
        global $CFG;
        $this->publickey = self::read_public_key();
        $this->privatekey = self::read_private_key();
        // make sure we have a public/private key pair in mdl_config
        if (!$this->publickey || !$this->privatekey) {
            // generate a new pair and save them
            $keypair = self::generate_keypair();
            error_log('Generated new LightWork public-key pair');
            self::update_public_key($keypair['public']);
            self::update_private_key($keypair['private']);
        }
    }

    /**
     * encrypt a string using the public-key
     *
     * note: the encrypted string output is not constant for a given $data source
     * string, bashed my head against this one for awhile till I discovered that
     * the way the RSA algorithm works is it varies it in a way that it is still
     * reversible when decrypting
     *
     * @param   string  $data   the string to be encrypted
     * @return  string  $cryptext   the encrypted string
     */
    function encrypt($data) {
        $pubkey = $this->publickey;
        openssl_get_publickey($pubkey);
        $crypttext = ''; // is returned by ref
        openssl_public_encrypt($data, $crypttext, $pubkey);
        return $crypttext;
    }

    /**
     * decrypt a string using the private-key
     *
     * @param  string  $data   the encrypted string
     * @return string  $cryptext   the decrypted string (or null if not able to parse key)
     */
    function decrypt($data) {
        $passphrase = '';   // we are not securing the private key at this stage
        $privkey = self::read_private_key();
        if ($res = openssl_get_privatekey($privkey, $passphrase)) {
            // Private Key OK
            $crypttext = ''; // is returned by ref
            openssl_private_decrypt($data, $crypttext, $res);
            return $crypttext;
        } else {
            return null; // Private key NOT OK
        }
    }

    /**
     * read and return the public key content from mdl_config
     *
     * @return string   public key in PEM format
     */
    private function read_public_key() {
        global $CFG;
        if (isset($CFG->lightwork_pkey_public)) {
            return $CFG->lightwork_pkey_public;
        } else {
            return '';
        }
    }

    /**
     * update the public key content in mdl_config
     *
     * @param   $keyval     public key string
     */
    private function update_public_key($keyval) {
        if (set_config('lightwork_pkey_public', $keyval)) {
            $this->publickey = $keyval;
        }
    }

    /**
     * read and return the private key content from mdl_config
     *
     * @return string   public key in PEM format
     */
    private function read_private_key() {
        global $CFG;
        if (isset($CFG->lightwork_pkey_private)) {
            return $CFG->lightwork_pkey_private;
        } else {
            return '';
        }
    }

    /**
     * update the private key content in mdl_config
     *
     * @param   $keyval     private key string
     */
    private function update_private_key($keyval) {
        if (set_config('lightwork_pkey_private', $keyval)) {
            $this->privatekey = $keyval;
        }
    }

    /**
     * Generate public/private keys and store in the config table
     *
     * Use the distinguished name provided to create a CSR, and then sign that CSR
     * with the same credentials. Store the keypair you create in the config table.
     * If a distinguished name is not provided, create one
     *
     * @param   array  $dn  The distinguished name of the server
     * @return  string      The signature over that text
     * @author  Donal McMullan  donal@catalyst.net.nz
     */
    private function generate_keypair($dn = null, $days=365) {
        global $CFG;
        $host = strtolower($CFG->wwwroot);
        $host = ereg_replace("^http(s)?://",'',$host);
        $break = strpos($host.'/' , '/');
        $host   = substr($host, 0, $break);
        $organization = 'None';

        $keypair = array();

        if (is_null($dn)) {
            $dn = array(
                    "countryName" => 'NZ',
                    "stateOrProvinceName" => 'Waikato',
                    "localityName" => 'Massey',
                    "organizationName" => 'LightWork',
                    "organizationalUnitName" => 'Moodle',
                    "commonName" => $CFG->wwwroot,
                    "emailAddress" => $CFG->supportemail
            );
        }
        // ensure we remove trailing slashes
        $dn["commonName"] = preg_replace(':/$:', '', $dn["commonName"]);
        
        // Create a CSR so we can issue a key pair
        if (!empty($CFG->opensslcnf)){
            $new_key = openssl_pkey_new(array("config"=>$CFG->opensslcnf));
            $csr_rsc = openssl_csr_new($dn, $new_key, array("config"=>$CFG->opensslcnf,'private_key_bits'=>2048));
            $selfSignedCert = openssl_csr_sign($csr_rsc, null, $new_key, $days, array("config"=>$CFG->opensslcnf));	
        } else {
            $new_key = openssl_pkey_new();
            $csr_rsc = openssl_csr_new($dn, $new_key, array('private_key_bits'=>2048));
            $selfSignedCert = openssl_csr_sign($csr_rsc, null, $new_key, $days);
        }
        unset($csr_rsc); // Free up the resource

        // Export our self-signed certificate to a string for use as Public key
        openssl_x509_export($selfSignedCert, $keypair['public']);
        $pub_key = openssl_pkey_get_public($keypair['public']);
        openssl_x509_free($selfSignedCert);

        // Export the private key as a PEM encoded string
        if (!empty($CFG->opensslcnf)){
        	$export = openssl_pkey_export($new_key, $keypair['private'], null, array("config"=>$CFG->opensslcnf));
        } else {
            $export = openssl_pkey_export($new_key, $keypair['private'] );	
        }        
        openssl_pkey_free($new_key);
        unset($new_key); // Free up the resource

        return $keypair;
    }

}

?>
