<?php


/**
 * @package LW_document
 *
 * Class to handle uploading and downloading/linking to all documents related to an
 * assignment.
 *
 */

class LW_document {

    const ANNOTATED = 'annotated';
    const LW_FOLDER_NAME = 'lightwork_documents';
    const LW_TEAMS = 'teams';
    public  $error;
    public $helper;
    private $relative_path;
    private $absolute_path;

    public function __construct($courseid=0, $assignmentid=0) {
        global $CFG;
        include_once($CFG->dirroot.'/local/lightwork/lib/lw_error.php');
        include_once($CFG->dirroot.'/local/lightwork/lib/lw_common.php');
        include_once($CFG->dirroot.'/lib/filelib.php');
        $this->assignmentid = $assignmentid;
        $this->courseid = $courseid;
        $this->error = new LW_Error();
        $this->relative_path = $this->courseid.'/'.self::LW_FOLDER_NAME.'/assignment/'.$this->assignmentid;
        $this->absolute_path = $CFG->dataroot.'/'.$this->relative_path;
        $this->helper = new LW_Common();
    }


    /**
     * Return a relative path to the folder that should contain the rubric documents
     *
     * @return  string  folder path
     */
    function relative_path() {
        return $this->relative_path;
    }


    /**
     * Echo HTML links to all LW documents for this assignment if they exist. Since this method is for displaying
     * assignment related documents, it will not display any link to files that is stored under 'annotated' directory.
     * 'annotated' directory is reserve for synchronizing annotated marking documents between Marking Manager and
     * Marker.
     *
     * @return  string  html for links to the docs echoed to stdout
     */
    function document_links() {
        global $CFG;
        if (!is_dir($this->absolute_path)) {
            return null;
        }

        $files = $this->strip_relative_base($this->rscandir($this->absolute_path));

        $result = '';
        foreach ($files as $file) {
            $icon = mimeinfo('icon', $file);
            $filearea = $this->relative_path;
            $fileurl = get_file_url("$filearea/$file");
            //$fileurl = $CFG->wwwroot.'/local/lightwork/lib/file.php/'.$this->relative_path.'/'.$file;
            $result = $result . "<br><img src=\"$CFG->pixpath/f/$icon\" class=\"icon\" alt=\"rubric\" />&nbsp;";
            $result = $result . link_to_popup_window ($fileurl, $file, $file, 500, 780, '', 'none', true);
        }
        return $result;
    }

    /*
     * Recursive scandir which get a list of all files starting with the given $base path.
     * This function will ignore the annotated directory if the flag $ignore is set to true.
     */
    function rscandir($base = '', &$data = array(), $depth = 0, $include_annotate = false) {
        $array = array_diff(scandir($base), array('.', '..'));
        
        foreach ($array as $value) {
            $newbase = $base.'/'.$value;
            if (is_dir($newbase)) {
                if (!$include_annotate && $depth == 0 && $value == self::ANNOTATED) continue;

                $data = $this->rscandir($newbase, $data, $depth++);
            }
            else {
                $data[] = $newbase;
            }
        }
        
        return $data;
    }

    /*
     * Method which accept an array of file paths and strip the parent directory from the path and only give
     * the filepath starting with the assignment folder that contains the files.
     */
    function strip_relative_base($data=array()) {
        $offset = strlen($this->absolute_path) + 1;

        $result = array();
        foreach ($data as $file) {
            $result[] = substr($file, $offset);
        }
        
        return $result;
    }

    /**
     * Saves the rubric pdf data stream to file in the dataroot directory
     *
     * @param   binary  $data   pdf content as a binary value
     * @return  bool    true if saved, false if not
     */
    function document_save_file($data, $docname) {
        $status = false;

        if (strpos($docname, "/") === false) {
            $relativepath = $this->relative_path;
        } else {
            $relativepath = $this->relative_path . '/' . dirname($docname);
        }

        if (! $basedir = make_upload_directory($relativepath)) {
            $this->error->add_error('Document', '0', 'dircannotbecreated');
            return false;    //Cannot be created, so error
        }
        
        $filename = $this->absolute_path.'/'.$docname;
        $file = fopen($filename, "w");
        if ($file) {
            $status = fwrite($file, $this->helper->sanitise_for_msoffice2007($filename, $data));
            fclose($file);
        } else {
            $this->error->add_error('Document', '0', 'cannotopenfile');
            return false;
        }
        return $status;
    }

    /**
     * Get the metadata information for all files belong to an assignment.
     * This will include the annotated files by marker to be synchronized to marking manager.
     *
     * @return array assignment files metadata
     */
    function document_metadata_download($includeannotatedfiles = true) {
        $rdocuments = array();
        if (!is_dir($this->absolute_path)) {
            $this->error->add_error('Document', '0', 'foldernotavailable');
            return $rdocuments;
        }

        $filelist = array();
        $filelist = $this->rscandir($this->absolute_path, $filelist, 0, $includeannotatedfiles);
        $relativefiles = $this->strip_relative_base($filelist);

        for ($i = 0; $i < count($filelist); $i++) {
            $mtime = filemtime($filelist[$i]);
            $rdocuments['metadata'][] = array('metadatainformation' => $relativefiles[$i],
                                              'modificationtime'    => $mtime);
        }
        return $rdocuments;
    }

    function get_assignment_file($filename) {
        $canonical_filename = $this->absolute_path.'/'.$filename;

        $result = array();

        if ($fh = fopen($canonical_filename, 'rb')) {
            $filesize = filesize($canonical_filename);
            if ($filesize > 0) {
                $data = fread($fh, $filesize);
                fclose($fh);

                $result['data'] = $data;
            } else {
                $result['data'] = '';
            }
            $result['filename'] = $filename;
            $result['filesize'] = $filesize;
            $result['timemodified'] = filemtime($canonical_filename);
            $result['fileref'] = LW_Common::CID . preg_replace('/\s/', '_', $filename);
        } else {
            $result['error'] = array(
                                   'element'     => 'Document',
                                   'id'          => 0,
                                   'errorcode'   => 'cannotopenfile',
                                   'errormessage'=> $filename
                               );
        }
        return $result;
    }
}
?>