<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Variable
 *
 * @author Administrator
 */
class Variable extends CI_Model {
    private $tbl_configuration_name="configuration";
    //put your code here
    function defineVariables(){
                
        // Load configuration variable fro database
            
            define('DB_PREFIX','');
            define('TABLE_ADDRESS_BOOK', DB_PREFIX . 'address_book');
            define('TABLE_ADMIN', DB_PREFIX . 'admin');
            define('TABLE_ADMIN_ACTIVITY_LOG', DB_PREFIX . 'admin_activity_log');
            define('TABLE_ADDRESS_FORMAT', DB_PREFIX . 'address_format');
            define('TABLE_AUTHORIZENET', DB_PREFIX . 'authorizenet');
            define('TABLE_BANNERS', DB_PREFIX . 'banners');
            define('TABLE_BANNERS_HISTORY', DB_PREFIX . 'banners_history');
            define('TABLE_CATEGORIES', DB_PREFIX . 'categories');
            define('TABLE_CATEGORIES_DESCRIPTION', DB_PREFIX . 'categories_description');
            define('TABLE_CONFIGURATION', DB_PREFIX . 'configuration');
            define('TABLE_CONFIGURATION_GROUP', DB_PREFIX . 'configuration_group');
            define('TABLE_COUNTER', DB_PREFIX . 'counter');
            define('TABLE_COUNTER_HISTORY', DB_PREFIX . 'counter_history');
            define('TABLE_COUNTRIES', DB_PREFIX . 'countries');
            define('TABLE_COUPON_GV_QUEUE', DB_PREFIX . 'coupon_gv_queue');
            define('TABLE_COUPON_GV_CUSTOMER', DB_PREFIX . 'coupon_gv_customer');
            define('TABLE_COUPON_EMAIL_TRACK', DB_PREFIX . 'coupon_email_track');
            define('TABLE_COUPON_REDEEM_TRACK', DB_PREFIX . 'coupon_redeem_track');
            define('TABLE_COUPON_RESTRICT', DB_PREFIX . 'coupon_restrict');
            define('TABLE_COUPONS', DB_PREFIX . 'coupons');
            define('TABLE_COUPONS_DESCRIPTION', DB_PREFIX . 'coupons_description');
            define('TABLE_CURRENCIES', DB_PREFIX . 'currencies');
            define('TABLE_CUSTOMERS', DB_PREFIX . 'customers');
            define('TABLE_CUSTOMERS_BASKET', DB_PREFIX . 'customers_basket');
            define('TABLE_CUSTOMERS_BASKET_ATTRIBUTES', DB_PREFIX . 'customers_basket_attributes');
            define('TABLE_CUSTOMERS_INFO', DB_PREFIX . 'customers_info');
            define('TABLE_DB_CACHE', DB_PREFIX . 'db_cache');
            define('TABLE_EMAIL_ARCHIVE', DB_PREFIX . 'email_archive');            
            define('TABLE_LANGUAGES', DB_PREFIX . 'languages');
            define('TABLE_META_TAGS_PRODUCTS_DESCRIPTION', DB_PREFIX . 'meta_tags_products_description');
            define('TABLE_METATAGS_CATEGORIES_DESCRIPTION', DB_PREFIX . 'meta_tags_categories_description');
            define('TABLE_NEWSLETTERS', DB_PREFIX . 'newsletters');
            define('TABLE_ORDERS', DB_PREFIX . 'orders');
            define('TABLE_ORDERS_PRODUCTS', DB_PREFIX . 'orders_products');
            define('TABLE_ORDERS_PRODUCTS_ATTRIBUTES', DB_PREFIX . 'orders_products_attributes');
            define('TABLE_ORDERS_PRODUCTS_DOWNLOAD', DB_PREFIX . 'orders_products_download');
            define('TABLE_ORDERS_STATUS', DB_PREFIX . 'orders_status');
            define('TABLE_ORDERS_STATUS_HISTORY', DB_PREFIX . 'orders_status_history');
            define('TABLE_ORDERS_TYPE', DB_PREFIX . 'orders_type');
            define('TABLE_ORDERS_TOTAL', DB_PREFIX . 'orders_total');
            define('TABLE_PRODUCTS', DB_PREFIX . 'products');
            define('TABLE_PRODUCT_TYPES', DB_PREFIX . 'product_types');
            define('TABLE_PRODUCT_TYPE_LAYOUT', DB_PREFIX . 'product_type_layout');
            define('TABLE_PRODUCT_TYPES_TO_CATEGORY', DB_PREFIX . 'product_types_to_category');
            define('TABLE_PRODUCTS_ATTRIBUTES', DB_PREFIX . 'products_attributes');
            define('TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD', DB_PREFIX . 'products_attributes_download');
            define('TABLE_PRODUCTS_DESCRIPTION', DB_PREFIX . 'products_description');
            define('TABLE_PRODUCTS_DISCOUNT_QUANTITY', DB_PREFIX . 'products_discount_quantity');
            define('TABLE_PRODUCTS_NOTIFICATIONS', DB_PREFIX . 'products_notifications');
            define('TABLE_PRODUCTS_OPTIONS', DB_PREFIX . 'products_options');
            define('TABLE_PRODUCTS_OPTIONS_VALUES', DB_PREFIX . 'products_options_values_bk');
            define('TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS', DB_PREFIX . 'products_options_values_to_products_options');
            define('TABLE_PRODUCTS_OPTIONS_TYPES', DB_PREFIX . 'products_options_types');
            define('TABLE_PRODUCTS_TO_CATEGORIES', DB_PREFIX . 'products_to_categories');
            define('TABLE_ZONES', DB_PREFIX . 'zones');
            define('ORDER_PREFIX', 'ECI');
            define('PROCESSING_ORDER_STATUS_ID', 1);
            define('In_STORE_SALES_1_ORDER_STATUS_ID', 53);
            define('In_STORE_ORDER_1_ORDER_STATUS_ID', 54);
            define('STORE_1_WAK_IN_CUSTOMER_ID', 38965);
            define('FILENAME_DEFAULT', 'index');
            define('EMAIL_DISCLAIMER', 'This email address was given to us by you or by one of our customers. If you feel that you have received this email in error, please send an email to %s ');
            define('EMAIL_SPAM_DISCLAIMER','This email is sent in accordance with the US CAN-SPAM Law in effect 01/01/2004. Removal requests can be sent to this address and will be honored and respected.');
            define('EMAIL_FOOTER_COPYRIGHT','Copyright (c) ' . date('Y') . ' Powered by <a href="http://www.zen-cart.com" target="_blank">Zen Cart</a>');
            

            $configuration=array(
                "QUANTITY_DECIMALS",
                "STORE_PRODUCT_TAX_BASIS",
                "DEFAULT_ORDERS_STATUS_ID",
                "DEFAULT_CURRENCY",                
                "MODULE_PAYMENT_AUTHORIZENET_AIM",
                "CURL_PROXY_REQUIRED",
                "STORE_NAME",
                "STORE_OWNER",
                "SEND_EMAILS",
                "CURRENCIES_TRANSLATIONS",
                "ADMIN_EXTRA_EMAIL_FORMAT",
                "CC_ENABLED",
                "Store_1_Restock_Days",
            );
            foreach ($configuration as $cfgKey){
                    $this->db->like('configuration_key', $cfgKey); // 0 : available
                    $this->db->select('configuration_value');
                    $this->db->select('configuration_key');
                    
                    $query = $this->db->get($this->tbl_configuration_name);
                     
                    foreach ($query->result() as $row)
                    {
                        define($row->configuration_key, $row->configuration_value);
                    }
                    
            }
            
            
            if (MODULE_PAYMENT_AUTHORIZENET_AIM_STATUS == 'True') {
                define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_DESCRIPTION', '<a target="_blank" href="https://account.authorize.net/">Authorize.net Merchant Login</a>' . (MODULE_PAYMENT_AUTHORIZENET_AIM_TESTMODE != 'Production' ? '<br /><br />Testing Info:<br /><b>Automatic Approval Credit Card Numbers:</b><br />Visa#: 4007000000027<br />MC#: 5424000000000015<br />Discover#: 6011000000000012<br />AMEX#: 370000000000002<br /><br /><b>Note:</b> These credit card numbers will return a decline in live mode, and an approval in test mode.  Any future date can be used for the expiration date and any 3 or 4 (AMEX) digit number can be used for the CVV Code.<br /><br /><b>Automatic Decline Credit Card Number:</b><br /><br />Card #: 4222222222222<br /><br />This card number can be used to receive decline notices for testing purposes.<br /><br />' : ''));
            } else { 
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_DESCRIPTION', '<a target="_blank" href="http://reseller.authorize.net/application.asp?id=131345">Click Here to Sign Up for an Account</a><br /><br /><a target="_blank" href="https://account.authorize.net/">Authorize.net Merchant Area</a><br /><br /><strong>Requirements:</strong><br /><hr />*<strong>Authorize.net Account</strong> (see link above to signup)<br />*<strong>CURL is required </strong>and MUST be compiled with SSL support into PHP by your hosting company<br />*<strong>Authorize.net username and transaction key</strong> available from your Merchant Area');
            }
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_ERROR_CURL_NOT_FOUND', 'CURL functions not found - required for Authorize.net AIM payment module');

            // Catalog Items
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CATALOG_TITLE', 'Credit Card');  // Payment option title as displayed to the customer
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_TYPE', 'Credit Card Type:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_OWNER', 'Cardholder Name:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_NUMBER', 'Credit Card Number:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_EXPIRES', 'Expiry Date:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CVV', 'CVV Number:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_POPUP_CVV_LINK', 'What\'s this?');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_JS_CC_OWNER', '* The owner\'s name of the credit card must be at least ' . 11 . ' characters.\n');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_JS_CC_NUMBER', '* The credit card number must be at least ' . 3 . ' characters.\n');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_JS_CC_CVV', '* The 3 or 4 digit CVV number must be entered from the back of the credit card.\n');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_DECLINED_MESSAGE', 'Your credit card could not be authorized for this reason. Please correct the information and try again or contact us for further assistance.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_ERROR', 'Credit Card Error!');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_AUTHENTICITY_WARNING', 'WARNING: Security hash problem. Please contact store-owner immediately. Your order has *not* been fully authorized.');

            define('TEXT_CCVAL_ERROR_INVALID_DATE', 'The expiration date entered for the credit card is invalid. Please check the date and try again.');
            define('TEXT_CCVAL_ERROR_INVALID_NUMBER', 'The credit card number entered is invalid. Please check the number and try again.');
            define('TEXT_CCVAL_ERROR_UNKNOWN_CARD', 'The credit card number starting with %s was not entered correctly, or we do not accept that kind of card. Please try again or use another credit card.');


            define('TEXT_CC_ENABLED_VISA','Visa');
            define('TEXT_CC_ENABLED_MC','MC');
            define('TEXT_CC_ENABLED_AMEX','AmEx');
            define('TEXT_CC_ENABLED_DINERS_CLUB','Diners Club');
            define('TEXT_CC_ENABLED_DISCOVER','Discover');
            define('TEXT_CC_ENABLED_JCB','JCB');
            define('TEXT_CC_ENABLED_AUSTRALIAN_BANKCARD','Australian Bankcard');
            define('TEXT_CC_ENABLED_SOLO','Solo');
            define('TEXT_CC_ENABLED_SWITCH','Switch');
            define('TEXT_CC_ENABLED_MAESTRO','Maestro');


            // admin tools:
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_BUTTON_TEXT', 'Do Refund');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_REFUND_CONFIRM_ERROR', 'Error: You requested to do a refund but did not check the Confirmation box.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_INVALID_REFUND_AMOUNT', 'Error: You requested a refund but entered an invalid amount.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CC_NUM_REQUIRED_ERROR', 'Error: You requested a refund but didn\'t enter the last 4 digits of the Credit Card number.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_REFUND_INITIATED', 'Refund Initiated. Transaction ID: %s - Auth Code: %s');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CAPTURE_CONFIRM_ERROR', 'Error: You requested to do a capture but did not check the Confirmation box.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_BUTTON_TEXT', 'Do Capture');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_INVALID_CAPTURE_AMOUNT', 'Error: You requested a capture but need to enter an amount.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_TRANS_ID_REQUIRED_ERROR', 'Error: You need to specify a Transaction ID.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CAPT_INITIATED', 'Funds Capture initiated. Amount: %s.  Transaction ID: %s - Auth Code: %s');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_BUTTON_TEXT', 'Do Void');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_VOID_CONFIRM_ERROR', 'Error: You requested a Void but did not check the Confirmation box.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_VOID_INITIATED', 'Void Initiated. Transaction ID: %s - Auth Code: %s ');


            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_TITLE', '<strong>Refund Transactions</strong>');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND', 'You may refund money to the customer\'s credit card here:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_REFUND_CONFIRM_CHECK', 'Check this box to confirm your intent: ');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_AMOUNT_TEXT', 'Enter the amount you wish to refund');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_CC_NUM_TEXT', 'Enter the last 4 digits of the Credit Card you are refunding.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_TRANS_ID', 'Enter the original Transaction ID:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_TEXT_COMMENTS', 'Notes (will show on Order History):');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_DEFAULT_MESSAGE', 'Refund Issued');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_REFUND_SUFFIX', 'You may refund an order up to the amount already captured. You must supply the last 4 digits of the credit card number used on the initial order.<br />Refunds must be issued within 120 days of the original transaction date.');

            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_TITLE', '<strong>Capture Transactions</strong>');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE', 'You may capture previously-authorized funds here:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_AMOUNT_TEXT', 'Enter the amount to Capture: ');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CAPTURE_CONFIRM_CHECK', 'Check this box to confirm your intent: ');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_TRANS_ID', 'Enter the original Transaction ID: ');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_TEXT_COMMENTS', 'Notes (will show on Order History):');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_DEFAULT_MESSAGE', 'Settled previously-authorized funds.');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_CAPTURE_SUFFIX', 'Captures must be performed within 30 days of the original authorization. You may only capture an order ONCE. <br />Please be sure the amount specified is correct.<br />If you leave the amount blank, the original amount will be used instead.');

            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_TITLE', '<strong>Voiding Transactions</strong>');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID', 'You may void a transaction which has not yet been settled:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_VOID_CONFIRM_CHECK', 'Check this box to confirm your intent:');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_TEXT_COMMENTS', 'Notes (will show on Order History):');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_DEFAULT_MESSAGE', 'Transaction Cancelled');
            define('MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_SUFFIX', 'Voids must be completed before the original transaction is settled in the daily batch.');
            
            define('HTTP_SERVER', 'http://localhost:1300');
            define('HTTPS_SERVER', 'http://localhost:1300');
            define('HTTP_CATALOG_SERVER', 'http://localhost:1300');
            define('HTTPS_CATALOG_SERVER', 'http://localhost:1300');

            // Use secure webserver for catalog module and/or admin areas?
            define('ENABLE_SSL_CATALOG', 'true');
            define('ENABLE_SSL_ADMIN', 'false');
            
            
            define('DIR_WS_CATALOG', '/john/');
            define('DIR_WS_CATALOG_IMAGES', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'images/');
            define('DIR_WS_CATALOG_TEMPLATE', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'includes/templates/');
            define('DIR_WS_INCLUDES', 'application/controllers');
            define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
            define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
            define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
            define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
            define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');
            define('DIR_WS_CATALOG_LANGUAGES', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'includes/languages/');
            
        }
}

?>
