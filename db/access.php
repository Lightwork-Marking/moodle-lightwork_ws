<?php

// ----------------------------------------------------------------------------------------------
// LightWork INSTALL CODE BEGINS
//     the code between these comments should be copied into your /local/db/access.php
//     file and added near similar sections at the top
// ----------------------------------------------------------------------------------------------
//
// Capability definitions for the LightWork  extension.
//
// These capabilities are loaded into the database table when the module is
// installed or updated. Whenever the capability definitions are updated,
// the module version number should be bumped up.
//
// The system has four possible values for a capability:
// CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
//
// CAPABILITY NAMING CONVENTION
//
// It is important that capability names are unique. The naming convention
// for capabilities that are specific to local customisations should be...
//   local/<pluginname>:<capabilityname>
//
// ----------------------------------------------------------------------------------------------
// LightWork  INSTALL CODE ENDS
// ----------------------------------------------------------------------------------------------

$local_lightworkws_capabilities = array(

    'local/lightwork:managelightworkmarkers' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'editingteacher' => CAP_ALLOW
       )
    ),
    'local/lightwork:marklightworksubmissions' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSE,
        'legacy' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW
        )
    )

);

?>