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
        private $tbl_products_options_values_name="products_options_values_store_1";
        private $tbl_customers_name="customers";
        private $tbl_address_book_name="address_book";
        private $tbl_countries_name="countries";
        private $tbl_zones_name="zones";
        private $tbl_orders_name="orders";
        private $tbl_orders_products_name="orders_products";
        private $tbl_orders_products_attributes_name="orders_products_attributes";
        private $tbl_orders_total_name="orders_total";
        private $tbl_orders_status_history_name="orders_status_history";
        

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
                
		$query = $this->db->get($this->tbl_products_name);

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
            
            $list=array();
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
                
                 $i=0;
             
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
                if ($option_value!=null && $option_value->products_options_values_quantity>0){
                    $list[$i] = array( 
                                'options_values_id' => $row->options_values_id,
                                'options_values_name' =>isset($option_value) ? $option_value->products_options_values_name : "Invalid",                                
                    );
                    $i++;
                }
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
        
         /**
	 * update order status
         * @param       array of products (including option_values)
	 * @param	int
	 * @return	object
	 */
	function update_order_status($order_id,$order_status)
	{
            
                $sql="UPDATE `orders` SET `orders_status` =".$order_status;
                $sql.=" where `orders_id` = ".$order_id;

                $this->db->query($sql);
                                
                return true;
	}
        
         /**
	 * update option values table (store 1)
         * @param       array of products (including option_values)
	 * @param	int
	 * @return	object
	 */
	function update_option_values($products,$language_id)
	{
            //repeat products and update all option_values with it
            
		foreach ($products as $product){
                    foreach ($product['option_value_ids'] as $option_value_id){
                        // get current option values quantity
                        
                        $sql= "select products_options_values_quantity quantity                            
                            from products_options_values_store_1 where products_options_values_id = ".$option_value_id;
                        $result=$this->db->query($sql);
                        $row = $result->row_array();
                        $new_quantity = $row['quantity']-$product['quantity'];
                        
                        
                        $sql="UPDATE `products_options_values_store_1`  SET `products_options_values_quantity` = ".$new_quantity;
                        $sql.=" where `products_options_values_id` = ".$option_value_id." and `language_id` =  ".$language_id;
                        
                        $this->db->query($sql);
                        
                        //insert one item on products_options_values sold store_1 table                        
                        
                        $this->db->set('product_id', $product['product_id']);
                        $this->db->set('from_products_options_values_id', $option_value_id); //need to consider
                        $this->db->set('products_options_values_id', $option_value_id);
                        $this ->db->set('sold_qty',$product['quantity']);
                        $this->db->set('stock_left', $new_quantity);
                        
                        if (!$this->db->insert("products_options_values_sold_store_1")) {
                             return false;
                        }  

                    }
                }
                                
                return true;
	}
        
         /**
	 * 
         * @param       customer_id	 
         *  
	 * @return	object
	 */
	function get_customer_by_id($customer_id)
	{
           $this->db->where('customers_id', $customer_id);
                
           $query = $this->db->get($this->tbl_customers_name);

            if ($query->num_rows() == 1) return $query->row();
            return NULL;
	}
        
         /**
	 * 
         * @param       customer_id	 
         *  
	 * @return	object
	 */
	function get_address_book_by_id($id)
	{
           $this->db->where('address_book_id', $id);
                
           $query = $this->db->get($this->tbl_address_book_name);

            if ($query->num_rows() == 1) return $query->row();
            return NULL;
	}
        
        /**
	 * 
         * @param       country_id	 
         *  
	 * @return	object
	 */
	function get_country_by_id($id)
	{
           $this->db->where('countries_id', $id);
                
           $query = $this->db->get($this->tbl_countries_name);

            if ($query->num_rows() == 1) return $query->row();
            return NULL;
	}
        
        /**
	 * 
         * @param       zone_id
         *  
	 * @return	object
	 */
	function get_zone_by_id($id)
	{
           $this->db->where('zone_id', $id);
                
           $query = $this->db->get($this->tbl_zones_name);

            if ($query->num_rows() == 1) return $query->row();
            return NULL;
	}
        
        
        /**
	 * get orders record by orders_id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_order_by_id($id)
	{
		$this->db->where('orders_id', $id);                
                
		$query = $this->db->get($this->tbl_orders_name);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
        
        /**
         * add products detail to "orders_products" table 
         * @param       orders_id : order id
         * @param       products : array of products
	 * @return	object
         */
        function create_orders_products($order_id,$products,$language_id){
            
            $orders_products_list=array();
            $i=0;
            
            foreach ($products as $aproduct){
                $product=$this->get_products_by_products_id($aproduct['product_id']);
                $product_desc=$this->get_products_description($product->products_id, $language_id);
                                
                $this->db->set('orders_id', $order_id);
                $this->db->set('products_id', $product->products_id);
                $this->db->set('products_model', $product->products_model);
                $this->db->set('products_name', $product_desc->products_name);
                $this->db->set('products_price', $product->products_price);
                $this->db->set('final_price', $aproduct['final_price']);
                $this->db->set('products_tax', 0); // default
                $this->db->set('products_quantity', $aproduct['quantity']);
                $this->db->set('onetime_charges', 0); // default
                $this->db->set('products_priced_by_attribute', 0); // default
                $this->db->set('product_is_free', 0); //default
                $this->db->set('products_discount_type', 0); //default
                $this->db->set('products_discount_type_from', 0); //default
                $this->db->set('products_prid', $aproduct['quantity'].":"); //not sure about products_prid
                if (!$this->db->insert($this->tbl_orders_products_name)) {
                   return null;
                }
                
                $orders_products_list[$product->products_id] = $this->db->insert_id();
                   //return $order_id;
                 $i++;
            }
            return $orders_products_list; //success
        }
        
       
        /**
	 * add one order table into main zen cart order table
         * @param       customer_id : registered customer id	 
	 * @return	order object
	 */
	function create_in_store_1_order($new_order_id,$order_total, $order_status, $payment_info,$order_tax,
                $ip_address,$payment_method,$payment_module_code="cc",
                $shipping_method="FREE SHIPPING! (Free Shipping Only)",
                $shipping_module_code="freeshipper")
	{
            if ($payment_method=="credit") 
                $payment_method="Credit Card";
            else if ($payment_method=="cash") 
                $payment_method="CASH";
       
            if (!$customer=$this->get_customer_by_id(STORE_1_WAK_IN_CUSTOMER_ID))
                return false;
            
            $address_book=$this->get_address_book_by_id($customer->customers_default_address_id);
            if (!$address_book)
                return false;
            
            if (!$country=$this->get_country_by_id($address_book->entry_country_id))
                return false;
                
            if (!$zone=$this->get_zone_by_id($address_book->entry_zone_id))
                return false;
            
            $this->db->set('orders_id', $new_order_id);
            $this->db->set('customers_id', $customer->customers_id);
            $this->db->set('customers_name', $customer->customers_firstname."  ".$customer->customers_lastname);
            $this->db->set('customers_company', $address_book->entry_company);
            $this->db->set('customers_street_address', $address_book->entry_street_address );
            $this->db->set('customers_suburb', $address_book->entry_suburb);
            $this->db->set('customers_city', $address_book->entry_city);
            $this->db->set('customers_postcode', $address_book->entry_postcode);
            $this->db->set('customers_state',$zone->zone_name );
            $this->db->set('customers_country', $country->countries_name);
            $this->db->set('customers_telephone', $customer->customers_telephone);
            $this->db->set('customers_email_address', $customer->customers_email_address);
            $this->db->set('customers_address_format_id', 2); //default
            $this->db->set('delivery_name', $customer->customers_firstname."  ".$customer->customers_lastname);
            $this->db->set('delivery_company', $address_book->entry_company);
            $this->db->set('delivery_street_address', $address_book->entry_street_address);
            $this->db->set('delivery_suburb', $address_book->entry_suburb);
            $this->db->set('delivery_city', $address_book->entry_city);
            $this->db->set('delivery_postcode', $address_book->entry_postcode);
            $this->db->set('delivery_state', $zone->zone_name);
            $this->db->set('delivery_country', $country->countries_name);
            $this->db->set('delivery_address_format_id', 2); //default
            $this->db->set('billing_name', $customer->customers_firstname."  ".$customer->customers_lastname);
            $this->db->set('billing_company', $address_book->entry_company);
            $this->db->set('billing_street_address', $address_book->entry_street_address);
            $this->db->set('billing_suburb', $address_book->entry_suburb);
            $this->db->set('billing_city', $address_book->entry_city);
            $this->db->set('billing_postcode', $address_book->entry_postcode);
            $this->db->set('billing_state', $zone->zone_name);
            $this->db->set('billing_country', $country->countries_name);
            $this->db->set('billing_address_format_id', 2); //default
            $this->db->set('payment_method', $payment_method); // credit card
            $this->db->set('payment_module_code', $payment_module_code); //cc 
            $this->db->set('shipping_method', $shipping_method);
            $this->db->set('shipping_module_code', $shipping_module_code);
            $this->db->set('coupon_code', '');
            $this->db->set('cc_type', '' );
            $this->db->set('cc_owner', '');
            $this->db->set('cc_number','');
            $this->db->set('cc_expires', "");//$payment_info['cc_expires']);
            $this->db->set('cc_cvv', "");//$payment_info['cc_cvv']);
            $date = new DateTime();
            $datetime = $date->format('Y-m-d H:i:s');
            $this->db->set('last_modified', $datetime);
            $this->db->set('date_purchased', $datetime);
            $this->db->set('orders_status', $order_status);
            $this->db->set('currency', "USD");
            $this->db->set('currency_value', 1);
            $this->db->set('order_total', $order_total);
            $this->db->set('order_tax', $order_tax);
            $this->db->set('ip_address', $ip_address);
            //$this->db->set('admin_complete_order_datetime', $datetime);
            
            if ($this->db->insert($this->tbl_orders_name)) {
                   //$order_id = $this->db->insert_id();                                  
                   //return $order_id;
                   return $new_order_id;
            }
            
            return "-1";
	}
        
          
         /**
	 * get products_attributes record by products_id & option_id, & option_value_id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function get_product_attribute($product_id,$option_id,$option_value_id)
	{
		$this->db->where('products_id', $product_id);
                $this->db->where('options_id', $option_id);
                $this->db->where('options_values_id', $option_value_id);
                
		$query = $this->db->get($this->tbl_products_attributes_name);

                if ($query->num_rows() == 1) return $query->row();
		return NULL;
	}
        
        /**
         * add products detail to "orders_products" table 
         * @param       orders_id : order id
         * @param       products : array of products
	 * @return	object
         */
        function create_orders_products_attributes($order_id,$products,$language_id,$orders_products_list){
            
            foreach ($products as $aproduct){
                $product=$this->get_products_by_products_id($aproduct['product_id']);
                $product_desc=$this->get_products_description($product->products_id, $language_id);
                                
                $i=0;
                foreach ($aproduct['option_value_ids'] as $option_value_id){
                    $option_id=$aproduct['option_ids'][$i];
                    $product_attribute=$this->get_product_attribute($product->products_id, $option_id, $option_value_id);
                    $option=$this->get_products_option($option_id, $language_id);
                    $option_value=$this->get_products_options_value($option_value_id, $language_id);
                    
                    $this->db->set('orders_id', $order_id);
                    $this->db->set('orders_products_id', $orders_products_list[$product->products_id]);
                    $this->db->set('products_options', $option->products_options_name);
                    $this->db->set('products_options_values', $option_value->products_options_values_name);
                    $this->db->set('options_values_price', $product_attribute->options_values_price); 
                    $this->db->set('price_prefix', $product_attribute->price_prefix); //default
                    
                    $this->db->set('product_attribute_is_free', $product_attribute->product_attribute_is_free);
                    $this->db->set('products_attributes_weight', $product_attribute->products_attributes_weight);
                    $this->db->set('products_attributes_weight_prefix', $product_attribute->products_attributes_weight_prefix);
                    $this->db->set('attributes_discounted', $product_attribute->attributes_discounted);
                    $this->db->set('attributes_price_base_included', $product_attribute->attributes_price_base_included);
                    $this->db->set('attributes_price_onetime', $product_attribute->attributes_price_onetime);
                    $this->db->set('attributes_price_factor', $product_attribute->attributes_price_factor);
                    $this->db->set('attributes_price_factor_offset', $product_attribute->attributes_price_factor_offset);
                    $this->db->set('attributes_price_factor_onetime', $product_attribute->attributes_price_factor_onetime);
                    $this->db->set('attributes_price_factor_onetime_offset', $product_attribute->attributes_price_factor_onetime_offset);
                    $this->db->set('attributes_qty_prices', $product_attribute->attributes_qty_prices);
                    $this->db->set('attributes_qty_prices_onetime', $product_attribute->attributes_qty_prices_onetime);
                    $this->db->set('attributes_price_words', $product_attribute->attributes_price_words);
                    $this->db->set('attributes_price_words_free', $product_attribute->attributes_price_words_free);
                    $this->db->set('attributes_price_letters', $product_attribute->attributes_price_letters);
                    $this->db->set('attributes_price_letters_free', $product_attribute->attributes_price_letters_free);
                    $this->db->set('products_options_id', $option_id);
                    $this->db->set('products_options_values_id', $option_value_id);
                    $this->db->set('products_prid', $aproduct['quantity'].":");
                    
                    if (!$this->db->insert($this->tbl_orders_products_attributes_name)) {
                        return 0;
                    }
                    $i++;
                }               
            }
            return 1; //success
        }
        
         /**
         * add products detail to "orders_products" table 
         * @param       orders_id : order id
         * @param       sub_total : sub total
         * @param       free_shipping : shippng cost
         * @param       total   : total
	 * @return	object
         */
        function create_orders_total($order_id,$sub_total,$free_shipping_cost,$sales_tax,$total){                       
         
            //*****Insert Sub total*********
            $this->db->set('orders_id', $order_id);
            $this->db->set('title', "Sub-Total:");
            $this->db->set('text', "$".number_format($sub_total,2));
            $this->db->set('value', $sub_total);
            $this->db->set('class', "ot_subtotal");
            $this->db->set('sort_order', 1);

            if (!$this->db->insert($this->tbl_orders_total_name)) {
                return 0;
            }
        
            //*****Insert Shipping cost*********
            $this->db->set('orders_id', $order_id);
            $this->db->set('title', "Free Shipping Options (Free Shipping):");
            $this->db->set('text', "$".number_format($free_shipping_cost,2));
            $this->db->set('value', $free_shipping_cost);
            $this->db->set('class', "ot_shipping");
            $this->db->set('sort_order', 2);

            if (!$this->db->insert($this->tbl_orders_total_name)) {
                return 0;
            }
            
            //*****Insert Sales Tax*********
            $this->db->set('orders_id', $order_id);
            $this->db->set('title', "Sales Tax");
            $this->db->set('text', "$".number_format($sales_tax,2));
            $this->db->set('value', $sales_tax);
            $this->db->set('class', "ot_tax");
            $this->db->set('sort_order', 2);

            if (!$this->db->insert($this->tbl_orders_total_name)) {
                return 0;
            }
            
            //*************Total cost**************
            $this->db->set('orders_id', $order_id);
            $this->db->set('title', "Total:");
            $this->db->set('text', "$".number_format($total,2));
            $this->db->set('value', $total);
            $this->db->set('class', "ot_total");
            $this->db->set('sort_order', 3);

            if (!$this->db->insert($this->tbl_orders_total_name)) {
                return 0;
            }
           
            return 1; //success
        }
        
         /**         
         * @param       orders_id : order id	 
         */
        function create_status_history($order_id,$status_id,$comment=""){
            
            $this->db->set('orders_id', $order_id);
            $this->db->set('orders_status_id', $status_id);
            
            $date = new DateTime();
            $datetime = $date->format('Y-m-d H:i:s');
            
            $this->db->set('date_added', $datetime);
            $this->db->set('customer_notified', -1);
            $this->db->set('comments', $comment);

            if (!$this->db->insert($this->tbl_orders_status_history_name)) {
                return 0;
            }
           
            return 1; //success
        }
        
                 
       
        
         /**
	 * get products_description record by products_id & language_id
         * @param       int
	 * @param	int
	 * @return	object
	 */
	function list_all_products_options_value($language_id)
	{		
                $this->db->where('language_id', $language_id);
		$query = $this->db->get($this->tbl_products_options_values_name);
		return $query->result();
	}
        
       function calculate_DA($option_value_id){
                                   
            //insert one item on products_options_values_store_1 table            
            
           $sql= "SELECT sum(sold_qty) AS sold_qty FROM products_options_values_sold_store_1 "
                 ." WHERE from_products_options_values_id=".$option_value_id
                 ." AND DATEDIFF(CURRENT_TIMESTAMP,create_date)<".STORE_1_RESTOCK_DAYS;
           $result=$this->db->query($sql);
           $row = $result->row_array();
           if ($row)
                return $row['sold_qty']/STORE_1_CHECK_BACK_DAYS;
           else
               return 0; // it is the beginning so avg_aty=sold_quantity of this time
           
       }
        
        //travels all option values and if current quantity < lead time  * daily average, create re order on main order table
        //should daily average within STORE_1_RESTOCK_DAYS

        function restock_values($products,$language_id){
            
            foreach ($products as $product){
                    foreach ($product['option_value_ids'] as $option_value_id){
                        $da=$this->calculate_DA($option_value_id);
                        if ($da==0)
                            $da=$product['quantity']/STORE_1_CHECK_BACK_DAYS; // it is the beginning so avg_qty = quantity of this time.
                        
                        $option_value=$this->get_products_options_value($option_value_id, $language_id);
                        $cur_quantity=$option_value->products_options_values_quantity;
                        $leadtime=$option_value->pov_leadtime;
                        
                        if ($cur_quantity<$leadtime*$da){ // cur_quantity < reorder quantity = lead time x daily average
                            
                            $reorder_qty=round(STORE_1_RESTOCK_DAYS*$da+1); //convert it integer value
                            
                            //**********Update option values*********************
                            $qry = array(
                                        'products_options_values_quantity'=> $cur_quantity+$reorder_qty,                            
                                );				
                             $this->db->where('products_options_values_id', $option_value->products_options_values_id);           
                             $this->db->update("products_options_values_store_1", $qry);

                             //Take minus from main product options
                            $sql="UPDATE `products_options_values_bk`  SET `products_options_values_quantity` = `products_options_values_quantity`- ";
                                $sql.=$reorder_qty." where `products_options_values_id` = ".$option_value->products_options_values_id." and `language_id` =  ".$language_id;

                           $this->db->query($sql);
                           
                            //*************Create new reorder record**************
                           
                           $this->db->set('product_id', $product['product_id']);
                           $this->db->set('products_options_values_id', $option_value_id);
                           $this->db->set('reorder_qty', $reorder_qty);                            

                            if (!$this->db->insert("products_options_values_reorder_store_1")) {
                                return 0;
                            }                            
                        }
                    }
            }
            return 1;
            
        }
       
        
        function get_next_insert_idx($tbl_name) {

		$next_increment = 0;
		$strSql = "SHOW TABLE STATUS WHERE Name='$tbl_name'";
		$query = $this->db->query($strSql);
		$row = $query->row_array();
		$next_increment = $row['Auto_increment'];
		
		return $next_increment;
	}
}

/* End of file users.php */
/* Location: ./application/models/auth/users.php */