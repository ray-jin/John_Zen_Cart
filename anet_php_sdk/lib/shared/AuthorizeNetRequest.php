<?php
/**
 * Sends requests to the Authorize.Net gateways.
 *
 * @package    AuthorizeNet
 * @subpackage AuthorizeNetRequest
 */
abstract class AuthorizeNetRequest
{
    
    protected $_api_login;
    protected $_transaction_key;
    protected $_post_string; 
    public $VERIFY_PEER = true; // Set to false if getting connection errors.
    protected $_sandbox = true; //default true;
    protected $_log_file = false;
    
    /**
     * Set the _post_string
     */
    abstract protected function _setPostString();
    
    /**
     * Handle the response string
     */
    abstract protected function _handleResponse($string);
    
    /**
     * Get the post url. We need this because until 5.3 you
     * you could not access child constants in a parent class.
     */
    abstract protected function _getPostUrl();
    
    /**
     * Constructor.
     *
     * @param string $api_login_id       The Merchant's API Login ID.
     * @param string $transaction_key The Merchant's Transaction Key.
     */
    public function __construct($api_login_id = false, $transaction_key = false)
    {
        $this->_api_login = ($api_login_id ? $api_login_id : (defined('AUTHORIZENET_API_LOGIN_ID') ? AUTHORIZENET_API_LOGIN_ID : ""));
        $this->_transaction_key = ($transaction_key ? $transaction_key : (defined('AUTHORIZENET_TRANSACTION_KEY') ? AUTHORIZENET_TRANSACTION_KEY : ""));
        $this->_sandbox = (defined('AUTHORIZENET_SANDBOX') ? AUTHORIZENET_SANDBOX : true);
        $this->_log_file = (defined('AUTHORIZENET_LOG_FILE') ? AUTHORIZENET_LOG_FILE : false);
    }
    
    /**
     * Alter the gateway url.
     *
     * @param bool $bool Use the Sandbox.
     */
    public function setSandbox($bool)
    {
        $this->_sandbox = $bool;
    }
    
    /**
     * Set a log file.
     *
     * @param string $filepath Path to log file.
     */
    public function setLogFile($filepath)
    {
        $this->_log_file = $filepath;
    }
    
    /**
     * Return the post string.
     *
     * @return string
     */
    public function getPostString()
    {
        return $this->_post_string;
    }
    
    /**
     * Posts the request to AuthorizeNet & returns response.
     *
     * @return AuthorizeNetARB_Response The response.
     */
    protected function _sendRequest()
    {
        $this->_setPostString();
        $post_url = $this->_getPostUrl();
       // var_dump($this->_post_string);
        
   // curl_setopt($ch, CURLOPT_REFERER, ($request_type == 'SSL' ? HTTPS_SERVER . DIR_WS_HTTPS_CATALOG : HTTP_SERVER . DIR_WS_CATALOG ));
 //   curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
   //  
   
   
  //  curl_setopt($ch, CURLOPT_SSLVERSION, 3);
    //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); /* compatibility for SSL communications on some Windows servers (IIS 5.0+) */
     $curl_request = curl_init($post_url);
        
        /*$data="x_login=0810106&x_tran_key=Nsdkb3Xbg5hLKp8&x_delim_data=TRUE&x_delim_char=%7C&
      x_encap_char=%2A&x_version=3.1&x_method=CC&x_amount=0.00&x_currency_code=USD&x_card_num=4012888888881881&x_exp_date=0613&x_card_code=345&x_email_customer=FALSE&x_email_merchant=TRUE&
      x_cust_id=38164&x_invoice_num=1002747-jgoSN1&x_first_name=Ray&
      x_last_name=Jin&x_company=&x_address=12312&x_city=sfsdf&x_state=Alaska&x_zip=2341
      &x_country=United+States&x_phone=234234&x_email=gold.brain.pitt%40gmail.com&
      x_ship_to_first_name=Maddox&x_ship_to_last_name=Pitt&x_ship_to_address=12312&x_ship_to_city=sfsdf&x_ship_to_state=Alaska&x_ship_to_zip=2341&x_ship_to_country=United+States&x_description=SmokePass+Electronic+Cigar+-+1800+Puff+%28qty%3A+1%29+&x_recurring_billing=NO&x_customer_ip=1&x_po_num=Jun-13-2013+07%3A23%3A27&x_freight=0.00&x_tax_exempt=FALSE&x_tax=0.00&x_duty=0&x_allow_partial_Auth=FALSE
      &IP=1&Session=dut2ni717thfum4pi9hgkij7a6";
     //$data="x_cpversion=1.0&x_delim_char=%2C&x_encap_char=%7C&x_market_type=2&x_response_format=1&x_amount=0.01&x_type=AUTH_CAPTURE&x_login=0810106&x_tran_key=Nsdkb3Xbg5hLKp8&email=nice.ray.jin%40gmail.com&device_type=4&x_version=3.1&x_card_num=4007000000027&x_exp_date=0613&x_first_name=Ray&x_last_name=Jin";
     */
        curl_setopt($curl_request, CURLOPT_POSTFIELDS,$this->_post_string);
        curl_setopt($curl_request, CURLOPT_POST, 1);
        curl_setopt($curl_request, CURLOPT_FRESH_CONNECT, 1);
         curl_setopt($curl_request, CURLOPT_VERBOSE, 0);
        curl_setopt($curl_request, CURLOPT_HEADER, 0);
        curl_setopt($curl_request, CURLOPT_TIMEOUT, 45);
        curl_setopt($curl_request, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl_request, CURLOPT_SSLVERSION, 3);
        curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, FALSE); 
        if ($this->VERIFY_PEER) {
            curl_setopt($curl_request, CURLOPT_CAINFO, dirname(dirname(__FILE__)) . '/ssl/cert.pem');
        } else {
            curl_setopt($curl_request, CURLOPT_SSL_VERIFYPEER, false);
        }
        
        if (preg_match('/xml/',$post_url)) {
            curl_setopt($curl_request, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        }
      //  return;
        $response = curl_exec($curl_request);
        
        if ($this->_log_file) {
        
            if ($curl_error = curl_error($curl_request)) {
                file_put_contents($this->_log_file, "----CURL ERROR----\n$curl_error\n\n", FILE_APPEND);
            }
            // Do not log requests that could contain CC info.
            // file_put_contents($this->_log_file, "----Request----\n{$this->_post_string}\n", FILE_APPEND);
            
            file_put_contents($this->_log_file, "----Response----\n$response\n\n", FILE_APPEND);
        }
        curl_close($curl_request);
        
        return $this->_handleResponse($response);
    }

}