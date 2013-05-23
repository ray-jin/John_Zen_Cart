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
		$this->db->where('categories_status', 1);
                $this->db->where('parent_id', $parent_id);
                $this->db->order_by("sort_order", "asc"); 
                $this->db->order_by("categories_id", "asc");
                
		$query = $this->db->get($this->tbl_category_name);

                $list=array(); $i=0;
                foreach ($query->result() as $row)
                {
                    
                    $category_desc=$this->get_category_description_by_category_id_and_language_id($row->categories_id, $language_id);
                    
                    $list[$i] = array( 'categories_id' => $row->categories_id,
                                'categories_image' => $row->categories_image,
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
                $i=0;
                foreach ($products_list as $row)
                {
                    //echo ($row['products_id'])."->";
                    $p_list[$i] = $row['products_id'];
                    $i++;
                }                 
                
                 $list=array(); $i=0;
                
                
                
                $this->db->where('products_status', 1);
                
                
               
                $this->db->where_in('products_id', $p_list);
                $this->db->order_by('products_sort_order');
            
		$query = $this->db->get($this->tbl_products_name);
               
                foreach ($query->result() as $row)
                {
                    $product_desc=$this->get_products_description_by_products_id_and_language_id($row->products_id, $language_id);
                    $list[$i] = array( 'products_id' => $row->products_id,
                                'products_image' => $row->products_image,
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
        
        /**
	 * get products_description record by products_id & language_id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_products_description_by_products_id_and_language_id($products_id,$language_id)
	{
		$this->db->where('products_id', $products_id);
                $this->db->where('language_id', $language_id);
                
		$query = $this->db->get($this->tbl_products_description_name);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
        
}

/* End of file users.php */
/* Location: ./application/models/auth/users.php */