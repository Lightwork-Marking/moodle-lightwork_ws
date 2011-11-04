Lightwork Moodle deployment notes
=================================

The instructions in this file explain how to install the lightwork Moodle code
into your Moodle installation. The location of your Moodle directory is referred
to in these instructions as $moodledir. Please note that lightwork is still under 
development and any feedback on how to improve this process is very welcome and 
should be directed to the forums at http://lightwork.massey.ac.nz.

See also http://moodle.org/mod/data/view.php?d=13&rid=2743

Please read the installation FAQ at http://lightwork.massey.ac.nz/wiki/fat/Installation_FAQ

Perform the following steps:

1) Configure your Moodle installation for Public key encryption as described below
2) Extract the Lightwork Moodle zip file to a temporary location
3) Copy the 3rd party pear mail libraries in lib-pear-Mail to $moodledir/lib/pear/Mail (you may have to
   create the Mail directory). The included versions of these libraries come from Mail_Mime-1.8.0 
   and Mail_mimeDecode-1.5.4.
4) Copy the directory lightwork and it's contents to $moodledir/local
5) Insert the extra lines contained in mod-assignment-lib.patch into the delete_instance($assignment) 
   function in the file $moodledir/mod/assignment/lib.php  
6) Run the notifications process in Moodle
7) Connect to your Moodle server using the Lightwork client (See separate instructions 
   in the client download)
    
Configuring your Moodle installation to use Public key encryption
=================================================================================
1) Verify that your PHP installation has OpenSSL support enabled. You can check this in the
   Moodle 'Administration-Server-PHP info' page. If it is not enabled, you will need to
   enable it (normally by removing the ';' at the beginning of the ;extension=php_openssl.dll line
   in your php.ini file).
2) On Windows, you will need create an environment variable OPENSSL_CONF that points to the php
   open ssl configuration file which is found in the extras/openssl folder within your PHP directory.
   Note that it is best to rename this file to openssl.conf to stop Windows from assuming that it is
   a 'speeddial' file.
3) Restart your computer to allow Windows to register this new variable
4) In Moodle, open the 'Site administration-Advanced features' page and set Networking to 'On'. 
   A 'Networking' link should be displayed and a Public key certificate should be generated and displayed 
   on the 'Networking-Settings' page.
   
If you still have problems, feel free to post questions to the Lightwork team in one of the forums
at http://lightwork.massey.ac.nz/projects/fat/boards
   
For further information on the Moodle open ssl installation see the links below:
http://docs.moodle.org/en/admin/environment/php_extension/openssl
http://moodle.org/mod/forum/discuss.php?d=110343
http://docs.moodle.org/en/Moodle_Network_FAQ#Moodle_doesn.27t_generate_any_keys_on_the_networking_pages

The Lightwork database tables
=============================
Installation of Lightwork will add the following tables to the Moodle database:

1) LW_MARKING_HISTORY
2) LW_MARKING
3) LW_RUBRIC
4) LW_MARKING_STATUS
5) LW_TEAM_MARKING
6) LW_TEAM_MARKING_HISTORY
7) LW_FEEDBACK

It is important to remember that Lightwork does not provide a backup and restore facility for these tables
It is assumed that the Moodle database administrator is already providing backup and restore
functionality for the core Moodle tables and that the Lightwork tables will be incorporated into this.