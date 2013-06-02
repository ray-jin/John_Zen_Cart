<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

        define('FOPEN_READ',							'rb');
        define('FOPEN_READ_WRITE',						'r+b');
        define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
        define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
        define('FOPEN_WRITE_CREATE',					'ab');
        define('FOPEN_READ_WRITE_CREATE',				'a+b');
        define('FOPEN_WRITE_CREATE_STRICT',				'xb');
        define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

  
        
        
        define('DIR_FS_CATALOG', 'http://192.168.180.90:1300//john/');

        //the following path is a COMPLETE path to the /logs/ folder  eg: /var/www/vhost/accountname/public_html/store/logs ... and no trailing slash
        define('DIR_FS_LOGS', 'C:/xampp/htdocs/zen-cart/logs');

        define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
        define('DIR_FS_CATALOG_IMAGES', DIR_FS_CATALOG . 'images/');
        define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');
        define('DIR_FS_CATALOG_TEMPLATES', DIR_FS_CATALOG . 'includes/templates/');

        define('DIR_FS_EMAIL_TEMPLATES', DIR_FS_CATALOG . 'email/');
        define('DIR_FS_DOWNLOAD', DIR_FS_CATALOG . 'download/');
        define('IMAGE_SUFFIX_MEDIUM', '_MED');
        define('IMAGE_SUFFIX_LARGE', '_LRG');
        define('DIR_WS_IMAGES', 'images/');
      
        define('IS_ADMIN_FLAG',1);
 
/* End of file constants.php */
/* Location: ./application/config/constants.php */