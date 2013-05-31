<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

	
        $config['max_count_per_page'] = 10;	
        $config['duplicate_mail'] = "Email address already exist";
        $config['login_success']="0";
        $config['login_not_activated']="1";
        $config['login_banned']="2";
	$config['login_incorrect_password']="3";
        $config['register_success']="0";
        $config['invalid_params']="Invalid parameters";
        $config['incorrect_fbname_umail']="Incorrect facebook id or mail id";
        $config['register_duplicate_umail']="Duplicate items";
        $config['fail']="0";
        $config['success']="1";
        $config['login_incorrect_fname_umail']="3";
        $config['max_img_size']=2048; //2MB;
        $config['invalid_session']="Invalid session"; 
        $config['upload_path']="uploads";
        $config['failed']="Failed";
        $config['unknown_error']="unknow error";
        $config['not_exist']="does not exist";
        
        
        
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
        define('In_Store_Sales_1_ORDER_STATUS_ID', 43);
        define('STORE_1_WAK_IN_CUSTOMER_ID', 38163);