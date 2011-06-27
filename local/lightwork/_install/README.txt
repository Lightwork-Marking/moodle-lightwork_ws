Lightwork Moodle deployment notes
=================================

The instructions in this file explain how to install the lightwork Moodle code
into your Moodle installation. The location of your Moodle directory is referred
to in these instructions as $moodledir. Please note that lightwork is still under 
development and any feedback on how to improve this process is very welcome and 
should be directed to the forums at http://lightwork.massey.ac.nz.

See also http://moodle.org/mod/data/view.php?d=13&rid=2743

Please read the installation FAQ at http://lightwork.massey.ac.nz/wiki/fat/Installation_FAQ
In particular please note that:

* Lightwork encrypts password data that is passed between the Lightwork client and Moodle.
  This means that your Moodle installation will need to be configured to generate a public and
  private key pair. See the section at the end of this file for instructions on how to do this.

Perform the following steps:

1) Extract the Lightwork Moodle zip file to a temporary location
2) Copy the 3rd party pear mail libraries in lib/pear/Mail to $moodledir/lib/pear/Mail. The included
   versions of these libraries are Mail_Mime-1.8.0 and Mail_mimeDecode-1.5.4.
3) Copy the directory lightwork to $moodledir/local
4) Insert the extra lines contained in mod-assignment-lib.patch into the view_intro() function and 
   delete_instance($assignment) function in the file $moodledir/mod/assignment/lib.php (Note that 
   the contents of this patch have changed in 2.4.6, so that if you have already done this for a 
   version prior to 2.4.6, then you will need to repeat the process) 
5) insert the extra lines contained in file.patch into /moodle/file.php. 
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
4) In Moodle, open the 'Administration-Networking-Settings' page. Set Networking to 'On' and save
   the changes. A Public key certificate should be generated and displayed on the page.
5) If the certificate is not generated correctly (the Public key text is not displayed) then it is possible
   that the country setting that is used to generate the certificate has not been set. Check this by searching
   for the word 'country' in the search box at the bottom of the Site Administration block.
   
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