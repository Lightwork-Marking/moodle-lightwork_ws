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
     * Saves a file as a resource for the assignment
     * @param binary $data
     * @param string $docname
     * TODO catch and throw failure exceptions
     */
    function document_save_file($data, $docname, $docowner) {
    	global $DB, $CFG, $USER;
    	require_once("$CFG->dirroot/mod/resource/lib.php");
    	require_once("$CFG->dirroot/mod/resource/locallib.php");
    	require_once("$CFG->dirroot/course/lib.php");
    	
    	$USER->id = $docowner;
        $fs = get_file_storage();
        error_log('document_save_file $docname: '.$docname);
        
        
        $context = get_context_instance(CONTEXT_USER, $docowner);
        $draftfileinfo = array('contextid'=>$context->id,
                          'component'=>'user',
                          'filearea'=>'draft',
                          'itemid'=>$this->assignmentid,
                          'filepath'=>'/',
                          'filename'=>$docname,
                          'userid'=>$docowner,
                          'sortorder'=>0);
        // create file in draft area
        $draftfile = $fs->get_file($draftfileinfo['contextid'], 
                              $draftfileinfo['component'], 
                              $draftfileinfo['filearea'],         
                              $draftfileinfo['itemid'], 
                              $draftfileinfo['filepath'], 
                              $draftfileinfo['filename']);
        if ($draftfile){
          $draftfile->delete();	
        }
        $fs->create_file_from_string($draftfileinfo,$this->helper->sanitise_for_msoffice2007($docname, $data));
        
        // Find the resource for this file
        $cm = get_coursemodule_from_instance('assignment', $this->assignmentid, $this->courseid);
        $resourcemodules = get_coursemodules_in_course('resource', $this->courseid);        
        $resource = new object();
                
        foreach ($resourcemodules as $rm){
            if ($rm->section == $cm->section){
            	// check if resource already exists for this file
            	$resource = $DB->get_record('resource', array('id'=>$rm->instance,'name'=>$docname));          	
            	if ($resource){
            		$resource->coursemodule = $rm->id;
            	    error_log('document_save_file found resource: '.print_r($resource, TRUE));
            	    break;
            	}           	 
            }	
        }

        if ($resource->id){
        	// move file from draft to content
        	$resource->files = $draftfileinfo['itemid'];
        	$resource->instance = $resource->id;
        	resource_update_instance($resource, null);
        } else {
        	// create the rubric resource, course module and context
        	// should all be done within a database transaction
        	require_once("$CFG->dirroot/course/lib.php");
        	require_once("$CFG->dirroot/mod/resource/locallib.php");
        	$rcm = new object();
        	$rcm->course = clean_param($this->courseid, PARAM_INT);
        	$rcm->module = 14; // resource module
        	$rcm->instance = 0; // value set when resource is created
        	$rcm->section = $cm->section;
        	$rcm->score = 0;
        	$rcm->indent = 0;
        	$rcm->visible = true;
        	$rcm->visibleold = true;
        	$rcm->groupmode = 0;
        	$rcm->groupingid = 0;
        	$rcm->groupmembersonly = 0;
        	$rcm->completion = false;
        	$rcm->completionview = false;
        	$rcm->completionexpected = 0;
        	$rcm->availablefrom = 0;
        	$rcm->availableuntil = 0;
        	$rcm->showavailability = false;
        	$rcmid = add_course_module($rcm);
        	add_mod_to_section($rcm);
        	error_log('document_save_file $rcmid: '.$rcmid);
        	
        	$resource->coursemodule = $rcmid;
        	$resource->course = clean_param($this->courseid, PARAM_INT);
        	$resource->name = $docname;
        	$resource->introformat = 0;
        	$resource->tobemigrated = 0;
        	$resource->legacyfiles = 0;
        	$resource->display = 0;
        	$resource->filterfiles = 0;
        	$resource->revision = 1;
        	$resource->files = $draftfileinfo['itemid'];       	
        	$resourceid = resource_add_instance($resource, null);
        	error_log('document_save_file $resourceid: '.$resourceid);
        	
        	// creates context if it doesn't exist
        	$context = get_context_instance(CONTEXT_MODULE, $rcmid);
        }        
        rebuild_course_cache($this->courseid);
        
        
        // TODO throw exception and log error if file cannot be saved
        //$this->error->add_error('Document', '0', 'dircannotbecreated');
        //$this->error->add_error('Document', '0', 'cannotopenfile');
        
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