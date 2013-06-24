<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require_once 'anet_php_sdk/AuthorizeNet.php'; // Make sure this path is correct.
//include_once('application/controllers/functions/functions_general.php');
include_once 'application/controllers/modules/payment/authorizenet_aim.php';

class Store extends CI_Controller
{
    
      
	function __construct()
	{
		parent::__construct();

		//$this->load->library('security');
		$this->load->database();
                $ci =& get_instance();
                $db=$ci->db;
                $GLOBALS ['db']=$db;
  
                $this->load->model('store_model');
                $this->load->model('variable');
                                                
	}
	
	function index()
	{
		echo "Invalid Access!";
	}
        
     
        /*
         * list categories
         * @param parent_id
         * @param language_id
         * @return	array
         */
	function list_categories() {
                        
            $parent_id=isset($_REQUEST['parent_id']) ? $_REQUEST['parent_id'] : 0; //default 0: top level
            $language_id=isset($_REQUEST['language_id']) ? $_REQUEST['language_id'] : 1; //default 1: English
      
            $result['categories']= $this->store_model->list_categories($parent_id, $language_id);
            $result['status'] = $this->config->item('success');
                                              
            echo json_encode($result);  
	}
        
      
         /*
         * list products
         * @param parent_id
         * @param language_id
         * @return	array
         */
	function list_products() {                        
            
           if (!isset($_REQUEST['category_id'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
           }
           $category_id=$_REQUEST['category_id'];
            $language_id=isset($_REQUEST['language_id']) ? $_REQUEST['language_id'] : 1; //default 1: English
      
            $result['products']= $this->store_model->list_products($category_id, $language_id);
            $result['status'] = $this->config->item('success');
                                              
            echo json_encode($result);  
	}
        
        /*
         * list products_attributes_options
         * @param	int
	 * @return	array
         */
	function list_products_attributes_options() {                        
            
           if (!isset($_REQUEST['product_id'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
           }
           
           $product_id=$_REQUEST['product_id'];
           $language_id=isset($_REQUEST['language_id']) ? $_REQUEST['language_id'] : 1; //default 1: English
                
            $result['options']= $this->store_model->list_products_attributes_options($product_id,$language_id);
            $result['status'] = $this->config->item('success');
                                              
            echo json_encode($result);  
	}
        
         /*
         * list products_attributes
         * @param	int
	 * @return	array of array
         */
	function list_products_attributes() {                        
            
           if (!isset($_REQUEST['product_id'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
           }
           
           $product_id=$_REQUEST['product_id'];
           $language_id=isset($_REQUEST['language_id']) ? $_REQUEST['language_id'] : 1; //default 1: English
                
           $options_list=$this->store_model->list_products_attributes_options($product_id,$language_id);
           $i=0; $list=array();           
           foreach ($options_list as $row)
           {                
              $options_values_list=$this->store_model->list_products_attributes_options_values($product_id,$row['options_id'],$language_id);
              if (sizeof($options_values_list)>0){
                  $list[$i]['options_id']=$row['options_id'];
                  $list[$i]['options_name']=$row['options_name'];
                  $list[$i]['values_list']=$options_values_list;
                  $i++;
              }
              
           }
           //print_r($list);
           $result['attributes']=$list;
           $result['status'] = $this->config->item('success');
                                              
            echo json_encode($result);  
	}
	
	/*
         * list products_attributes_options
         * @param	int
	 * @return	array
         */
	function list_products_attributes_options_values() {                        
            
           if (!isset($_REQUEST['product_id'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
           }
           
            if (!isset($_REQUEST['option_id'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
           }
           
           $product_id=$_REQUEST['product_id'];
           $option_id=$_REQUEST['option_id'];
           $language_id=isset($_REQUEST['language_id']) ? $_REQUEST['language_id'] : 1; //default 1: English
                
           $result['options_values']= $this->store_model->list_products_attributes_options_values($product_id,$option_id,$language_id);
            $result['status'] = $this->config->item('success');
                                              
            echo json_encode($result);  
	}
	
        /*
         * 
         * @param	array of array
         * @param       reference checkout_products.html
	 * @return	array
         */
	function checkout_products() {
            
            $obj=json_decode($_POST['product_string']);
            
            $this->variable->defineVariables();                    
            $order_total=$obj->order_total;
            $sub_total=$obj->sub_total;
            $order_tax=$obj->order_tax;
            $payment_method=$obj->payment_method;
            $language_id=$obj->language_id;
                            
           // var_dump($obj);
            $products=array(); $i=0;
            foreach ($obj->product_ids as $product_id) {
                $option_value_ids=$obj->pids[$i];   //option values
                $option_ids=$obj->pid_options[$i]; //option name
                
                $products[$i]=array(
                    'product_id' => $product_id,
                    'product_name' => $obj->product_names[$i],
                    'quantity' => $obj->quantities[$i],
                    'option_value_ids' => $option_value_ids,
                    'option_ids' => $option_ids,
                    'final_price' => $obj->final_prices[$i],
                    );
                $i++;
            }
           
            $store_1_walk_in_customer=$this->store_model->get_customer_by_id(STORE_1_WAK_IN_CUSTOMER_ID);
          
            $address_book=$this->store_model->get_address_book_by_id($store_1_walk_in_customer->customers_default_address_id);
            $new_order_id="";
            $payment_info=array();
            $result=array();
            
            //Payment pre_check & before process
            
            if ($payment_method=="credit"){
                
                $transaction = new AuthorizeNetCP(MODULE_PAYMENT_AUTHORIZENET_AIM_LOGIN, MODULE_PAYMENT_AUTHORIZENET_AIM_MD5HASH);// 'enetworkprocessing username & password');
                $transaction->amount = $order_total;
                $transaction->setSandbox(false);

                $transaction->setCustomField('email', $store_1_walk_in_customer->customers_email_address);
                $transaction->setCustomField('device_type', "4"); 
            //    $transaction->setCustomField('x_market_type', "2");
                $transaction->setCustomField('x_version', "3.1");            
                $cc_number=$obj->cc_number;        
                $exp_date=$obj->exp_date;
               
                $transaction->setCustomField('x_card_num', $cc_number); //card number  5155760000409640
                $transaction->setCustomField('x_exp_date', $exp_date);
                $transaction->setCustomField('x_first_name', $address_book->entry_firstname);
                $transaction->setCustomField('x_last_name', $address_book->entry_lastname);
                $transaction->setCustomField('x_delim_data', TRUE);                
                $response = $transaction->authorizeAndCapture();
               
                if ($response->approved) {
                    $result['pay_status']=SUCCESS;
                    $result['pay_message']="Transaction ID: " . $response->transaction_id;                    
                } else {
                    $result['status'] = FAIL;
                    $result['pay_status']=FAIL;
                    $result['pay_message']= $response->response_reason_text;
                    echo json_encode($result);
                    return;
                }                
            }
            else if ($payment_method=="cash"){
                
                $payment_info['cc_type']="";
                $payment_info['cc_owner']="";
                $payment_info['cc_number']="";
                $payment_info['cc_expires']="";
                $payment_info['cc_cvv']="";
            }
            else{
                $result['status'] = $this->config->item('fail');   
                $result['error'] = "Payment method is not specified.";
                 echo json_encode($result);
                return;
            }
            
            $new_order_id=$this->store_model->get_next_insert_idx("orders");
            
            $result=array();
            
            $ip_address=$_SERVER['REMOTE_ADDR'];
            //add record to store_1_sales_orders table
            
            define('PROCESSING_STATUS', 1);
            
            $order_id=$this->store_model->create_in_store_1_order($new_order_id,$order_total,PROCESSING_STATUS , array(),
                    $order_tax,$ip_address,$payment_method);
            
            if (!$order_id || $order_id==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Order is not created";
                echo json_encode($result);
                return;
            }
            
            //add record to orders_products table
            $orders_products_list=$this->store_model->create_orders_products($order_id,$products,$language_id);
            if ($orders_products_list==null){
                $result['status']=$this->config->item('fail');
                $result['error']="Order:".$order_id. " Order_product is not created";
                echo json_encode($result);
                return;
            }
            
            //add record to orders_products_attributes table
            $r_value=$this->store_model->create_orders_products_attributes($order_id,$products,$language_id,$orders_products_list);
            if (!$r_value || $r_value==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Order_products created but order_products_attributes is not created";
                echo json_encode($result);
                return;
            }
            
            //add record to orders_total table
             
            $r_value=$this->store_model->create_orders_total($order_id,$sub_total,0,$order_tax,$order_total);
            if (!$r_value || $r_value==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Orders Total is not created";
                echo json_encode($result);
                return;
            }
            
            //add to orders_status_history table
            $r_value=$this->store_model->create_status_history($order_id,PROCESSING_ORDER_STATUS_ID,"Initialized by Mobile App");
            if (!$r_value || $r_value==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Order Status History is not created";
                echo json_encode($result);
                return;
            }
            
          
            //deduct option values from Store #1 option value tables
            // add sold quantity on to sold_store_1 table
            if ($this->store_model->update_option_values($products,$language_id)){
                                
                //add one order record in main order table under the status of "In Store Sales #1"                  
                $result['status']=$this->config->item('success');
            }
            /*
             if ($payment=="Credit Card"){                 
               
                
                //change the order status
               // $this->store_model->update_order_status($order_id,In_Store_Sales_1_ORDER_STATUS_ID);
                
            }
            else if ($payment=="moneyorder"){
                //do nothing
            }
            else{
                $result['status'] = $this->config->item('fail');   
                $result['error'] = "Payment method is not specified.";
                echo json_encode($result);
                return;
            }
            */
            
            // restock the product options
            
            $this->store_model->restock_values($products,$language_id);
            echo json_encode($result);
   
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */