<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

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
           
            if (!isset($_REQUEST['quantity']) || !isset($_REQUEST['product_id']) || !isset($_REQUEST['product_name'])) {
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
            }
            
            if ( !isset($_REQUEST['authorizenet_aim_cc_owner'])|| !isset($_REQUEST['authorizenet_aim_cc_number'])
               || !isset($_REQUEST['authorizenet_aim_cc_expires_month'])|| !isset($_REQUEST['authorizenet_aim_cc_expires_year'])
               || !isset($_REQUEST['authorizenet_aim_cc_cvv'])) {
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
            }
            
            $product_ids=$_REQUEST['product_id'];
            $product_names=$_REQUEST['product_name'];
            $quantities=$_REQUEST['quantity'];
             
            if (sizeof($product_ids)!=sizeof($quantities) || !isset($_REQUEST['product_id']) 
                    || !isset($_REQUEST['quantity']) || !isset($_REQUEST['language_id']) 
                    || !isset($_REQUEST['order_total'])|| !isset($_REQUEST['order_tax'])
                    || !isset($_REQUEST['payment_method'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
            }
            
            $this->store_model->defineVariables();
            
            $payment=$_REQUEST['payment_method'];
            $GLOBALS ['currency']=DEFAULT_CURRENCY;
            
            $GLOBALS['payment']=$payment;
            
            $order_total=$_REQUEST['order_total'];
            $order_tax=$_REQUEST['order_tax'];
            $payment_method=$_REQUEST['payment_method'];
            $language_id=$_REQUEST['language_id'];
            
            $products=array(); $i=0;
            foreach ($product_ids as $product_id) {
                $option_value_ids=$_REQUEST['pid'.$product_id];   //option values
                $option_ids=$_REQUEST['pid_option'.$product_id]; //option name
                
                $products[$i]=array(
                    'product_id' => $product_id,
                    'product_name' => $product_names[$i],
                    'quantity' => $quantities[$i],
                    'option_value_ids' => $option_value_ids,
                    'option_ids' => $option_ids,
                    );

                $i++;
            }
            
            $store_1_walk_in_customer=$this->store_model->get_customer_by_id(STORE_1_WAK_IN_CUSTOMER_ID);
            $address_book=$this->store_model->get_address_book_by_id($store_1_walk_in_customer->customers_default_address_id);
            $new_order_id="";
            $payment_info=array();
            
            //Payment pre_check & before process
            if ($payment=="Credit Card"){                 
                $aim=new authorizenet_aim();
                $aim->cc_card_owner=$_REQUEST['authorizenet_aim_cc_owner'];
                if ($aim->process($products,$store_1_walk_in_customer,$address_book ,$order_total)==false){
                    $result['status'] = $this->config->item('fail');
                    $result['error'] = $aim->error;                    
                     echo json_encode($result);
                     return;
                }
                $new_order_id=$aim->new_order_id;
                                       
                $payment_info=$aim->order->info;                
            }
            else if ($payment=="moneyorder"){
                //$moneyorder=new moneyorder();
                $new_order_id=$this->store_model->get_new_order_id();
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
            
          
            $result=array();
            
            $ip_address=$_SERVER['REMOTE_ADDR'];
            //add record to store_1_sales_orders table
            
	
            $order_id=$this->store_model->create_in_store_1_sales_order($new_order_id,$order_total,$payment_info,
                    $order_tax,$ip_address,$payment_method);
            if (!$order_id || $order_id==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Order is not created";
                echo json_encode($result);
                return;
            }
            
            //add record to orders_products table
            $r_value=$this->store_model->create_orders_products($order_id,$products,$language_id);
            if (!$r_value || $r_value==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Order:".$order_id. " Order_product is not created";
                echo json_encode($result);
                return;
            }
            
            //add record to orders_products_attributes table
            $r_value=$this->store_model->create_orders_products_attributes($order_id,$products,$language_id);
            if (!$r_value || $r_value==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Order_products created but order_products_attributes is not created";
                echo json_encode($result);
                return;
            }
            
            //add record to orders_total table
            $r_value=$this->store_model->create_orders_total($order_id,$order_total,0,$order_total);
            if (!$r_value || $r_value==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Orders Total is not created";
                echo json_encode($result);
                return;
            }
            
            //add to orders_status_history table
            $r_value=$this->store_model->create_status_history($order_id,PROCESSING_ORDER_STATUS_ID);
            if (!$r_value || $r_value==0){
                $result['status']=$this->config->item('fail');
                $result['error']="Order Status History is not created";
                echo json_encode($result);
                return;
            }
            
            //deduct option values from Store #1 option value tables
            if ($this->store_model->update_option_values($products,$language_id)){
                                
                //add one order record in main order table under the status of "In Store Sales #1"                  
                $result['status']=$this->config->item('success');
            }
            
             /*if ($payment=="Credit Card"){                 
                $aim->order_status=In_Store_Sales_1_ORDER_STATUS_ID;
                if (!$aim->after_process()){
                    $result['status'] = $this->config->item('fail');
                    $result['error'] ="Afer process failed";
                     echo json_encode($result);
                //    return;
                }
                
            }
            else if ($payment=="moneyorder"){
                //do nothing
            }
            else{
                $result['status'] = $this->config->item('fail');   
                $result['error'] = "Payment method is not specified.";
                echo json_encode($result);
                return;
            }*/
            
            echo json_encode($result);
   
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */