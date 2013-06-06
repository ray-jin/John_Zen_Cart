<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


/**
 * authorize.net AIM payment method class
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @copyright Portions Copyright 2003 osCommerce
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: authorizenet_aim.php 16256 2010-05-10 23:59:53Z drbyte $
 */
/**
 * Authorize.net Payment Module (AIM version)
 * You must have SSL active on your server to be compliant with merchant TOS
 *
 */

include_once 'application/controllers/classes/class.base.php';
include_once 'application/controllers/functions/functions_general.php';
include_once 'application/controllers/functions/functions_email.php';
include_once 'application/controllers/classes/cc_validation.php';

include_once 'application/controllers/classes/order.php';

class authorizenet_aim extends base {
  /**
   * $code determines the internal 'code' name used to designate "this" payment module
   *
   * @var string
   */
  var $code;
  /**
   * $title is the displayed name for this payment method
   *
   * @var string
   */
  var $title;
  /**
   * $description is a soft name for this payment method
   *
   * @var string
   */
  var $description;
  /**
   * $enabled determines whether this module shows or not... in catalog.
   *
   * @var boolean
   */
  var $enabled;
  /**
   * $delimiter determines what separates each field of returned data from authorizenet
   *
   * @var string (single char)
   */
  var $delimiter = '|';
  /**
   * $encapChar denotes what character is used to encapsulate the response fields
   *
   * @var string (single char)
   */
  var $encapChar = '*';
  /**
   * log file folder
   *
   * @var string
   */
  var $_logDir = '';
  /**
   * communication vars
   */
  var $authorize = '';
  var $commErrNo = 0;
  var $commError = '';
  var $error;
  /**
   * debug content var
   */
  var $reportable_submit_data = array();
  
  public $cc_card_type;
  public $cc_card_number ;
  public $cc_expiry_month ;
  public $cc_expiry_year ;
  public $cc_card_owner;
  public $cc_cvv;
  
  public $new_order_id;
  public $order_status;
  var $order; //order object
  /**
   * Constructor
   *
   * @return authorizenet_aim
   */
  function authorizenet_aim() {
    
    $this->error="";
  }
  /**
   * calculate zone matches and flag settings to determine whether this module should display to customers or not
   *
   */
  function update_status() {
    global $order, $db;
    if ( ($this->enabled == true) && ((int)MODULE_PAYMENT_AUTHORIZENET_AIM_ZONE > 0) ) {
      $check_flag = false;
      $check = $db->Execute("select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . MODULE_PAYMENT_AUTHORIZENET_AIM_ZONE . "' and zone_country_id = '" . $order->billing['country']['id'] . "' order by zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }
  }
  
  /**
   * Evaluates the Credit Card Type for acceptance and the validity of the Credit Card Number & Expiration Date
   *
   */
  function pre_confirmation_check() {    

    $cc_validation = new cc_validation();
    $result = $cc_validation->validate($_POST['authorizenet_aim_cc_number'], $_POST['authorizenet_aim_cc_expires_month'], 
            $_POST['authorizenet_aim_cc_expires_year'], $_POST['authorizenet_aim_cc_cvv']);
    $error = '';
    switch ($result) {
      case -1:
      $error = sprintf(TEXT_CCVAL_ERROR_UNKNOWN_CARD, substr($cc_validation->cc_number, 0, 4));
      break;
      case -2:
      case -3:
      case -4:
      $error = TEXT_CCVAL_ERROR_INVALID_DATE;
      break;
      case false:
      $error = TEXT_CCVAL_ERROR_INVALID_NUMBER;
      break;
    }

    if ( ($result == false) || ($result < 1) ) {
      return 0;            
    }

    $this->cc_card_type = $cc_validation->cc_type;
    $this->cc_card_number = $cc_validation->cc_number;
    $this->cc_expiry_month = $cc_validation->cc_expiry_month;
    $this->cc_expiry_year = $cc_validation->cc_expiry_year;
    $this->cc_cvv=$_POST['authorizenet_aim_cc_cvv'];
    return 1;
  }
  /**
   * Display Credit Card Information on the Checkout Confirmation Page
   *
   * @return array
   */
  function confirmation() {
    $confirmation = array('fields' => array(array('title' => MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_TYPE,
                                                  'field' => $this->cc_card_type),
                                            array('title' => MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_OWNER,
                                                  'field' => $_POST['authorizenet_aim_cc_owner']),
                                            array('title' => MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_NUMBER,
                                                  'field' => substr($this->cc_card_number, 0, 4) . str_repeat('X', (strlen($this->cc_card_number) - 8)) . substr($this->cc_card_number, -4)),
                                            array('title' => MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_CREDIT_CARD_EXPIRES,
                                                  'field' => strftime('%B, %Y', mktime(0,0,0,$_POST['authorizenet_aim_cc_expires_month'], 1, '20' . $_POST['authorizenet_aim_cc_expires_year']))) ));
    return $confirmation;
  }
  
  
  /**
   * Process the payment module
   *
   * @return string
   */
  function process($products,$customer,$address_book,$total) {
   
    if ($this->pre_confirmation_check()==0){
        return false;
    }

    return $this->before_process($products,$customer,$address_book,$total);
  }
  /**
   * Store the CC info to the order and process any results that come back from the payment gateway
   *
   */
  function before_process($products,$customer,$address_book,$total) {
   
    $db=$GLOBALS['db'];
    
    $this->order->info['cc_type']    = $this->cc_card_type;
    $this->order->info['cc_owner']   = $this->cc_card_owner;
    $this->order->info['cc_number'] = $this->cc_card_number;
    $this->order->info['cc_expires'] = '';  // $_POST['cc_expires'];
    $this->order->info['cc_expires'] =$this->cc_expiry_month . substr($this->cc_expiry_year, -2);            
    //$this->order->info['cc_cvv']     = '***'; //$_POST['cc_cvv'];
    $this->order->info['cc_cvv']     =$this->cc_cvv;
    $this->order->info['shipping_cost']=0;
    $this->order->info['tax']=0;
    

    
    //$sessID = zen_session_id();
    $sessID=session_id();
    
    // DATA PREPARATION SECTION
    unset($submit_data);  // Cleans out any previous data stored in the variable

    // Create a string that contains a listing of products ordered for the description field
    $description = '';
       
     foreach ($products as $product){         
         
         $description .= $product['product_name'] . ' (qty: ' . $product['quantity'] . ') + ';
    }
    // Remove the last "\n" from the string
    $description = substr($description, 0, -2);

    // Create a variable that holds the order time
    $order_time = date("F j, Y, g:i a");

    // Calculate the next expected order id (adapted from code written by Eric Stamper - 01/30/2004 Released under GPL)
     $last_order_id= $db->query("select * from " . TABLE_ORDERS . " order by orders_id desc limit 1")->row();
     
    //$last_order_id=$query->row();
    $this->new_order_id = $last_order_id->orders_id;
    $this->new_order_id = ($this->new_order_id + 1);

    // add randomized suffix to order id to produce uniqueness ... since it's unwise to submit the same order-number twice to authorize.net
    
    $this->new_order_id = (string)$this->new_order_id . '-' . zen_create_random_value(6,"chars");
   
     /*$taxCloudTax = $_SESSION['taxcloudTaxTotal']; 
      if ( isset($taxCloudTax)) {
       $order->info['total']  = $order->info['total'] + round($taxCloudTax,2);
       $order->info['tax'] = round($taxCloudTax,2);
      }*/
      //End TaxCloud fix
      
      
      
    // Populate an array that contains all of the data to be sent to Authorize.net
   
    $submit_data = array(
                         'x_login' => trim(MODULE_PAYMENT_AUTHORIZENET_AIM_LOGIN),
                         'x_tran_key' => trim(MODULE_PAYMENT_AUTHORIZENET_AIM_TXNKEY),
                         'x_relay_response' => 'FALSE', // AIM uses direct response, not relay response
                         'x_delim_data' => 'TRUE',
                         'x_delim_char' => $this->delimiter,  // The default delimiter is a comma
                         'x_encap_char' => $this->encapChar,  // The divider to encapsulate response fields
                         'x_version' => '3.1',  // 3.1 is required to use CVV codes
                         'x_method' => 'CC',
                         'x_amount' => number_format($total, 2),
                         'x_currency_code' => "USD",
                         'x_card_num' => $this->order->info['cc_number'],
                         'x_exp_date' => $this->order->info['cc_expires'] ,
                         'x_card_code' => $this->order->info['cc_cvv'],
                         'x_email_customer' => MODULE_PAYMENT_AUTHORIZENET_AIM_EMAIL_CUSTOMER == 'True' ? 'TRUE': 'FALSE',
                         'x_email_merchant' => MODULE_PAYMENT_AUTHORIZENET_AIM_EMAIL_MERCHANT == 'True' ? 'TRUE': 'FALSE',
                         'x_cust_id' => STORE_1_WAK_IN_CUSTOMER_ID,
                         'x_invoice_num' => (MODULE_PAYMENT_AUTHORIZENET_AIM_TESTMODE == 'Test' ? 'TEST-' : '') . $this->new_order_id,
                         'x_first_name' => $customer->customers_firstname,
                         'x_last_name' => $customer->customers_lastname,
                         'x_company' => $address_book->entry_company,
                         'x_address' => $address_book->entry_street_address,
                         'x_city' => $address_book->entry_city,
                         'x_state' => $address_book->entry_state,
                         'x_zip' => $address_book->entry_postcode,
                         'x_country' => "USA",
                         'x_phone' => $customer->customers_telephone,
                         'x_email' => $customer->customers_email_address,
                         'x_ship_to_first_name' => $customer->customers_firstname,
                         'x_ship_to_last_name' => $customer->customers_lastname,
                         'x_ship_to_address' =>  $address_book->entry_street_address,
                         'x_ship_to_city' => $address_book->entry_city,
                         'x_ship_to_state' => $address_book->entry_state,
                         'x_ship_to_zip' => $address_book->entry_postcode,
                         'x_ship_to_country' => "USA",
                         'x_description' => $description,
                         'x_recurring_billing' => 'NO',
                         'x_customer_ip' => $_SERVER['REMOTE_ADDR'],
                         'x_po_num' => date('M-d-Y h:i:s'), //$order->info['po_number'],
                         'x_freight' => number_format((float)$this->order->info['shipping_cost'],2),
                         'x_tax_exempt' => 'FALSE', /* 'TRUE' or 'FALSE' */
                         'x_tax' => number_format((float)$this->order->info['tax'],2),
                         'x_duty' => '0',
                         'x_allow_partial_Auth' => 'FALSE', // unable to accept partial authorizations at this time

                         // Additional Merchant-defined variables go here
                         'Date' => $order_time,
                         'IP' => $_SERVER['REMOTE_ADDR'],
                         'Session' => $sessID );

    unset($response);
     
    $response = $this->_sendRequest($submit_data);
    
    $response_code = $response[0];
    $response_text = $response[3];
    $this->auth_code = $response[4];
    $this->transaction_id = $response[6];
    $this->avs_response= $response[5];
    $this->ccv_response= $response[38];
    $response_msg_to_customer = $response_text . ($this->commError == '' ? '' : ' Communications Error - Please notify webmaster.');

    $response['Expected-MD5-Hash'] = $this->calc_md5_response($response[6], $response[9]);
    $response['HashMatchStatus'] = ($response[37] == $response['Expected-MD5-Hash']) ? 'PASS' : 'FAIL';
   
    $this->_debugActions($response, $order_time, $sessID);
    
    // If the MD5 hash doesn't match, then this transaction's authenticity cannot be verified.
    // Thus, order will be placed in Pending status
    if ($response['HashMatchStatus'] != 'PASS' && defined('MODULE_PAYMENT_AUTHORIZENET_AIM_MD5HASH') && MODULE_PAYMENT_AUTHORIZENET_AIM_MD5HASH != '') {
      $this->error=MODULE_PAYMENT_AUTHORIZENET_AIM_MD5HASH.$response[4];
      
    }
    
    // If the response code is not 1 (approved) then redirect back to the payment page with the appropriate error message
    if ($response_code != '1') {
      //$messageStack->add_session('checkout_payment', $response_msg_to_customer . ' - ' . MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_DECLINED_MESSAGE, 'error');
      $this->error=$response[3]."   ,   ".$response[4];
      return false;
    }

    if (sizeof($response)>87 && $response[88] != '') {
         $this->error= $response[88];
    }
    // If the response code is not 1 (approved) then redirect back to the payment page with the appropriate error message
    if ($response_code == '1') {      
      return true;
    }
    $this->error="Unknown Error";
    return false;
  }
  /**
   * Post-process activities. Updates the order-status history data with the auth code from the transaction.
   *
   * @return boolean
   */
  function after_process() {
  
    $db=$GLOBALS['db'];
            
    $db->set("comments","Credit Card payment.  AUTH: ". $this->auth_code . '. TransID: ' . $this->transaction_id . '.');
    $db->set("orders_id",$this->new_order_id);
    $db->set("orders_status_id",$this->order_status);
    $db->set("customer_notified",-1);
    $date = new DateTime();
    $datetime = $date->format('Y-m-d H:i:s');
    $db->set("date_added",$datetime);

    if (!$db->insert(TABLE_ORDERS_STATUS_HISTORY)) {
        return false;
    }
    return true;
  }
  /**
    * Build admin-page components
    *
    * @param int $zf_order_id
    * @return string
    */
  function admin_notification($zf_order_id) {
    global $db;
    $output = '';
    $aimdata->fields = array();
    require(DIR_FS_CATALOG . DIR_WS_MODULES . 'payment/authorizenet/authorizenet_admin_notification.php');
    return $output;
  }
  /**
   * Used to display error message details
   *
   * @return array
   */
  function get_error() {
    $error = array('title' => MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_ERROR,
                   'error' => stripslashes(urldecode($_GET['error'])));
    return $error;
  }
  /**
   * Check to see whether module is installed
   *
   * @return boolean
   */
  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_AUTHORIZENET_AIM_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }
  
  /**
   * Internal list of configuration keys used for configuration of the module
   *
   * @return array
   */
  function keys() {
    return array('MODULE_PAYMENT_AUTHORIZENET_AIM_STATUS', 'MODULE_PAYMENT_AUTHORIZENET_AIM_LOGIN', 'MODULE_PAYMENT_AUTHORIZENET_AIM_TXNKEY', 'MODULE_PAYMENT_AUTHORIZENET_AIM_MD5HASH', 'MODULE_PAYMENT_AUTHORIZENET_AIM_TESTMODE', 'MODULE_PAYMENT_AUTHORIZENET_AIM_AUTHORIZATION_TYPE', 'MODULE_PAYMENT_AUTHORIZENET_AIM_STORE_DATA', 'MODULE_PAYMENT_AUTHORIZENET_AIM_EMAIL_CUSTOMER', 'MODULE_PAYMENT_AUTHORIZENET_AIM_EMAIL_MERCHANT', 'MODULE_PAYMENT_AUTHORIZENET_AIM_USE_CVV', 'MODULE_PAYMENT_AUTHORIZENET_AIM_SORT_ORDER', 'MODULE_PAYMENT_AUTHORIZENET_AIM_ZONE', 'MODULE_PAYMENT_AUTHORIZENET_AIM_ORDER_STATUS_ID', 'MODULE_PAYMENT_AUTHORIZENET_AIM_REFUNDED_ORDER_STATUS_ID', 'MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING');
  }
  /**
   * Send communication request
   */
  function _sendRequest($submit_data) {
    global $request_type;

    // Populate an array that contains all of the data to be sent to Authorize.net
    $submit_data = array_merge(array(
                         'x_login' => trim(MODULE_PAYMENT_AUTHORIZENET_AIM_LOGIN),
                         'x_tran_key' => trim(MODULE_PAYMENT_AUTHORIZENET_AIM_TXNKEY),
                         'x_relay_response' => 'FALSE',
                         'x_delim_data' => 'TRUE',
                         'x_delim_char' => $this->delimiter,  // The default delimiter is a comma
                         'x_encap_char' => $this->encapChar,  // The divider to encapsulate response fields
                         'x_version' => '3.1',  // 3.1 is required to use CVV codes
                         ), $submit_data);

    if(MODULE_PAYMENT_AUTHORIZENET_AIM_TESTMODE == 'Test') {
      $submit_data['x_test_request'] = 'TRUE';
    }

    // set URL
    $url = 'https://www.eProcessingNetwork.Com/cgi-bin/an/order.pl';
    $devurl = 'https://test.authorize.net/gateway/transact.dll';
    $dumpurl = 'https://developer.authorize.net/param_dump.asp';
    $certurl = 'https://certification.authorize.net/gateway/transact.dll';
    
    if (defined('AUTHORIZENET_DEVELOPER_MODE')) {
      if (AUTHORIZENET_DEVELOPER_MODE == 'on') $url = $devurl;
      if (AUTHORIZENET_DEVELOPER_MODE == 'echo' || MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING == 'echo') $url = $dumpurl;
      if (AUTHORIZENET_DEVELOPER_MODE == 'certify') $url = $certurl;
    }
    if (MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING == 'echo') $url = $dumpurl;

    // concatenate the submission data into $data variable after sanitizing to protect delimiters
    $data = '';
    while(list($key, $value) = each($submit_data)) {
      if ($key != 'x_delim_char' && $key != 'x_encap_char') {
        $value = str_replace(array($this->delimiter, $this->encapChar,'"',"'",'&amp;','&', '='), '', $value);
      }
      $data .= $key . '=' . urlencode($value) . '&';
    }
    // Remove the last "&" from the string
    $data = substr($data, 0, -1);


    // prepare a copy of submitted data for error-reporting purposes
    $this->reportable_submit_data = $submit_data;
    $this->reportable_submit_data['x_login'] = '*******';
    $this->reportable_submit_data['x_tran_key'] = '*******';
    if (isset($this->reportable_submit_data['x_card_num'])) 
        $this->reportable_submit_data['x_card_num'] = str_repeat('X', strlen($this->reportable_submit_data['x_card_num'] - 4)) . substr($this->reportable_submit_data['x_card_num'], -4);
    if (isset($this->reportable_submit_data['x_card_code'])) $this->reportable_submit_data['x_card_code'] = '****';
    $this->reportable_submit_data['url'] = $url;
   
    // Post order info data to Authorize.net via CURL - Requires that PHP has CURL support installed
    
    // Send CURL communication
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_REFERER, ($request_type == 'SSL' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG ));
    curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_VERBOSE, 0);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 45);
    curl_setopt($ch, CURLOPT_SSLVERSION, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); /* compatibility for SSL communications on some Windows servers (IIS 5.0+) */
    if (CURL_PROXY_REQUIRED == 'True') {
      $this->proxy_tunnel_flag = (defined('CURL_PROXY_TUNNEL_FLAG') && strtoupper(CURL_PROXY_TUNNEL_FLAG) == 'FALSE') ? false : true;
      curl_setopt ($ch, CURLOPT_HTTPPROXYTUNNEL, $this->proxy_tunnel_flag);
      curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
      curl_setopt ($ch, CURLOPT_PROXY, CURL_PROXY_SERVER_DETAILS);
    }

    $this->authorize = curl_exec($ch);
    $this->commError = curl_error($ch);
    $this->commErrNo = curl_errno($ch);

    $this->commInfo = @curl_getinfo($ch);
    curl_close ($ch);

    // if in 'echo' mode, dump the returned data to the browser and stop execution
    if ((defined('AUTHORIZENET_DEVELOPER_MODE') && AUTHORIZENET_DEVELOPER_MODE == 'echo') || MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING == 'echo') {
      echo $this->authorize . ($this->commErrNo != 0 ? '<br />' . $this->commErrNo . ' ' . $this->commError : '') . '<br />';
      die('Press the BACK button in your browser to return to the previous page.');
    }

    // parse the data received back from the gateway, taking into account the delimiters and encapsulation characters
    $stringToParse = $this->authorize;
    if (substr($stringToParse,0,1) == $this->encapChar) $stringToParse = substr($stringToParse,1);
    $stringToParse = preg_replace('/.{*}' . $this->encapChar . '$/', '', $stringToParse);
    $response = explode($this->encapChar . $this->delimiter . $this->encapChar, $stringToParse);

    return $response;
  }
  /**
   * Calculate validity of response
   */
  function calc_md5_response($trans_id = '', $amount = '') {
    if ($amount == '' || $amount == '0') $amount = '0.00';
    $validating = md5(MODULE_PAYMENT_AUTHORIZENET_AIM_MD5HASH . MODULE_PAYMENT_AUTHORIZENET_AIM_LOGIN . $trans_id . $amount);
    return strtoupper($validating);
  }
  /**
   * Used to do any debug logging / tracking / storage as required.
   */
  function _debugActions($response, $order_time= '', $sessID = '') {
          
    $db=$GLOBALS['db'];
    if ($order_time == '') $order_time = date("F j, Y, g:i a");
    // convert output to 1-based array for easier understanding:
    $resp_output = array_reverse($response);
    $resp_output[] = 'Response from gateway';
    $resp_output = array_reverse($resp_output);

    // DEBUG LOGGING
      $errorMessage = date('M-d-Y h:i:s') .
                      "\n=================================\n\n" .
                      ($this->commError !='' ? 'Comm results: ' . $this->commErrNo . ' ' . $this->commError . "\n\n" : '') .
                      'Response Code: ' . $response[0] . ".\nResponse Text: " . $response[3] . "\n\n" .
                      'Sending to Authorizenet: ' . print_r($this->reportable_submit_data, true) . "\n\n" .
                      'Results Received back from Authorizenet: ' . print_r($resp_output, true) . "\n\n" .
                      'CURL communication info: ' . print_r($this->commInfo, true) . "\n";
      if (CURL_PROXY_REQUIRED == 'True')
        $errorMessage .= 'Using CURL Proxy: [' . CURL_PROXY_SERVER_DETAILS . ']  with Proxy Tunnel: ' .($this->proxy_tunnel_flag ? 'On' : 'Off') . "\n";
      $errorMessage .= "\nRAW data received: \n" . $this->authorize . "\n\n";

      if (strstr(MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING, 'Log') || strstr(MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING, 'All') || (defined('AUTHORIZENET_DEVELOPER_MODE') && in_array(AUTHORIZENET_DEVELOPER_MODE, array('on', 'certify')))) {
        $key = $response[6] . '_' . time() . '_' . zen_create_random_value(4);
        $file = $this->_logDir . '/' . 'AIM_Debug_' . $key . '.log';
        if ($fp = @fopen($file, 'a')) {
          fwrite($fp, $errorMessage);
          fclose($fp);
        }
      }
      $this->error=$errorMessage;
      if (($response[0] != '1' && stristr(MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING, 'Alerts')) || strstr(MODULE_PAYMENT_AUTHORIZENET_AIM_DEBUGGING, 'Email')) {
        //zen_mail(STORE_NAME, STORE_OWNER_EMAIL_ADDRESS, 'AuthorizenetAIM Alert ' . $response[7] . ' ' . date('M-d-Y h:i:s') . ' ' . $response[6], $errorMessage, STORE_OWNER, STORE_OWNER_EMAIL_ADDRESS, array('EMAIL_MESSAGE_HTML'=>nl2br($errorMessage)), 'debug');
      }

    // DATABASE SECTION
    // Insert the send and receive response data into the database.
    // This can be used for testing or for implementation in other applications
    // This can be turned on and off if the Admin Section
    if (MODULE_PAYMENT_AUTHORIZENET_AIM_STORE_DATA == 'True'){
      $db_response_text = $response[3] . ($this->commError !='' ? ' - Comm results: ' . $this->commErrNo . ' ' . $this->commError : '');
      $db_response_text .= ($response[0] == 2 && $response[2] == 4) ? ' NOTICE: Card should be picked up - possibly stolen ' : '';
      $db_response_text .= ($response[0] == 3 && $response[2] == 11) ? ' DUPLICATE TRANSACTION ATTEMPT ' : '';

      // Insert the data into the database
     // $sql = "insert into " . TABLE_AUTHORIZENET . "  (id, customer_id, order_id, response_code, response_text, authorization_type, transaction_id, 
     // sent, received, time, session_id) values (NULL, :custID, :orderID, :respCode, :respText, :authType, :transID, :sentData, :recvData, :orderTime, :sessID )";
       
      $db->set('customer_id', STORE_1_WAK_IN_CUSTOMER_ID);
      $db->set('order_id', preg_replace('/[^0-9]/', '', $response[7])); 
      $db->set('response_code', $response[0]);
      $db->set('response_text', $db_response_text);
      $db->set('authorization_type', $response[11]);
      $db->set('transaction_id', $this->transaction_id);
      $db->set('sent', print_r($this->reportable_submit_data, true));
      $db->set('received', print_r($response, true));
      $db->set('time', $order_time);
      $db->set('session_id', $sessID);
      
      $db->insert(TABLE_AUTHORIZENET);
    }
  }
  /**
   * Check and fix table structure if appropriate
   */
  function tableCheckup() {
    global $db, $sniffer;
    $fieldOkay1 = (method_exists($sniffer, 'field_type')) ? $sniffer->field_type(TABLE_AUTHORIZENET, 'transaction_id', 'bigint', true) : -1;
    if ($fieldOkay1 !== true) {
      $db->Execute("ALTER TABLE " . TABLE_AUTHORIZENET . " CHANGE transaction_id transaction_id bigint default NULL");
    }
  }
 
  /**
   * Used to void a given previously-authorized transaction.
   */
  function _doVoid($oID, $note = '') {
    global $db, $messageStack;

    $new_order_status = (int)MODULE_PAYMENT_AUTHORIZENET_AIM_REFUNDED_ORDER_STATUS_ID;
    if ($new_order_status == 0) $new_order_status = 1;
    $voidNote = strip_tags(zen_db_input($_POST['voidnote'] . $note));
    $voidAuthID = trim(strip_tags(zen_db_input($_POST['voidauthid'])));
    $proceedToVoid = true;
    if (isset($_POST['ordervoid']) && $_POST['ordervoid'] == MODULE_PAYMENT_AUTHORIZENET_AIM_ENTRY_VOID_BUTTON_TEXT) {
      if (isset($_POST['voidconfirm']) && $_POST['voidconfirm'] != 'on') {
        $messageStack->add_session(MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_VOID_CONFIRM_ERROR, 'error');
        $proceedToVoid = false;
      }
    }
    if ($voidAuthID == '') {
      $messageStack->add_session(MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_TRANS_ID_REQUIRED_ERROR, 'error');
      $proceedToVoid = false;
    }
    // Populate an array that contains all of the data to be sent to gateway
    $submit_data = array('x_type' => 'VOID',
                         'x_trans_id' => trim($voidAuthID) );
    /**
     * Submit void request to Gateway
     */
    if ($proceedToVoid) {
      $response = $this->_sendRequest($submit_data);
      $response_code = $response[0];
      $response_text = $response[3];
      $response_alert = $response_text . ($this->commError == '' ? '' : ' Communications Error - Please notify webmaster.');
      $this->reportable_submit_data['Note'] = $voidNote;
      $this->_debugActions($response);

      if ($response_code != '1' || ($response[0]==1 && $response[2] == 310) ) {
        $messageStack->add_session($response_alert, 'error');
      } else {
        // Success, so save the results
        $sql_data_array = array('orders_id' => (int)$oID,
                                'orders_status_id' => (int)$new_order_status,
                                'date_added' => 'now()',
                                'comments' => 'VOIDED. Trans ID: ' . $response[6] . ' ' . $response[4] . "\n" . $voidNote,
                                'customer_notified' => 0
                             );
        zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);
        $db->Execute("update " . TABLE_ORDERS  . "
                      set orders_status = '" . (int)$new_order_status . "'
                      where orders_id = '" . (int)$oID . "'");
        $messageStack->add_session(sprintf(MODULE_PAYMENT_AUTHORIZENET_AIM_TEXT_VOID_INITIATED, $response[6], $response[4]), 'success');
        return true;
      }
    }
    return false;
  }

}
