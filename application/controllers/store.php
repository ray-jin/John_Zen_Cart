<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Store extends CI_Controller
{
    
      
	function __construct()
	{
		parent::__construct();

		//$this->load->library('security');
		$this->load->database();
                $this->load->library('session');
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
            
              $list[$i]['options_id']=$row['options_id'];
              $list[$i]['options_name']=$row['options_name'];
              $options_values_list=$this->store_model->list_products_attributes_options_values($product_id,$row['options_id'],$language_id);
              $list[$i]['values_list']=$options_values_list;
                $i++;
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
              
            $product_ids=$_REQUEST['product_id'];
            $quantities=$_REQUEST['quantity'];
            $language_id=$_REQUEST['language_id'];
             
            if (sizeof($product_ids)!=sizeof($quantities) || !isset($_REQUEST['product_id']) 
                    || !isset($_REQUEST['quantity']) || !isset($_REQUEST['language_id']) 
                    || !isset($_REQUEST['order_total'])|| !isset($_REQUEST['order_tax'])
                    || !isset($_REQUEST['payment_method'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
            }
            
            $order_total=$_REQUEST['order_total'];
            $order_tax=$_REQUEST['order_tax'];
            $payment_method=$_REQUEST['payment_method'];
            
            
            $products=array(); $i=0;
            foreach ($product_ids as $product_id) {
                $option_value_ids=$_REQUEST['pid'.$product_id];   //option values
                $option_ids=$_REQUEST['pid_option'.$product_id]; //option name
                
                $products[$i]=array(
                    'product_id' => $product_id,
                    'quantity' => $quantities[$i],
                    'option_value_ids' => $option_value_ids,
                    'option_ids' => $option_ids,
                    );

                $i++;
            }
            
            $result=array();
            
            $ip_address=$_SERVER['REMOTE_ADDR'];
            //add record to store_1_sales_orders table
            $order_id=$this->store_model->create_in_store_1_sales_order($order_total,$order_tax,$payment_method,$ip_address);
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
            $r_value=$this->store_model->create_status_history($order_id);
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
            
            echo json_encode($result);
   
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */