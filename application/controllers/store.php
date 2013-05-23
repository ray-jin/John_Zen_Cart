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
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */