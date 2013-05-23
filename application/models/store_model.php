<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Users
 *
 * This model represents user authentication data. It operates the following tables:
 * - user account data,
 * - user profiles
 *
 * @package	Tank_auth
 * @author	Ilya Konyukhov (http://konyukhov.com/soft/)
 */
class Store_model extends CI_Model
{
	
        
        private $tbl_category_name="categories";
        private $tbl_category_description_name="categories_description";
        private $tbl_product_to_category_name="products_to_categories";
        private $tbl_products_name="products";
        private $tbl_products_description_name="products_description";
        private $tbl_products_attributes_name="products_attributes";
        private $tbl_products_options_name="products_options";
        private $tbl_products_options_values_name="products_options_values";

	function __construct()
	{
            parent::__construct();

            $ci =& get_instance();
            
            
            $this->load->helper('url');
            
	}
	
        
        /**
	 * List category records
	 * status=1 (enabled)
         * by sort_order
	 * @param	int
	 * @return	array
	 */
	function list_categories($parent_id,$language_id)
	{
		/*$this->db->where('categories_status', 1);
                $this->db->where('parent_id', $parent_id);
                $this->db->order_by("sort_order", "asc"); 
                $this->db->order_by("categories_id", "asc");
                */
            
             $sql = "SELECT c.categories_id, c.categories_image, cd.categories_name  FROM (`".$this->tbl_category_name."` c) 
                 , `".$this->tbl_category_description_name."` cd 
                WHERE `categories_status` =  1   AND c.`parent_id`=".$parent_id." and cd.`categories_id`=c.`categories_id` 
ORDER BY c.`sort_order` asc , cd.`categories_name` ASC";
             
              $query = $this->db->query($sql);
             error_log($sql);
		//$query = $this->db->get($this->tbl_category_name);

                $list=array(); $i=0;
                foreach ($query->result() as $row)
                {
                    
                    $category_desc=$this->get_category_description_by_category_id_and_language_id($row->categories_id, $language_id);
                    
                    $list[$i] = array( 'categories_id' => $row->categories_id,
                                'categories_image' => isset($row->categories_image)? $row->categories_image : "",
                                'categories_name' =>isset($category_desc) ? $category_desc->categories_name : "Invalid",
                                //'categories_desc' =>isset($category_desc) ? $category_desc->categories_description : "Invalid",
                    );
                    $i++;
                }
		return $list;
	}
        
        /**
	 * get category_description record by id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_category_description_by_category_id_and_language_id($category_id,$language_id)
	{
		$this->db->where('categories_id', $category_id);
                $this->db->where('language_id', $language_id);
                
		$query = $this->db->get($this->tbl_category_description_name);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
        
        /**
	 * List product id which belongs to special category
	 * @param	int
	 * @return	array
	 */
       function list_products_to_categories($categories_id) {
           
                $this->db->where('categories_id', $categories_id);                
                
		$query = $this->db->get($this->tbl_product_to_category_name);

                $list=array(); $i=0;
                foreach ($query->result() as $row)
                {                    
                    
                    $list[$i] = array( 'products_id' => $row->products_id,                                
                    );
                    $i++;
                }
		return $list;

       }
       
       /**
	 * get category_description record by id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_products_by_products_id($products_id)
	{
		$this->db->where('products_id', $products_id);                
                
		$query = $this->db->get($this->tbl_);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
        
       /**
	 * List category records
	 * status=1 (enabled)
         * by products_sort_order
	 * @param	int
	 * @return	array
	 */
	function list_products($categories_id,$language_id)
	{
            
            
                $products_list=$this->list_products_to_categories($categories_id); //get list of product id

                 if (sizeof($products_list)==0)
                    return $list;
                 
                $p_list= array(); 
                $i=0; $in_string="AND p.`products_id` IN (";
                
                foreach ($products_list as $row)
                {                    
                    //$p_list[$i] = $row['products_id'];
                    $i++;
                    $in_string.="'".$row['products_id']."',";
                }
                $in_string.="'')";
                
                /*SELECT p.products_id, p.products_image, `pd`.products_name, p.`products_sort_order` FROM (`products` p) , `products_description` pd 
WHERE `products_status` =  1  AND p.`products_id` IN ('123', '125', '134', '136', '141', '142')  AND p.`products_id`=pd.`products_id`
ORDER BY p.`products_sort_order` , pd.`products_name` ASC*/
                
                $list=array(); $i=0;
             
              /*  $this->db->where('products_status', 1);             
                $this->db->where_in('products_id', $p_list);                
                $this->db->order_by('products_sort_order');
                $this->db->from($this->tbl_products_name);*/
                
                $sql = "SELECT p.products_id, p.products_image, p.products_quantity, p.products_price, p.products_price_sorter,
                    `pd`.products_name, p.`products_sort_order` FROM (`products` p) , `products_description` pd 
WHERE `products_status` =  1 ".$in_string."  AND p.`products_id`=pd.`products_id`
ORDER BY p.`products_sort_order` , pd.`products_name` ASC";
               
                $query = $this->db->query($sql);
                
		///= $this->db->get();
                
                foreach ($query->result() as $row)
                {
                                        
                    $product_desc=$this->get_products_description($row->products_id, $language_id);
                    
                    $products_image_medium=$this->getFullImageUrl($row->products_image);
                    
                    $list[$i] = array( 'categories_id' => $categories_id,
                                'products_id' => $row->products_id,                                
                                'products_image' => $products_image_medium,//$row->products_image,
                                'products_quantity' => $row->products_quantity,
                                'products_name' =>isset($product_desc) ? $product_desc->products_name : "Invalid",
                                'products_price' => $row->products_price,
                                'products_price_sorter' => $row->products_price_sorter,
                                //'categories_desc' =>isset($category_desc) ? $category_desc->categories_description : "Invalid",
                    );
                    $i++;
                }
		return $list;
	}
        
        /*
         * 
         * 
         */
        function getFullImageUrl($products_image){
            $products_image_extension = substr($products_image, strrpos($products_image, '.'));
                    
            $products_image_base = str_replace($products_image_extension, '', $products_image);
            $products_image_medium = $products_image_base . IMAGE_SUFFIX_MEDIUM . $products_image_extension;
            //$products_image_large = $products_image_base . IMAGE_SUFFIX_LARGE . $products_image_extension;

            // check for a medium image else use small
            if (!file_exists(DIR_WS_IMAGES . 'medium/' . $products_image_medium)) {
                $products_image_medium = DIR_WS_IMAGES . $products_image;
            } else {
                $products_image_medium = DIR_WS_IMAGES . 'medium/' . $products_image_medium;
            }
            $products_image_medium=DIR_FS_CATALOG.$products_image_medium;
            return $products_image_medium;
        }
        /**
	 * get products_description record by products_id & language_id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_products_description($products_id,$language_id)
	{
		$this->db->where('products_id', $products_id);
                $this->db->where('language_id', $language_id);
                
		$query = $this->db->get($this->tbl_products_description_name);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
        
        
        /**
	 * List product_attributes records sorted by options_id with array of option_values_id
	 * status=0 (enabled)
         * by products_options_sort_order
	 * @param	int
	 * @return	array
	 */
	function list_products_attributes_options($product_id,$language_id)
	{
            //SELECT DISTINCT (options_id)  FROM products_attributes WHERE products_id=156 ORDER BY products_options_sort_order
            $this->db->where('attributes_status', 0); // 0 : available               
            $this->db->where('products_id', $product_id);
            $this->db->select('options_id');
            $this->db->distinct();
            
            $this->db->order_by('products_options_sort_order');

            $query = $this->db->get($this->tbl_products_attributes_name);
                            
            $list=array(); $i=0;
                                    
            foreach ($query->result() as $row)
            {                
                
               $option=$this->get_products_option($row->options_id, $language_id);
               $list[$i] = array( 
                                'options_id' => $row->options_id,
                                'options_name' =>isset($option) ? $option->products_options_name : "Invalid",                                
                    );
                $i++;
            }
           
            return $list;
	}
        
        /**
	 * List product_attributes records sorted by options_id with array of option_values_id
	 * status=0 (enabled)
         * by products_options_sort_order
	 * @param	int
	 * @return	array
	 */
	function list_products_attributes_options_values($product_id,$option_id,$language_id)
	{
            //SELECT DISTINCT (options_id)  FROM products_attributes WHERE products_id=156 ORDER BY products_options_sort_order
            
            $this->db->where('products_id', $product_id);
            $this->db->where('options_id', $option_id);                        
            
            $this->db->order_by('products_options_sort_order');

            $query = $this->db->get($this->tbl_products_attributes_name);
                            
            $list=array(); $i=0;
                                    
            foreach ($query->result() as $row)
            {         
                $option_value=$this->get_products_options_value($row->options_values_id, $language_id);
                $list[$i] = array( 
                                'options_values_id' => $row->options_values_id,
                                'options_values_name' =>isset($option_value) ? $option_value->products_options_values_name : "Invalid",                                
                    );
                $i++;
            }
           
            return $list;
	}
        
         /**
	 * get products_description record by products_id & language_id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_products_options_value($products_options_values_id,$language_id)
	{
		$this->db->where('products_options_values_id', $products_options_values_id);
                $this->db->where('language_id', $language_id);
                
		$query = $this->db->get($this->tbl_products_options_values_name);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
        
         /**
	 * get products_description record by products_id & language_id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_products_option($products_options_id,$language_id)
	{
		$this->db->where('products_options_id', $products_options_id);
                $this->db->where('language_id', $language_id);
                
		$query = $this->db->get($this->tbl_products_options_name);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
}

/* End of file users.php */
/* Location: ./application/models/auth/users.php */