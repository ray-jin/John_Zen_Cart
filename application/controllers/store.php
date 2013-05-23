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
                        
            $parent_id=isset($_GET['parent_id']) ? $_GET['parent_id'] : 0; //default 0: top level
            $language_id=isset($_GET['language_id']) ? $_GET['language_id'] : 1; //default 1: English
      
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
                        
            
           if (!isset($_GET['category_id'])){
                $result['status'] = $this->config->item('fail');   
                $result['error'] = $this->config->item('invalid_params');
                echo json_encode($result);  
                return;
           }
           $category_id=$_GET['category_id'];
            $language_id=isset($_GET['language_id']) ? $_GET['language_id'] : 1; //default 1: English
      
            $result['products']= $this->store_model->list_products($category_id, $language_id);
            $result['status'] = $this->config->item('success');
                                              
            echo json_encode($result);  
	}
	
	
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */