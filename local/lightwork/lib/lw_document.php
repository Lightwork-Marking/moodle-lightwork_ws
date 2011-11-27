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
    const PATTERN = '/annotated/';
    const LW_FOLDER_NAME = 'lightwork_documents';
    const LW_TEAMS = 'teams';
    public $error;
    public $helper;
    private $relative_path;

    public function __construct($courseid=0, $assignmentid=0) {
        global $CFG;
        include_once($CFG->dirroot.'/local/lightwork/lib/lw_error.php');
        include_once($CFG->dirroot.'/local/lightwork/lib/lw_common.php');
        include_once($CFG->dirroot.'/local/lightwork/lib/lw_sql.php');
        include_once($CFG->dirroot.'/lib/filelib.php');
        $this->assignmentid = $assignmentid;
        $this->courseid = $courseid;
        $this->error = new LW_Error();
        $this->relative_path = $this->courseid.'/'.self::LW_FOLDER_NAME.'/assignment/'.$this->assignmentid;
        $this->helper = new LW_Common();
    }

    /*
     * Return a relative path to the folder that should contain the rubric documents
     *
     * @return  string  folder path
     *
    function relative_path() {
        return $this->relative_path;
    }

*/
    /**
     * Echo HTML links to all LW documents for this assignment if they exist. Since this method is for displaying
     * assignment related documents, it will not display any link to files that is stored under 'annotated' directory.
     * 'annotated' directory is reserve for synchronizing annotated marking documents between Marking Manager and
     * Marker.
     *
     * @return  string  html for links to the docs echoed to stdout
     */
    function document_links() {
        global $OUTPUT;
        $result = '';
        
        $files = $this->get_assignment_fileinfo(false);
        forEach($files as $file) {
            if ($file) {
                //debugging('filename: ' . $file->get_visible_name() . ' - file: ' . print_r($file, true) );
                $mimetype = mimeinfo('type', $file->get_visible_name());
                $result = $result . "<br><img src='" . $OUTPUT->pix_url(file_mimetype_icon($mimetype)) . "'/>&nbsp;";
                $result = $result . $OUTPUT->action_link($file->get_url(), $file->get_visible_name());
            }
        }

        return $result;
    }

    /**
     * Get the metadata information for all file resources belonging to an assignment.
     * This will include the annotated files by marker to be synchronized to marking manager.
     *
     * @return array assignment files metadata
     */
    function document_metadata_download($includeannotatedfiles = true) {
        global $DB;
        $rdocuments = array();
        
        $files = $this->get_assignment_fileinfo($includeannotatedfiles);
        forEach ($files as $file) {
            $relative_file_path = ltrim($file->filepath .$file->filename);
            $rdocuments['metadata'][] = array('metadatainformation' => $relative_file_path,
                                              'modificationtime'    => $file->timemodified,
                                              'contextid'           => $file->contextid);
        }
        
        return $rdocuments;
    }
    
    private function get_assignment_fileinfo($includeannotatedfiles = true) {
        global $DB;
        $result = array();
        $browser = get_file_browser();
        $sql = get_assignment_resource_id($this->courseid, $this->assignmentid);
        $resources = $DB->get_records_sql($sql);
        $contexts = array();
        forEach($resources as $id => $rm) {
            if ($id) {
                $context = get_context_instance(CONTEXT_MODULE, $id);
                if ($context) {
                    $contexts[] = $context->id;
                }
                error_log('get_assignment_fileinfo context: ' . var_export($context, true));
            }
        }
        
        return $this->load_fileinfo($contexts);
    }
    
    private function load_fileinfo($contexts, $includeannotatedfiles = true) {
        global $DB;
        
        if (empty($contexts)) {
            return array();
        }
        
        list($usql, $params) = $DB->get_in_or_equal($contexts);
        $sql = "select * from {files} ".
               "where component='mod_resource' ".
               "and filearea='content' ".
               "and filename <> '.' ".
               "and contextid $usql";
        
        if (!$includeannotatedfiles) {
            $sql = $sql . " and filename not like '/annotated/%'";
        }
        error_log('load_fileinfo sql: [' . $sql . '] and params: ' . var_export($params, true));
        
        return $DB->get_records_sql($sql, $params);
    }
    
    /*
     * Traverse down the folder structure and collect all child fileinfo.
    private function collect_children_fileinfo($folder, &$result, $includeannotatedfiles = true) {
        $children = $folder->get_children();
        error_log('children: ', var_export($children, true));
        
        forEach($children as $child) {
            $params = $child->get_params();
            if (!$includeannotatedfiles && 
                (preg_match(self::PATTERN, $params['filepath']) > 0 || (preg_match(self::PATTERN, $params['filename']) > 0))) 
            { 
                continue;
            }
            
            if ($child->is_directory()) {
                $result = $this->collect_children_fileinfo($child, $result, $includeannotatedfiles);
            } else {
                $result[] = $child;
            }
        }
        
        return $result;
    }
     */

    /**
     * Saves a file as a resource for the assignment
     * @param binary $data
     * @param string $docname
     */
    function document_save_file($data, $docname, $docowner) {
        global $CFG, $USER;
        require_once($CFG->dirroot."/mod/resource/lib.php");
        require_once($CFG->dirroot."/mod/resource/locallib.php");
        require_once($CFG->dirroot."/course/lib.php");
         
        $USER->id = $docowner;

        if ($docname[0] != '/') {
            $docname = '/' . $docname;
        }
        $path_parts = pathinfo($docname);
        if ($path_parts['dirname'] === '.') {
            $path_parts['dirname'] = '/';
        }
        if (substr($path_parts['dirname'], -1) != '/') {
            $path_parts['dirname'] = $path_parts['dirname'] . '/';
        }

        //LW_Common::lw_debug('document_save_file() - path_parts: ', $path_parts);
        $context = get_context_instance(CONTEXT_USER, $docowner);
        //LW_Common::lw_debug('document_save_file() - context: ', $context);
        $draftfileinfo = $this->construct_draftinfo($context, $path_parts, $docowner);
        //LW_Common::lw_debug('document_save_file() - draft fileinfo: ', $draftfileinfo);
        $this->save_file($draftfileinfo, $docname, $data);
        
        $this->update_resource($draftfileinfo, $docname);
    }
    
    /*
     * Update resource information of a particular file.
     */
    private function update_resource($draftfileinfo, $docname) {
        global $CFG, $DB;
        
        $cm = get_coursemodule_from_instance('assignment', $this->assignmentid, $this->courseid);
        // Find the resource for this file
        $sql = get_assignment_resource($this->courseid, $this->assignmentid, $docname);
        //LW_Common::lw_debug('sql: ', $sql);
        $resources = $DB->get_records_sql($sql);
        //LW_Common::lw_debug('update_resource() - resources: ', $resources);
        if (count($resources) > 1) {
            error_log('more then 1 resources were found for ' . $docname);
            $this->error->add_error('resource', key($resources), 'multipleresources');
            return;
        }
        $resource = current($resources);
        //LW_Common::lw_debug('update_resource() - current resource: ', $resource);
        
        // If exit update the resource with??
        if ($resource) {
            $resource->files = $draftfileinfo['itemid'];
            $resource->instance = $resource->id;
            $rcm = get_coursemodule_from_instance('resource', $resource->id, $this->courseid, $cm->section);
            $resource->coursemodule = $rcm->id;
            resource_update_instance($resource, null);
            $resourceid = $resource->id;
        } else { 
            // If resource does not exist, create one
            // create the rubric resource, course module and context
            // should all be done within a database transaction
            $rcmid = $this->create_course_module_resource($cm->section);
            $resourceid = $this->create_resource($docname, $draftfileinfo, $rcmid);
             
            // creates context if it doesn't exist
            $context = get_context_instance(CONTEXT_MODULE, $rcmid);
        }
    
        // If resource is not of annotation files, add to mdl_course_sections
        // for display
        if (! preg_match(self::PATTERN, $docname) > 0) {
            $mod = $DB->get_record('course_modules', array(
                    'instance' => $resourceid,
                    'course'   => $this->courseid,
                    'section'  => $cm->section
                     ));
            $this->add_resource_module_to_section($mod);
        }
        
        rebuild_course_cache($this->courseid, TRUE);
    }
    
    private function create_resource($docname, $draftfileinfo, $rcmid) {
        global $CFG;
        require_once($CFG->dirroot."/mod/resource/locallib.php");
        $resource = new stdClass();
        $resource->course = clean_param($this->courseid, PARAM_INT);
        $resource->name = $docname;
        $resource->intro = $docname;
        $resource->introformat = 0;
        $resource->tobemigrated = 0;
        $resource->legacyfiles = 0;
        $resource->display = 0;
        $resource->filterfiles = 0;
        $resource->revision = 1;
        $resource->coursemodule = $rcmid;
        $resource->files = $draftfileinfo['itemid'];
        $resourceid = resource_add_instance($resource, null);
        error_log('save_resource resourceid: '.$resourceid . ' - resource: ' . var_export($resource, TRUE));
        
        return $resourceid;
    }
    
    private function create_course_module_resource($section) {
        global $CFG, $DB;
        require_once($CFG->dirroot."/course/lib.php");
        
        $rcm = new stdClass();
        $rcm->course = clean_param($this->courseid, PARAM_INT);
        $rcm->module = $DB->get_field('modules', 'id', array('name'=>'resource')); // resource module
        $rcm->instance = 0;
        $rcm->section = $section;
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
        $rcm->id = $rcmid;
        // $this->add_resource_module_to_section($rcm);
        error_log('save_rcm rcmid: '.$rcmid . ' - rcm: ' . var_export($rcm, TRUE));
        
        return $rcmid;
    }
    
    function construct_draftinfo($context, $path_parts, $docowner) {
        // generate somewhat unique itemid by hashsing the fullpath filename
        // since md5 hash is too big too convert to integer, we just grab
        // the first 6 digits of the md5 hash.
        $hash = substr((md5($path_parts['dirname'] . $path_parts['basename'])), 0, 6);
        $itemid = abs(hexdec($hash));
        error_log('construct_draftinfo() - itemid: ' . $itemid);
        return array('contextid'   => $context->id,
                     'component'   => 'user',
                     'filearea'    => 'draft',
                     'itemid'      => $itemid,
                     'filepath'    => $path_parts['dirname'],
                     'filename'    => $path_parts['basename'],
                     'userid'      => $docowner,
                     'sortorder'   => 0);
    }
    
    function save_file($fileinfo, $docname, $data) {
        $fs = get_file_storage();
        $file = $fs->get_file($fileinfo['contextid'],
                              $fileinfo['component'],
                              $fileinfo['filearea'],
                              $fileinfo['itemid'],
                              $fileinfo['filepath'],
                              $fileinfo['filename']);
        if ($file) {
            try {
                //LW_Common::lw_debug('  deleting file: ' . var_export($file, true));
                $file->delete();
                //LW_Common::lw_debug('  delete successful');
            } catch (dml_exception $dex) {
                //LW_Common::lw_debug('save_draftfile dml_exception: '. $dex->getMessage());
                error_log('save_file dml_exception('.var_export($fileinfo, true).', '.$docname.'): '.$dex->getMessage());
            }
        }
        try {
            //LW_Common::lw_debug('save_file file_info: ', var_export($fileinfo, true));
            $fs->create_file_from_string($fileinfo,$this->helper->sanitise_for_msoffice2007($docname, $data));
        } catch (file_exception $fex) {
            //LW_Common::lw_debug('save_draftfile file_exception: '. $fex->getMessage());
            error_log('save_file file_exception('.var_export($fileinfo, true).', '.$docname.'): '.$fex->getMessage());
        }
    }
    
    /**
     * Get the resource file belong to an assignment based on the given filename.
     * 
     * @param string $filename
     * 
     * @return an associative array which contain the file and its metadata.
     * The content of the associative array are based on the following keys:
     * <ul>
     *   <li>filename - name of the file including its path</li>
     *   <li>filesize - the size of the file in byte</li>
     *   <li>timemodified - time the file was last modified</li>
     *   <li>fileref - file reference for XML SOAP attachment</li>
     *   <li>data    - the content of the file</li>
     *   <li>error   - error that might happened during getting the file</li>
     * </ul>
     * 
     */
    function get_assignment_file($filename) {
         
        $fs = get_file_storage();
         
        $path_parts = pathinfo($filename);
        if ($path_parts['dirname']==='.') {
            $path_parts['dirname']='/';
        }
        if ($path_parts['dirname'][0] !== '/') {
            $path_parts['dirname'] = '/' . $path_parts['dirname'];
        }
        if (substr($path_parts['dirname'], -1) !== '/') {
            $path_parts['dirname'] = $path_parts['dirname'] . '/';
        }
        
        //debugging('path_parts: ' . var_export($path_parts, true));
        $contextid = $this->get_file_contextid($path_parts['dirname'], $path_parts['basename']);
        //debugging($filename . ' has contextid ' . $contextid);
        $file = $fs->get_file($contextid,'mod_resource','content',0,$path_parts['dirname'],$path_parts['basename']);

        $result = array();

        if ($file) {
            $result['data'] = $file->get_content();
            $result['filename'] = $filename;
            $result['filesize'] = $file->get_filesize();
            $result['timemodified'] = $file->get_timemodified();
            //$result['fileref'] = LW_Common::CID . preg_replace('/\s/', '_', $filename);
            $result['fileref'] = LW_Common::CID . $filename;
        } else {
            $result['error'] = array(
                                   'element'     => 'Document',
                                   'id'          => 0,
                                   'errorcode'   => 'cannotopenfile',
                                   'errormessage'=> $filename
            );
             
        }
        error_log('get_assignment_file result: ' . print_r($result, TRUE));
        return $result;
    }
    
    /*
     * Function to get lightwork moodle file contextid based on given filepath
     * and filename. 
     */
    function get_file_contextid($filepath, $filename) {
        global $DB;
        error_log('get_file_context_id() - filepath: ' . $filepath . ' - filename: ' . $filename);
        $sql = find_file_contextid($this->courseid, $filepath, $filename);
        $params = array();
        $params['courseid'] = $this->courseid;
        $params['filepath'] = $filepath;
        $params['filename'] = $filename;
        $result = $DB->get_record_sql($sql, $params);
        error_log('get_file_contextid result: ' . var_export($result, true));
        if ($result) {
            return $result->contextid;
        }
    }

    /**
     * This is an alternative to add_mod_to_section in course/lib.php which has a defect
     * TODO report to Moodle error shown below
     * $DB->get_record("course_sections", array("course"=>$mod->course, "section"=>$mod->section)))
     * Enter description here ...
     * @param unknown_type $mod
     * @param unknown_type $beforemod
     */
    private function add_resource_module_to_section($mod) {
        global $DB;
        if ($section = $DB->get_record("course_sections", array("id"=>$mod->section))) {

            $section->sequence = trim($section->sequence);
            
            if (empty($section->sequence)) {
                $newsequence = "$mod->id";
            } else {
                $modarray = explode(",", $section->sequence);
                
                if (!in_array($mod->id, $modarray)) {
                    $newsequence = "$section->sequence,$mod->id";
                } else {
                    return;
                }
            }

            $DB->set_field("course_sections", "sequence", $newsequence, array("id"=>$section->id));
            return $section->id;     // Return course_sections ID that was used.
        } else {
            // TODO throw exception
        }
    }
}
?>