<?php

/**
 * Class for error handling.
 *
 * PHP version 5
 *
 * @package LW_Marker
 * @version $Revision$
 * @author  Yoke Chui <yokec@waikato.ac.nz>
 * @license http://www.gnu.org/copyleft/gpl.html GNU Public License
 *
 */


/**
 * Class for handling errors
 * @package LW_Error
 */

class LW_Error {

    public $errors = array();

    /**
     * Add error to array
     * @param   string   $element    Name of element ie. Course
     * @param   int      $elementid  Id of element
     * @param   string   $errorcode  Error Code
     */
    public function add_error($element, $elementid, $errorcode) {
        $this->errors['error'][] =  array(
                'element'     =>$element,
                'id'          =>$elementid,
                'errorcode'   =>$errorcode,
                'errormessage'=>get_string($errorcode, 'local')
        );
    }

    public function get_errors() {
        return $this->errors;
    }
}

?>