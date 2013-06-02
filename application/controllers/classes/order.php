<?php
/**
 * File contains the order-processing class ("order")
 *
 * @package classes
 * @copyright Copyright 2003-2010 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: order.php 16580 2010-06-02 21:12:23Z drbyte $
 */
/**
 * order class
 *
 * Handles all order-processing functions
 *
 * @package classes
 */

require_once 'application/controllers/modules/TaxCloud/func.taxcloud.php';
require_once 'shopping_cart.php';
require_once 'currencies.php';
require_once 'shipping.php';

if (!defined('IS_ADMIN_FLAG')) {
  die('Illegal Access');
}



class order extends base {
  var $info, $totals, $products, $customer, $delivery, $content_type, $email_low_stock, $products_ordered_attributes,
  $products_ordered, $products_ordered_email, $attachArray, $cart, $currencies,$coupon_code,$shipping;

  function order($order_id = '') {
    $this->info = array();
    $this->totals = array();
    $this->products = array();
    $this->customer = array();
    $this->delivery = array();
    $this->currencies= new currencies();
    $this->shipping= new shipping();
    //$this->

    if (zen_not_null($order_id)) {
      $this->query($order_id);
    } else {
        //create new shoppingCart & add items onit
            $this->cart=new shoppingCart();            

            $product_ids=$_REQUEST['product_id'];
            $quantities=$_REQUEST['quantity'];
            $i=0;

            foreach ($product_ids as $products_id){
                
                $this->cart->add_cart($products_id, $quantities[$i]);
                $i++;
            }

            
        
        $this->cart();
    }
  }

  function query($order_id) {
  

    $order_id = zen_db_prepare_input($order_id);

    $order_query = "select customers_id, customers_name, customers_company,
                         customers_street_address, customers_suburb, customers_city,
                         customers_postcode, customers_state, customers_country,
                         customers_telephone, customers_email_address, customers_address_format_id,
                         delivery_name, delivery_company, delivery_street_address, delivery_suburb,
                         delivery_city, delivery_postcode, delivery_state, delivery_country,
                         delivery_address_format_id, billing_name, billing_company,
                         billing_street_address, billing_suburb, billing_city, billing_postcode,
                         billing_state, billing_country, billing_address_format_id,
                         payment_method, payment_module_code, shipping_method, shipping_module_code,
                         coupon_code, cc_type, cc_owner, cc_number, cc_expires, currency, currency_value,
                         date_purchased, orders_status, last_modified, order_total, order_tax, ip_address
                        from " . TABLE_ORDERS . "
                        where orders_id = '" . (int)$order_id . "'";

    $order = $db->Execute($order_query);

    $totals_query = "select title, text, class
                         from " . TABLE_ORDERS_TOTAL . "
                         where orders_id = '" . (int)$order_id . "'
                         order by sort_order";

    $totals = $db->Execute($totals_query);

    while (!$totals->EOF) {


      if ($totals->fields['class'] == 'ot_coupon') {
        $coupon_link_query = "SELECT coupon_id
                from " . TABLE_COUPONS . "
                where coupon_code ='" . $order->fields['coupon_code'] . "'";
        $coupon_link = $db->Execute($coupon_link_query);
        $zc_coupon_link = '<a href="javascript:couponpopupWindow(\'' . zen_href_link(FILENAME_POPUP_COUPON_HELP, 'cID=' . $coupon_link->fields['coupon_id']) . '\')">';
      }
      $this->totals[] = array('title' => ($totals->fields['class'] == 'ot_coupon' ? $zc_coupon_link . $totals->fields['title'] . '</a>' : $totals->fields['title']),
                              'text' => $totals->fields['text'],
                              'class' => $totals->fields['class']);
      $totals->MoveNext();
    }

    $order_total_query = "select text, value
                             from " . TABLE_ORDERS_TOTAL . "
                             where orders_id = '" . (int)$order_id . "'
                             and class = 'ot_total'";


    $order_total = $db->Execute($order_total_query);


    $shipping_method_query = "select title, value
                                from " . TABLE_ORDERS_TOTAL . "
                                where orders_id = '" . (int)$order_id . "'
                                and class = 'ot_shipping'";


    $shipping_method = $db->Execute($shipping_method_query);

    $order_status_query = "select orders_status_name
                             from " . TABLE_ORDERS_STATUS . "
                             where orders_status_id = '" . $order->fields['orders_status'] . "'
                             and language_id = '" . (int)$_SESSION['languages_id'] . "'";

    $order_status = $db->Execute($order_status_query);

    $this->info = array('currency' => $order->fields['currency'],
                        'currency_value' => $order->fields['currency_value'],
                        'payment_method' => $order->fields['payment_method'],
                        'payment_module_code' => $order->fields['payment_module_code'],
                        'shipping_method' => $order->fields['shipping_method'],
                        'shipping_module_code' => $order->fields['shipping_module_code'],
                        'coupon_code' => $order->fields['coupon_code'],
                        'cc_type' => $order->fields['cc_type'],
                        'cc_owner' => $order->fields['cc_owner'],
                        'cc_number' => $order->fields['cc_number'],
                        'cc_expires' => $order->fields['cc_expires'],
                        'date_purchased' => $order->fields['date_purchased'],
                        'orders_status' => $order_status->fields['orders_status_name'],
                        'last_modified' => $order->fields['last_modified'],
                        'total' => $order->fields['order_total'],
                        'tax' => $order->fields['order_tax'],
                        'ip_address' => $order->fields['ip_address']
                        );

    $this->customer = array('id' => $order->fields['customers_id'],
                            'name' => $order->fields['customers_name'],
                            'company' => $order->fields['customers_company'],
                            'street_address' => $order->fields['customers_street_address'],
                            'suburb' => $order->fields['customers_suburb'],
                            'city' => $order->fields['customers_city'],
                            'postcode' => $order->fields['customers_postcode'],
                            'state' => $order->fields['customers_state'],
                            'country' => $order->fields['customers_country'],
                            'format_id' => $order->fields['customers_address_format_id'],
                            'telephone' => $order->fields['customers_telephone'],
                            'email_address' => $order->fields['customers_email_address']);

    $this->delivery = array('name' => $order->fields['delivery_name'],
                            'company' => $order->fields['delivery_company'],
                            'street_address' => $order->fields['delivery_street_address'],
                            'suburb' => $order->fields['delivery_suburb'],
                            'city' => $order->fields['delivery_city'],
                            'postcode' => $order->fields['delivery_postcode'],
                            'state' => $order->fields['delivery_state'],
                            'country' => $order->fields['delivery_country'],
                            'format_id' => $order->fields['delivery_address_format_id']);

    if (empty($this->delivery['name']) && empty($this->delivery['street_address'])) {
      $this->delivery = false;
    }

    $this->billing = array('name' => $order->fields['billing_name'],
                           'company' => $order->fields['billing_company'],
                           'street_address' => $order->fields['billing_street_address'],
                           'suburb' => $order->fields['billing_suburb'],
                           'city' => $order->fields['billing_city'],
                           'postcode' => $order->fields['billing_postcode'],
                           'state' => $order->fields['billing_state'],
                           'country' => $order->fields['billing_country'],
                           'format_id' => $order->fields['billing_address_format_id']);

    $index = 0;
    $orders_products_query = "select orders_products_id, products_id, products_name,
                                 products_model, products_price, products_tax,
                                 products_quantity, final_price,
                                 onetime_charges,
                                 products_priced_by_attribute, product_is_free, products_discount_type,
                                 products_discount_type_from
                                  from " . TABLE_ORDERS_PRODUCTS . "
                                  where orders_id = '" . (int)$order_id . "'
                                  order by orders_products_id";

    $orders_products = $db->Execute($orders_products_query);

    while (!$orders_products->EOF) {
      // convert quantity to proper decimals - account history
      if (QUANTITY_DECIMALS != 0) {
        $fix_qty = $orders_products->fields['products_quantity'];
        switch (true) {
          case (!strstr($fix_qty, '.')):
          $new_qty = $fix_qty;
          break;
          default:
          $new_qty = preg_replace('/[0]+$/', '', $orders_products->fields['products_quantity']);
          break;
        }
      } else {
        $new_qty = $orders_products->fields['products_quantity'];
      }

      $new_qty = round($new_qty, QUANTITY_DECIMALS);

      if ($new_qty == (int)$new_qty) {
        $new_qty = (int)$new_qty;
      }

      $this->products[$index] = array('qty' => $new_qty,
                                      'id' => $orders_products->fields['products_id'],
                                      'name' => $orders_products->fields['products_name'],
                                      'model' => $orders_products->fields['products_model'],
                                      'tax' => $orders_products->fields['products_tax'],
                                      'price' => $orders_products->fields['products_price'],
                                      'final_price' => $orders_products->fields['final_price'],
                                      'onetime_charges' => $orders_products->fields['onetime_charges'],
                                      'products_priced_by_attribute' => $orders_products->fields['products_priced_by_attribute'],
                                      'product_is_free' => $orders_products->fields['product_is_free'],
                                      'products_discount_type' => $orders_products->fields['products_discount_type'],
                                      'products_discount_type_from' => $orders_products->fields['products_discount_type_from']);

      $subindex = 0;
      $attributes_query = "select products_options_id, products_options_values_id, products_options, products_options_values,
                              options_values_price, price_prefix from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . "
                               where orders_id = '" . (int)$order_id . "'
                               and orders_products_id = '" . (int)$orders_products->fields['orders_products_id'] . "'";

      $attributes = $db->Execute($attributes_query);
      if ($attributes->RecordCount()) {
        while (!$attributes->EOF) {
          $this->products[$index]['attributes'][$subindex] = array('option' => $attributes->fields['products_options'],
                                                                   'value' => $attributes->fields['products_options_values'],
                                                                   'option_id' => $attributes->fields['products_options_id'],
                                                                   'value_id' => $attributes->fields['products_options_values_id'],
                                                                   'prefix' => $attributes->fields['price_prefix'],
                                                                   'price' => $attributes->fields['options_values_price']);

          $subindex++;
          $attributes->MoveNext();
        }
      }

      $this->info['tax_groups']["{$this->products[$index]['tax']}"] = '1';

      $index++;
      $orders_products->MoveNext();
    }
  }

  function cart() {
    
    $db=$GLOBALS ['db'];
   
   $this->content_type = $this->cart->get_content_type();
    

    $customer_address_query = "select c.customers_firstname, c.customers_lastname, c.customers_telephone,
                                    c.customers_email_address, ab.entry_company, ab.entry_street_address,
                                    ab.entry_suburb, ab.entry_postcode, ab.entry_city, ab.entry_zone_id,
                                    z.zone_name, co.countries_id, co.countries_name,
                                    co.countries_iso_code_2, co.countries_iso_code_3,
                                    co.address_format_id, ab.entry_state
                                   from (" . TABLE_CUSTOMERS . " c, " . TABLE_ADDRESS_BOOK . " ab )
                                   left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id)
                                   left join " . TABLE_COUNTRIES . " co on (ab.entry_country_id = co.countries_id)
                                   where c.customers_id = '" . STORE_1_WAK_IN_CUSTOMER_ID . "'
                                   and ab.customers_id = '" . STORE_1_WAK_IN_CUSTOMER_ID . "'
                                   and c.customers_default_address_id = ab.address_book_id";

    $customer_address = $db->query($customer_address_query)->result();

    $shipping_address_query = "select ab.entry_firstname, ab.entry_lastname, ab.entry_company,
                                    ab.entry_street_address, ab.entry_suburb, ab.entry_postcode,
                                    ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id,
                                    c.countries_id, c.countries_name, c.countries_iso_code_2,c.is_banned,
                                    c.countries_iso_code_3, c.address_format_id, ab.entry_state
                                   from " . TABLE_ADDRESS_BOOK . " ab
                                   left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id)
                                   left join " . TABLE_COUNTRIES . " c on (ab.entry_country_id = c.countries_id)
                                   where ab.customers_id = '" . STORE_1_WAK_IN_CUSTOMER_ID. "'
                                   and ab.address_book_id = '" . STORE_1_WAK_IN_CUSTOMER_ID. "'";

    $shipping_address = $db->query($shipping_address_query)->result();

    $billing_address_query = "select ab.entry_firstname, ab.entry_lastname, ab.entry_company,
                                   ab.entry_street_address, ab.entry_suburb, ab.entry_postcode,
                                   ab.entry_city, ab.entry_zone_id, z.zone_name, ab.entry_country_id,
                                   c.countries_id, c.countries_name, c.countries_iso_code_2,
                                   c.countries_iso_code_3, c.address_format_id, ab.entry_state
                                  from " . TABLE_ADDRESS_BOOK . " ab
                                  left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id)
                                  left join " . TABLE_COUNTRIES . " c on (ab.entry_country_id = c.countries_id)
                                  where ab.customers_id = '" . STORE_1_WAK_IN_CUSTOMER_ID . "'
                                  and ab.address_book_id = '" . STORE_1_WAK_IN_CUSTOMER_ID . "'";

    $billing_address = $db->query($billing_address_query)->result();
    //STORE_PRODUCT_TAX_BASIS

    switch (STORE_PRODUCT_TAX_BASIS) {
      case 'Shipping':

      $tax_address_query = "select ab.entry_country_id, ab.entry_zone_id
                              from " . TABLE_ADDRESS_BOOK . " ab
                              left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id)
                              where ab.customers_id = '" . STORE_1_WAK_IN_CUSTOMER_ID. "'
                              and ab.address_book_id = '" . (int)($this->content_type == 'virtual' ? STORE_1_WAK_IN_CUSTOMER_ID : STORE_1_WAK_IN_CUSTOMER_ID) . "'";
      $tax_address = $db->query($tax_address_query)->result();
      break;
      case 'Billing':

      $tax_address_query = "select ab.entry_country_id, ab.entry_zone_id
                              from " . TABLE_ADDRESS_BOOK . " ab
                              left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id)
                              where ab.customers_id = '" . (int)$_SESSION['customer_id'] . "'
                              and ab.address_book_id = '" . (int)$_SESSION['billto'] . "'";
      $tax_address = $db->Execute($tax_address_query);
      break;
      case 'Store':
      if ($billing_address->fields['entry_zone_id'] == STORE_ZONE) {

        $tax_address_query = "select ab.entry_country_id, ab.entry_zone_id
                                from " . TABLE_ADDRESS_BOOK . " ab
                                left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id)
                                where ab.customers_id = '" . (int)$_SESSION['customer_id'] . "'
                                and ab.address_book_id = '" . (int)$_SESSION['billto'] . "'";
      } else {
        $tax_address_query = "select ab.entry_country_id, ab.entry_zone_id
                                from " . TABLE_ADDRESS_BOOK . " ab
                                left join " . TABLE_ZONES . " z on (ab.entry_zone_id = z.zone_id)
                                where ab.customers_id = '" . (int)$_SESSION['customer_id'] . "'
                                and ab.address_book_id = '" . (int)($this->content_type == 'virtual' ? $_SESSION['billto'] : $_SESSION['sendto']) . "'";
      }
      $tax_address = $db->Execute($tax_address_query);
    }


    $class =$GLOBALS['payment'];
    $coupon_code="";
    if (isset($_REQUEST['cc_id'])) {
      $coupon_code_query = "select coupon_code
                              from " . TABLE_COUPONS . "
                              where coupon_id = '" . (int)$_REQUEST['cc_id'] . "'";

      $l=$db->query($coupon_code_query);
      if ($query->num_rows() == 0) {
          die(' Invalid Coupon Code');
      }
      else{
          $coupon_code=$l->row();
      }
    }
  
    $this->info = array('order_status' => DEFAULT_ORDERS_STATUS_ID,
                        'currency' => $GLOBALS ['currency'],
                        'currency_value' => $this->currencies->currencies[$GLOBALS['currency']]['value'],
                        'payment_method' => $GLOBALS[$class]->title,
                        'payment_module_code' => $GLOBALS[$class]->code,
                        'coupon_code' => $coupon_code!="" ? $coupon_code->coupon_code : "",
    //                          'cc_type' => (isset($GLOBALS['cc_type']) ? $GLOBALS['cc_type'] : ''),
    //                          'cc_owner' => (isset($GLOBALS['cc_owner']) ? $GLOBALS['cc_owner'] : ''),
    //                          'cc_number' => (isset($GLOBALS['cc_number']) ? $GLOBALS['cc_number'] : ''),
    //                          'cc_expires' => (isset($GLOBALS['cc_expires']) ? $GLOBALS['cc_expires'] : ''),
    //                          'cc_cvv' => (isset($GLOBALS['cc_cvv']) ? $GLOBALS['cc_cvv'] : ''),
                        //'shipping_method' => (is_array($_SESSION['shipping']) ? $_SESSION['shipping']['title'] : $_SESSION['shipping']),
                        'shipping_method' => $this->shipping->title,
                        'shipping_module_code' => (isset($_SESSION['shipping']['id']) && strpos($_SESSION['shipping']['id'], '_') > 0 ? $_SESSION['shipping']['id'] : $_SESSION['shipping']),
                        'shipping_cost' => $_SESSION['shipping']['cost'],
                        'subtotal' => 0,
                        'shipping_tax' => 0,
                        'tax' => 0,
                        'total' => 0,
                        'tax_groups' => array(),
                        'comments' => (isset($_SESSION['comments']) ? $_SESSION['comments'] : ''),
                        'ip_address' => $_SESSION['customers_ip_address'] . ' - ' . $_SERVER['REMOTE_ADDR']
                        );

    //print_r($GLOBALS[$class]);
    //echo $class;
    //print_r($GLOBALS);
    //echo $_SESSION['payment'];
    /*
    // this is set above to the module filename it should be set to the module title like Checks/Money Order rather than moneyorder
    if (isset($$_SESSION['payment']) && is_object($$_SESSION['payment'])) {
    $this->info['payment_method'] = $$_SESSION['payment']->title;
    }
    */

/*
// bof: move below calculations
    if ($this->info['total'] == 0) {
      if (DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID == 0) {
        $this->info['order_status'] = DEFAULT_ORDERS_STATUS_ID;
      } else {
        $this->info['order_status'] = DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID;
      }
    }
    if (isset($GLOBALS[$class]) && is_object($GLOBALS[$class])) {
      if ( isset($GLOBALS[$class]->order_status) && is_numeric($GLOBALS[$class]->order_status) && ($GLOBALS[$class]->order_status > 0) ) {
        $this->info['order_status'] = $GLOBALS[$class]->order_status;
      }
    }
// eof: move below calculations
*/
    $this->customer = array('firstname' => $customer_address->fields['customers_firstname'],
                            'lastname' => $customer_address->fields['customers_lastname'],
                            'company' => $customer_address->fields['entry_company'],
                            'street_address' => $customer_address->fields['entry_street_address'],
                            'suburb' => $customer_address->fields['entry_suburb'],
                            'city' => $customer_address->fields['entry_city'],
                            'postcode' => $customer_address->fields['entry_postcode'],
                            'state' => ((zen_not_null($customer_address->fields['entry_state'])) ? $customer_address->fields['entry_state'] : $customer_address->fields['zone_name']),
                            'zone_id' => $customer_address->fields['entry_zone_id'],
                            'country' => array('id' => $customer_address->fields['countries_id'], 'title' => $customer_address->fields['countries_name'], 'iso_code_2' => $customer_address->fields['countries_iso_code_2'], 'iso_code_3' => $customer_address->fields['countries_iso_code_3']),
                            'format_id' => (int)$customer_address->fields['address_format_id'],
                            'telephone' => $customer_address->fields['customers_telephone'],
                            'email_address' => $customer_address->fields['customers_email_address']);

    $this->delivery = array('firstname' => $shipping_address->fields['entry_firstname'],
                            'lastname' => $shipping_address->fields['entry_lastname'],
                            'company' => $shipping_address->fields['entry_company'],
                            'street_address' => $shipping_address->fields['entry_street_address'],
                            'suburb' => $shipping_address->fields['entry_suburb'],
                            'city' => $shipping_address->fields['entry_city'],
                            'postcode' => $shipping_address->fields['entry_postcode'],
                            'state' => ((zen_not_null($shipping_address->fields['entry_state'])) ? $shipping_address->fields['entry_state'] : $shipping_address->fields['zone_name']),
                            'zone_id' => $shipping_address->fields['entry_zone_id'],
                            'country' => array('id' => $shipping_address->fields['countries_id'], 'title' => $shipping_address->fields['countries_name'], 'iso_code_2' => $shipping_address->fields['countries_iso_code_2'], 'iso_code_3' => $shipping_address->fields['countries_iso_code_3']),
                            'country_id' => $shipping_address->fields['entry_country_id'],
     'is_banned' => $shipping_address->fields['is_banned'],
                            'format_id' => (int)$shipping_address->fields['address_format_id']);

    $this->billing = array('firstname' => $billing_address->fields['entry_firstname'],
                           'lastname' => $billing_address->fields['entry_lastname'],
                           'company' => $billing_address->fields['entry_company'],
                           'street_address' => $billing_address->fields['entry_street_address'],
                           'suburb' => $billing_address->fields['entry_suburb'],
                           'city' => $billing_address->fields['entry_city'],
                           'postcode' => $billing_address->fields['entry_postcode'],
                           'state' => ((zen_not_null($billing_address->fields['entry_state'])) ? $billing_address->fields['entry_state'] : $billing_address->fields['zone_name']),
                           'zone_id' => $billing_address->fields['entry_zone_id'],
                           'country' => array('id' => $billing_address->fields['countries_id'], 'title' => $billing_address->fields['countries_name'], 'iso_code_2' => $billing_address->fields['countries_iso_code_2'], 'iso_code_3' => $billing_address->fields['countries_iso_code_3']),
                           'country_id' => $billing_address->fields['entry_country_id'],
                           'format_id' => (int)$billing_address->fields['address_format_id']);

    $index = 0;$showMsg = 'N';
    $products = $_SESSION['cart']->get_products();
    for ($i=0, $n=sizeof($products); $i<$n; $i++) {
      if (($i/2) == floor($i/2)) {
        $rowClass="rowEven";
      } else {
        $rowClass="rowOdd";
      }
      
          /*
      * @ Check Is Nicotine on Product then add the new Value in order Array For show Msg
      * @ Added By: PardeepSingal
      * @ Saturday 16 April 2011
      */ 
          

      	if($shipping_address->fields['is_banned'] == '1'){
      	      global $db;      	
      		foreach($products[$i]['attributes']  as  $key => $value){
      		  $has_nicotine = $db->Execute("select `has_nicotine` from ".TABLE_PRODUCTS_ATTRIBUTES."  where  `options_id` = '".$key."' and  `options_values_id` = '".$value."'   and  `products_id` = '".$products[$i]['id']."'");
      		  if($has_nicotine->fields['has_nicotine']){
      		   	 $showMsg = 'Y';
      		  }      			
      		}
      	}	
      		
     
      	/*
      	 * End of integration
      	 * 
      	 */
      	
      $taxRates = zen_get_multiple_tax_rates($products[$i]['tax_class_id'], $tax_address->fields['entry_country_id'], $tax_address->fields['entry_zone_id']);
      $this->products[$index] = array('qty' => $products[$i]['quantity'],
                                      'name' => $products[$i]['name'],
                                      'model' => $products[$i]['model'],
                                      'tax' => zen_get_tax_rate($products[$i]['tax_class_id'], $tax_address->fields['entry_country_id'], $tax_address->fields['entry_zone_id']),
                                      'tax_groups'=>$taxRates,
                                      'tax_description' => zen_get_tax_description($products[$i]['tax_class_id'], $tax_address->fields['entry_country_id'], $tax_address->fields['entry_zone_id']),
                                      'price' => $products[$i]['price'],
                                      'final_price' => $products[$i]['price'] + $_SESSION['cart']->attributes_price($products[$i]['id']),
                                      'onetime_charges' => $_SESSION['cart']->attributes_price_onetime_charges($products[$i]['id'], $products[$i]['quantity']),
                                      'weight' => $products[$i]['weight'],
                                      'products_priced_by_attribute' => $products[$i]['products_priced_by_attribute'],
                                      'product_is_free' => $products[$i]['product_is_free'],
                                      'products_discount_type' => $products[$i]['products_discount_type'],
                                      'products_discount_type_from' => $products[$i]['products_discount_type_from'],
                                      'id' => $products[$i]['id'],
                                      'rowClass' => $rowClass);
      $this->notify('NOTIFY_ORDER_CART_ADD_PRODUCT_LIST', array('index'=>$index, 'products'=>$products[$i]));
      if ($products[$i]['attributes']) {
        $subindex = 0;
        reset($products[$i]['attributes']);
        while (list($option, $value) = each($products[$i]['attributes'])) {
          /*
          //clr 030714 Determine if attribute is a text attribute and change products array if it is.
          if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID){
          $attr_value = $products[$i]['attributes_values'][$option];
          } else {
          $attr_value = $attributes->fields['products_options_values_name'];
          }
          */

          $attributes_query = "select popt.products_options_name, poval.products_options_values_name,poval.products_options_values_quantity,
                                          pa.options_values_price, pa.price_prefix
                                   from " . TABLE_PRODUCTS_OPTIONS . " popt,
                                        " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval,
                                        " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                   where pa.products_id = '" . (int)$products[$i]['id'] . "'
                                   and pa.options_id = '" . (int)$option . "'
                                   and pa.options_id = popt.products_options_id
                                   and pa.options_values_id = '" . (int)$value . "'
                                   and pa.options_values_id = poval.products_options_values_id
                                   and popt.language_id = '" . (int)$_SESSION['languages_id'] . "'
                                   and poval.language_id = '" . (int)$_SESSION['languages_id'] . "'";

          $attributes = $db->Execute($attributes_query);

          //clr 030714 Determine if attribute is a text attribute and change products array if it is.
          if ($value == PRODUCTS_OPTIONS_VALUES_TEXT_ID){
            $attr_value = $products[$i]['attributes_values'][$option];
          } else {
            $attr_value = $attributes->fields['products_options_values_name'];
          }

          $this->products[$index]['attributes'][$subindex] = array('option' => $attributes->fields['products_options_name'],
                                                                   'value' => $attr_value,
                                                                   'option_id' => $option,
                                                                   'value_id' => $value,
								   'quantity' => $attributes->fields['products_options_values_quantity'],
                                                                   'prefix' => $attributes->fields['price_prefix'],
                                                                   'price' => $attributes->fields['options_values_price']);

          $this->notify('NOTIFY_ORDER_CART_ADD_ATTRIBUTE_LIST', array('index'=>$index, 'subindex'=>$subindex, 'products'=>$products[$i], 'attributes'=>$attributes));
          $subindex++;
        }
      }


$shippingAddress = $this->delivery;  
				
	if ( !isset($shippingAddress['street_address']) )  {
		$shippingAddress = $this->billing;
	}

	$TAXCLOUD_ENABLE = func_taxcloud_is_enabled($shippingAddress['country_id']);	

      // add onetime charges here
      //$_SESSION['cart']->attributes_price_onetime_charges($products[$i]['id'], $products[$i]['quantity'])

      /*********************************************
       * Calculate taxes for this product
       *********************************************/
	   if ($TAXCLOUD_ENABLE!='true') { 
      $shown_price = (zen_add_tax($this->products[$index]['final_price'] * $this->products[$index]['qty'], $this->products[$index]['tax']))
      + zen_add_tax($this->products[$index]['onetime_charges'], $this->products[$index]['tax']);
      $this->info['subtotal'] += $shown_price;
      $this->notify('NOTIFIY_ORDER_CART_SUBTOTAL_CALCULATE', array('shown_price'=>$shown_price));
      // find product's tax rate and description
      $products_tax = $this->products[$index]['tax'];
      $products_tax_description = $this->products[$index]['tax_description'];

      if (DISPLAY_PRICE_WITH_TAX == 'true') {
        // calculate the amount of tax "inc"luded in price (used if tax-in pricing is enabled)
        $tax_add = $shown_price - ($shown_price / (($products_tax < 10) ? "1.0" . str_replace('.', '', $products_tax) : "1." . str_replace('.', '', $products_tax)));
      } else {
        // calculate the amount of tax for this product (assuming tax is NOT included in the price)
//        $tax_add = zen_round(($products_tax / 100) * $shown_price, $currencies->currencies[$this->info['currency']]['decimal_places']);
        $tax_add = ($products_tax/100) * $shown_price;
      }
      $this->info['tax'] += $tax_add;
      foreach ($taxRates as $taxDescription=>$taxRate)
      {
        $taxAdd = zen_calculate_tax($this->products[$index]['final_price']*$this->products[$index]['qty'], $taxRate)
                +  zen_calculate_tax($this->products[$index]['onetime_charges'], $taxRate);
        if (isset($this->info['tax_groups'][$taxDescription]))
        {
          $this->info['tax_groups'][$taxDescription] += $taxAdd;
        } else
        {
          $this->info['tax_groups'][$taxDescription] = $taxAdd;
        }
      }
	  } //END if TAXCLOUD_ENABLE
      /*********************************************
       * END: Calculate taxes for this product
       *********************************************/
      $index++;
    }
    
    /*
	  * @ Added By Pardeep Singal
	  * @ Set The message flag in Order Array
	  */	
    $this->info['showMsg'] = $showMsg;
    
    /*
     *  @ End Of Integration
     */
    
    

    // Update the final total to include tax if not already tax-inc
    if (DISPLAY_PRICE_WITH_TAX == 'true') {
      $this->info['total'] = $this->info['subtotal'] + $this->info['shipping_cost'];
    } else {
      $this->info['total'] = $this->info['subtotal'] + $this->info['tax'] + $this->info['shipping_cost'];
    }
	
	
	
	   /*******************************
     * START TaxCloud
     *******************************/
      if ($TAXCLOUD_ENABLE=='true') {	
	  		
    	//calculate the sub_total the same way as Zen Cart does above (line 511) so that the subtotal is displayed
    	//on checkout_payment.php
   	foreach($this->products as $k=>$v){
		$this->info['subtotal'] += ($v['final_price'] * $v['qty']) + $v['onetime_charges']; 
	}
    
   	 // only call TaxCloud at checkout_confirmation.php page to take coupon into account as well. There's no harm in 
    	// calling in at checkout_payment.php page as in Zen Cart original design, but all the tax calculation will be 
    	// void anyway if a coupon is specified at checkout_confirmation.php    
    	if($_GET['main_page'] == FILENAME_CHECKOUT_PAYMENT || $_GET['main_page'] == FILENAME_CHECKOUT_CONFIRMATION) {      
	    // ************ TaxCloud Begin ************	        	
	    
    		$err = Array ();	  			        	
		
		$selectedCert = $_SESSION['singlePurchase'];   //one-time certificate
		$selectedCertID = $_SESSION['selectedCertID'];  //saved certificate
	
		if (!isset($selectedCert)) {
			if (isset($selectedCertID)) {
				$selectedCert = new ExemptionCertificate();
				$selectedCert ->setCertificateID($selectedCertID);
			} else {
				$selectedCert = null;
			}
		} 
		
		$shippingAddress = $this->delivery;  
				
		if ( !isset($shippingAddress['street_address']) )  {
			$shippingAddress = $this->billing;
		}
    		
	  	$tax = func_taxcloud_lookup_tax( $this->products, $shippingAddress, $err, $this->info['shipping_cost'], $selectedCert );	

	  	if (sizeof ( $err ) == 0 && is_array ( $tax )) {  	  		
  	  		$this->info['tax'] = $tax['tax'];
			$taxname = $tax['name'];
			$this->info['tax_groups'] = array ($taxname => $tax['tax']);
	  	} else {
			$err_msg = '';
			foreach ( $err as $msg ) {
	  	  		$err_msg .= $msg;
			}		   	
			$this->info['tax'] = 0;
			$this->info['tax_groups'] = array("Tax lookup ".$err_msg => 0);
			$this->info['err_msg'] = $err_msg;			
			global $messageStack;
			$messageStack->add_session('checkout', $err_msg, 'error');
	  	}
	   
    	}           
	  
	  }
	  
	   // Update the final total to include tax if not already tax-inc
    if (DISPLAY_PRICE_WITH_TAX == 'true') {
      $this->info['total'] = $this->info['subtotal'] + $this->info['shipping_cost'];
    } else {
      $this->info['total'] = $this->info['subtotal'] + $this->info['tax'] + $this->info['shipping_cost'];
    }

/*
// moved to function create
    if ($this->info['total'] == 0) {
      if (DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID == 0) {
        $this->info['order_status'] = DEFAULT_ORDERS_STATUS_ID;
      } else {
        $this->info['order_status'] = DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID;
      }
    }
*/
    if (isset($GLOBALS[$class]) && is_object($GLOBALS[$class])) {
      if ( isset($GLOBALS[$class]->order_status) && is_numeric($GLOBALS[$class]->order_status) && ($GLOBALS[$class]->order_status > 0) ) {
        $this->info['order_status'] = $GLOBALS[$class]->order_status;
      }
    }
    $this->notify('NOTIFY_ORDER_CART_FINISHED');
  }

  function create($zf_ot_modules, $zf_mode = 2) {
    global $db;
    
    // If we are using TaxCloud we need to update the totals again since they get recalculated by the cart
	$shippingAddress = $this->delivery;  
				
	if ( !isset($shippingAddress['street_address']) )  {
		$shippingAddress = $this->billing;
	}

	$TAXCLOUD_ENABLE = func_taxcloud_is_enabled($shippingAddress['country_id']);	
	
    if ($TAXCLOUD_ENABLE =='true') {      
    	$taxcloudTaxTotal = $_SESSION['taxcloudTaxTotal'];
    	if ( isset($taxcloudTaxTotal) ) {
    		$this->info['tax'] = $taxcloudTaxTotal;
    		$origTotal = $this->info['total'];
    		$this->info['total'] = $origTotal + $taxcloudTaxTotal;
    		
    		$moduleIndex = 0;
    		//update order total modules
    		foreach ($zf_ot_modules as $module) {
    			if ( $module['code'] == 'ot_total' ) { 
    				$originalValue = $module['value']; 
    				$module['text'] = '$'.($originalValue + $taxcloudTaxTotal);
    				$module['value'] = $originalValue + $taxcloudTaxTotal; 
    			}
    			$zf_ot_modules[$moduleIndex] = $module;
    			$moduleIndex++;
    		}
    		// add tax module
    		$taxModule = Array();
    		$taxModule['code'] = 'ot_tax';
    		$taxModule['title'] = 'Sales tax:';
    		$taxModule['text'] = '$'.$taxcloudTaxTotal;
    		$taxModule['value'] = $taxcloudTaxTotal;
    		$taxModule['sort_order'] = 990;
    		$zf_ot_modules[$moduleIndex] = $taxModule;
    	}
    } // end if TAXCLOUD_ENABLE
	
    if ($this->info['total'] == 0) {
      if (DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID == 0) {
        $this->info['order_status'] = DEFAULT_ORDERS_STATUS_ID;
      } else {
        if ($_SESSION['payment'] != 'freecharger') {
          $this->info['order_status'] = DEFAULT_ZERO_BALANCE_ORDERS_STATUS_ID;
        }
      }
    }

    if ($_SESSION['shipping'] == 'free_free') {
      $this->info['shipping_module_code'] = $_SESSION['shipping'];
    }

    $sql_data_array = array('customers_id' => $_SESSION['customer_id'],
                            'customers_name' => $this->customer['firstname'] . ' ' . $this->customer['lastname'],
                            'customers_company' => $this->customer['company'],
                            'customers_street_address' => $this->customer['street_address'],
                            'customers_suburb' => $this->customer['suburb'],
                            'customers_city' => $this->customer['city'],
                            'customers_postcode' => $this->customer['postcode'],
                            'customers_state' => $this->customer['state'],
                            'customers_country' => $this->customer['country']['title'],
                            'customers_telephone' => $this->customer['telephone'],
                            'customers_email_address' => $this->customer['email_address'],
                            'customers_address_format_id' => $this->customer['format_id'],
                            'delivery_name' => $this->delivery['firstname'] . ' ' . $this->delivery['lastname'],
                            'delivery_company' => $this->delivery['company'],
                            'delivery_street_address' => $this->delivery['street_address'],
                            'delivery_suburb' => $this->delivery['suburb'],
                            'delivery_city' => $this->delivery['city'],
                            'delivery_postcode' => $this->delivery['postcode'],
                            'delivery_state' => $this->delivery['state'],
                            'delivery_country' => $this->delivery['country']['title'],
                            'delivery_address_format_id' => $this->delivery['format_id'],
                            'billing_name' => $this->billing['firstname'] . ' ' . $this->billing['lastname'],
                            'billing_company' => $this->billing['company'],
                            'billing_street_address' => $this->billing['street_address'],
                            'billing_suburb' => $this->billing['suburb'],
                            'billing_city' => $this->billing['city'],
                            'billing_postcode' => $this->billing['postcode'],
                            'billing_state' => $this->billing['state'],
                            'billing_country' => $this->billing['country']['title'],
                            'billing_address_format_id' => $this->billing['format_id'],
                            'payment_method' => (($this->info['payment_module_code'] == '' and $this->info['payment_method'] == '') ? PAYMENT_METHOD_GV : $this->info['payment_method']),
                            'payment_module_code' => (($this->info['payment_module_code'] == '' and $this->info['payment_method'] == '') ? PAYMENT_MODULE_GV : $this->info['payment_module_code']),
                            'shipping_method' => $this->info['shipping_method'],
                            'shipping_module_code' => (strpos($this->info['shipping_module_code'], '_') > 0 ? substr($this->info['shipping_module_code'], 0, strpos($this->info['shipping_module_code'], '_')) : $this->info['shipping_module_code']),
                            'coupon_code' => $this->info['coupon_code'],
                            'cc_type' => $this->info['cc_type'],
                            'cc_owner' => $this->info['cc_owner'],
                            'cc_number' => $this->info['cc_number'],
                            'cc_expires' => $this->info['cc_expires'],
                            'date_purchased' => 'now()',
                            'orders_status' => $this->info['order_status'],
                            'order_total' => $this->info['total'],
                            'order_tax' => $this->info['tax'],
                            'currency' => $this->info['currency'],
                            'currency_value' => $this->info['currency_value'],
                            'ip_address' => $_SESSION['customers_ip_address'] . ' - ' . $_SERVER['REMOTE_ADDR']
                            );


    zen_db_perform(TABLE_ORDERS, $sql_data_array);

    $insert_id = $db->Insert_ID();

    $this->notify('NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_HEADER', array_merge(array('orders_id' => $insert_id, 'shipping_weight' => $_SESSION['cart']->weight), $sql_data_array));

    for ($i=0, $n=sizeof($zf_ot_modules); $i<$n; $i++) {
      $sql_data_array = array('orders_id' => $insert_id,
                              'title' => $zf_ot_modules[$i]['title'],
                              'text' => $zf_ot_modules[$i]['text'],
                              'value' => (is_numeric($zf_ot_modules[$i]['value'])) ? $zf_ot_modules[$i]['value'] : '0',
                              'class' => $zf_ot_modules[$i]['code'],
                              'sort_order' => $zf_ot_modules[$i]['sort_order']);

      zen_db_perform(TABLE_ORDERS_TOTAL, $sql_data_array);

      $this->notify('NOTIFY_ORDER_DURING_CREATE_ADDED_ORDERTOTAL_LINE_ITEM', $sql_data_array);
    }

    $customer_notification = (SEND_EMAILS == 'true') ? '1' : '0';
    $sql_data_array = array('orders_id' => $insert_id,
                            'orders_status_id' => $this->info['order_status'],
                            'date_added' => 'now()',
                            'customer_notified' => $customer_notification,
                            'comments' => $this->info['comments']);

    zen_db_perform(TABLE_ORDERS_STATUS_HISTORY, $sql_data_array);

    $this->notify('NOTIFY_ORDER_DURING_CREATE_ADDED_ORDER_COMMENT', $sql_data_array);

    return($insert_id);

  }


  function  create_add_products($zf_insert_id, $zf_mode = false) {
    global $db, $currencies, $order_total_modules, $order_totals;

    // initialized for the email confirmation
    $this->products_ordered = '';
    $this->products_ordered_html = '';
    $this->subtotal = 0;
    $this->total_tax = 0;

    // lowstock email report
    $this->email_low_stock='';

    for ($i=0, $n=sizeof($this->products); $i<$n; $i++) {
      $custom_insertable_text = '';
      // Stock Update - Joao Correia
      if (STOCK_LIMITED == 'true') {
        if (DOWNLOAD_ENABLED == 'true') {
          $stock_query_raw = "select p.products_quantity, pad.products_attributes_filename, p.product_is_always_free_shipping
                              from " . TABLE_PRODUCTS . " p
                              left join " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                               on p.products_id=pa.products_id
                              left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                               on pa.products_attributes_id=pad.products_attributes_id
                              WHERE p.products_id = '" . zen_get_prid($this->products[$i]['id']) . "'";

          // Will work with only one option for downloadable products
          // otherwise, we have to build the query dynamically with a loop
          $products_attributes = $this->products[$i]['attributes'];
          if (is_array($products_attributes)) {
            $stock_query_raw .= " AND pa.options_id = '" . $products_attributes[0]['option_id'] . "' AND pa.options_values_id = '" . $products_attributes[0]['value_id'] . "'";
          }
          $stock_values = $db->Execute($stock_query_raw);
        } else {
          $stock_values = $db->Execute("select * from " . TABLE_PRODUCTS . " where products_id = '" . zen_get_prid($this->products[$i]['id']) . "'");
        }

        $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_BEGIN');

        if ($stock_values->RecordCount() > 0) {
          // do not decrement quantities if products_attributes_filename exists
          if ((DOWNLOAD_ENABLED != 'true') || $stock_values->fields['product_is_always_free_shipping'] == 2 || (!$stock_values->fields['products_attributes_filename']) ) {
            $stock_left = $stock_values->fields['products_quantity'] - $this->products[$i]['qty'];
            $this->products[$i]['stock_reduce'] = $this->products[$i]['qty'];
          } else {
            $stock_left = $stock_values->fields['products_quantity'];
          }

          //            $this->products[$i]['stock_value'] = $stock_values->fields['products_quantity'];

          $db->Execute("update " . TABLE_PRODUCTS . " set products_quantity = '" . $stock_left . "' where products_id = '" . zen_get_prid($this->products[$i]['id']) . "'");
          //        if ( ($stock_left < 1) && (STOCK_ALLOW_CHECKOUT == 'false') ) {
          if ($stock_left <= 0) {
            // only set status to off when not displaying sold out
            if (SHOW_PRODUCTS_SOLD_OUT == '0') {
              $db->Execute("update " . TABLE_PRODUCTS . " set products_status = 0 where products_id = '" . zen_get_prid($this->products[$i]['id']) . "'");
            }
          }

          // for low stock email
          if ( $stock_left <= STOCK_REORDER_LEVEL ) {
            // WebMakers.com Added: add to low stock email
            $this->email_low_stock .=  'ID# ' . zen_get_prid($this->products[$i]['id']) . "\t\t" . $this->products[$i]['model'] . "\t\t" . $this->products[$i]['name'] . "\t\t" . ' Qty Left: ' . $stock_left . "\n";
          }

 	  // ZMB -- Decrement stock for all attributes ordered
		//@Eric: for Inventory Tool
		$insert_query = "insert into products_options_values_sold (website, product_id, from_products_options_values_id, products_options_values_id, sold_qty, stock_left) values";
		//@Eric: for Inventory Tool
		$process_sold_query = false;
	  foreach ( $this->products[$i]['attributes'] as $attribute ) {
	    $products_options_values_id = $attribute['value_id'];
	    $stock_left = $attribute['quantity'] - $this->products[$i]['qty'];
			
			//@Eric: for Inventory Tool
			$insert_query .= "('".HTTP_SERVER."', '" . $this->products[$i]['id'] . "', '" . $products_options_values_id . "', '" . $products_options_values_id . "', '" . $this->products[$i]['qty'] . "', '" . $stock_left . "'),";
			$process_sold_query = true;
			//@Eric: for Inventory Tool
			
	    $db->Execute( "update " . TABLE_PRODUCTS_OPTIONS_VALUES . " set products_options_values_quantity = '" . $stock_left . "' WHERE products_options_values_id = '" . $products_options_values_id . "'" );
	  }
		
		//@Eric: for Inventory Tool
		if($process_sold_query) {
			$insert_query = rtrim($insert_query, ",") . ";";
			$db->Execute($insert_query);
		}
		//@Eric: for Inventory Tool

        }
      }

      // Update products_ordered (for bestsellers list)
      //    $db->Execute("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%d', $order->products[$i]['qty']) . " where products_id = '" . zen_get_prid($order->products[$i]['id']) . "'");
      $db->Execute("update " . TABLE_PRODUCTS . " set products_ordered = products_ordered + " . sprintf('%f', $this->products[$i]['qty']) . " where products_id = '" . zen_get_prid($this->products[$i]['id']) . "'");

      $this->notify('NOTIFY_ORDER_PROCESSING_STOCK_DECREMENT_END');

      $sql_data_array = array('orders_id' => $zf_insert_id,
                              'products_id' => zen_get_prid($this->products[$i]['id']),
                              'products_model' => $this->products[$i]['model'],
                              'products_name' => $this->products[$i]['name'],
                              'products_price' => $this->products[$i]['price'],
                              'final_price' => $this->products[$i]['final_price'],
                              'onetime_charges' => $this->products[$i]['onetime_charges'],
                              'products_tax' => $this->products[$i]['tax'],
                              'products_quantity' => $this->products[$i]['qty'],
                              'products_priced_by_attribute' => $this->products[$i]['products_priced_by_attribute'],
                              'product_is_free' => $this->products[$i]['product_is_free'],
                              'products_discount_type' => $this->products[$i]['products_discount_type'],
                              'products_discount_type_from' => $this->products[$i]['products_discount_type_from'],
                              'products_prid' => $this->products[$i]['id']);
      zen_db_perform(TABLE_ORDERS_PRODUCTS, $sql_data_array);

      $order_products_id = $db->Insert_ID();

      $this->notify('NOTIFY_ORDER_DURING_CREATE_ADDED_PRODUCT_LINE_ITEM', array_merge(array('orders_products_id' => $order_products_id), $sql_data_array));

      $this->notify('NOTIFY_ORDER_PROCESSING_CREDIT_ACCOUNT_UPDATE_BEGIN');
      $order_total_modules->update_credit_account($i);//ICW ADDED FOR CREDIT CLASS SYSTEM

      $this->notify('NOTIFY_ORDER_PROCESSING_ATTRIBUTES_BEGIN');

      //------ bof: insert customer-chosen options to order--------
      $attributes_exist = '0';
      $this->products_ordered_attributes = '';
      if (isset($this->products[$i]['attributes'])) {
        $attributes_exist = '1';
        for ($j=0, $n2=sizeof($this->products[$i]['attributes']); $j<$n2; $j++) {
          if (DOWNLOAD_ENABLED == 'true') {
            $attributes_query = "select popt.products_options_name, poval.products_options_values_name,
                                 pa.options_values_price, pa.price_prefix,
                                 pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
                                 pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
                                 pa.attributes_price_factor, pa.attributes_price_factor_offset,
                                 pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
                                 pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
                                 pa.attributes_price_words, pa.attributes_price_words_free,
                                 pa.attributes_price_letters, pa.attributes_price_letters_free,
                                 pad.products_attributes_maxdays, pad.products_attributes_maxcount, pad.products_attributes_filename,
                                 pa.product_attribute_is_free, pa.attributes_discounted
                                 from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " .
            TABLE_PRODUCTS_ATTRIBUTES . " pa
                                  left join " . TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD . " pad
                                  on pa.products_attributes_id=pad.products_attributes_id
                                 where pa.products_id = '" . zen_db_input($this->products[$i]['id']) . "'
                                  and pa.options_id = '" . $this->products[$i]['attributes'][$j]['option_id'] . "'
                                  and pa.options_id = popt.products_options_id
                                  and pa.options_values_id = '" . $this->products[$i]['attributes'][$j]['value_id'] . "'
                                  and pa.options_values_id = poval.products_options_values_id
                                  and popt.language_id = '" . $_SESSION['languages_id'] . "'
                                  and poval.language_id = '" . $_SESSION['languages_id'] . "'";

            $attributes_values = $db->Execute($attributes_query);
          } else {
            $attributes_values = $db->Execute("select popt.products_options_name, poval.products_options_values_name,
                                 pa.options_values_price, pa.price_prefix,
                                 pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
                                 pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
                                 pa.attributes_price_factor, pa.attributes_price_factor_offset,
                                 pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
                                 pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
                                 pa.attributes_price_words, pa.attributes_price_words_free,
                                 pa.attributes_price_letters, pa.attributes_price_letters_free
                                 from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
                                 where pa.products_id = '" . $this->products[$i]['id'] . "' and pa.options_id = '" . (int)$this->products[$i]['attributes'][$j]['option_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . (int)$this->products[$i]['attributes'][$j]['value_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $_SESSION['languages_id'] . "' and poval.language_id = '" . $_SESSION['languages_id'] . "'");
          }

          //clr 030714 update insert query.  changing to use values form $order->products for products_options_values.
          $sql_data_array = array('orders_id' => $zf_insert_id,
                                  'orders_products_id' => $order_products_id,
                                  'products_options' => $attributes_values->fields['products_options_name'],

          //                                 'products_options_values' => $attributes_values->fields['products_options_values_name'],
                                  'products_options_values' => $this->products[$i]['attributes'][$j]['value'],
                                  'options_values_price' => $attributes_values->fields['options_values_price'],
                                  'price_prefix' => $attributes_values->fields['price_prefix'],
                                  'product_attribute_is_free' => $attributes_values->fields['product_attribute_is_free'],
                                  'products_attributes_weight' => $attributes_values->fields['products_attributes_weight'],
                                  'products_attributes_weight_prefix' => $attributes_values->fields['products_attributes_weight_prefix'],
                                  'attributes_discounted' => $attributes_values->fields['attributes_discounted'],
                                  'attributes_price_base_included' => $attributes_values->fields['attributes_price_base_included'],
                                  'attributes_price_onetime' => $attributes_values->fields['attributes_price_onetime'],
                                  'attributes_price_factor' => $attributes_values->fields['attributes_price_factor'],
                                  'attributes_price_factor_offset' => $attributes_values->fields['attributes_price_factor_offset'],
                                  'attributes_price_factor_onetime' => $attributes_values->fields['attributes_price_factor_onetime'],
                                  'attributes_price_factor_onetime_offset' => $attributes_values->fields['attributes_price_factor_onetime_offset'],
                                  'attributes_qty_prices' => $attributes_values->fields['attributes_qty_prices'],
                                  'attributes_qty_prices_onetime' => $attributes_values->fields['attributes_qty_prices_onetime'],
                                  'attributes_price_words' => $attributes_values->fields['attributes_price_words'],
                                  'attributes_price_words_free' => $attributes_values->fields['attributes_price_words_free'],
                                  'attributes_price_letters' => $attributes_values->fields['attributes_price_letters'],
                                  'attributes_price_letters_free' => $attributes_values->fields['attributes_price_letters_free'],
                                  'products_options_id' => (int)$this->products[$i]['attributes'][$j]['option_id'],
                                  'products_options_values_id' => (int)$this->products[$i]['attributes'][$j]['value_id'],
                                  'products_prid' => $this->products[$i]['id']
                                  );


          zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_data_array);

          $this->notify('NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_LINE_ITEM', $sql_data_array);

          if ((DOWNLOAD_ENABLED == 'true') && isset($attributes_values->fields['products_attributes_filename']) && zen_not_null($attributes_values->fields['products_attributes_filename'])) {
            $sql_data_array = array('orders_id' => $zf_insert_id,
                                    'orders_products_id' => $order_products_id,
                                    'orders_products_filename' => $attributes_values->fields['products_attributes_filename'],
                                    'download_maxdays' => $attributes_values->fields['products_attributes_maxdays'],
                                    'download_count' => $attributes_values->fields['products_attributes_maxcount'],
                                    'products_prid' => $this->products[$i]['id']
                                    );

            zen_db_perform(TABLE_ORDERS_PRODUCTS_DOWNLOAD, $sql_data_array);

            $this->notify('NOTIFY_ORDER_DURING_CREATE_ADDED_ATTRIBUTE_DOWNLOAD_LINE_ITEM', $sql_data_array);
          }
          $this->products_ordered_attributes .= "\n\t" . $attributes_values->fields['products_options_name'] . ' ' . zen_decode_specialchars($this->products[$i]['attributes'][$j]['value']);
        }
      }
      //------eof: insert customer-chosen options ----
    $this->notify('NOTIFY_ORDER_PROCESSING_ATTRIBUTES_EXIST', $attributes_exist);

    $this->notify('NOTIFY_ORDER_DURING_CREATE_ADD_PRODUCTS', $custom_insertable_text);

/* START: ADD MY CUSTOM DETAILS
 * 1. calculate/prepare custom information to be added to this product entry in order-confirmation, perhaps as a function call to custom code to build a serial number etc:
 *   Possible parameters to pass to custom functions at this point:
 *     Product ID ordered (for this line item): $this->products[$i]['id']
 *     Quantity ordered (of this line-item): $this->products[$i]['qty']
 *     Order number: $zf_insert_id
 *     Attribute Option Name ID: (int)$this->products[$i]['attributes'][$j]['option_id']
 *     Attribute Option Value ID: (int)$this->products[$i]['attributes'][$j]['value_id']
 *     Attribute Filename: $attributes_values->fields['products_attributes_filename']
 *
 * 2. Add that data to the $this->products_ordered_attributes variable, using this sort of format:
 *      $this->products_ordered_attributes .=  {INSERT CUSTOM INFORMATION HERE};
 */

    $this->products_ordered_attributes .= $custom_insertable_text;

/* END: ADD MY CUSTOM DETAILS */

      // update totals counters
      $this->total_weight += ($this->products[$i]['qty'] * $this->products[$i]['weight']);
      $this->total_tax += zen_calculate_tax($total_products_price, $products_tax) * $this->products[$i]['qty'];
      $this->total_cost += $total_products_price;

      $this->notify('NOTIFY_ORDER_PROCESSING_ONE_TIME_CHARGES_BEGIN');

      // build output for email notification
      $this->products_ordered .=  $this->products[$i]['qty'] . ' x ' . $this->products[$i]['name'] . ($this->products[$i]['model'] != '' ? ' (' . $this->products[$i]['model'] . ') ' : '') . ' = ' .
      $currencies->display_price($this->products[$i]['final_price'], $this->products[$i]['tax'], $this->products[$i]['qty']) .
      ($this->products[$i]['onetime_charges'] !=0 ? "\n" . TEXT_ONETIME_CHARGES_EMAIL . $currencies->display_price($this->products[$i]['onetime_charges'], $this->products[$i]['tax'], 1) : '') .
      $this->products_ordered_attributes . "\n";
      $this->products_ordered_html .=
      '<tr>' . "\n" .
      '<td class="product-details" align="right" valign="top" width="30">' . $this->products[$i]['qty'] . '&nbsp;x</td>' . "\n" .
      '<td class="product-details" valign="top">' . nl2br($this->products[$i]['name']) . ($this->products[$i]['model'] != '' ? ' (' . nl2br($this->products[$i]['model']) . ') ' : '') . "\n" .
      '<nobr>' .
      '<small><em> '. nl2br($this->products_ordered_attributes) .'</em></small>' .
      '</nobr>' .
      '</td>' . "\n" .
      '<td class="product-details-num" valign="top" align="right">' .
      $currencies->display_price($this->products[$i]['final_price'], $this->products[$i]['tax'], $this->products[$i]['qty']) .
      ($this->products[$i]['onetime_charges'] !=0 ?
      '</td></tr>' . "\n" . '<tr><td class="product-details">' . nl2br(TEXT_ONETIME_CHARGES_EMAIL) . '</td>' . "\n" .
      '<td>' . $currencies->display_price($this->products[$i]['onetime_charges'], $this->products[$i]['tax'], 1) : '') .
      '</td></tr>' . "\n";
			
			//@Eric: hide feature - add attribute with only one option to invoice automatically			
			$attributes_one_added = $db->Execute("SELECT products_id, options_id
				FROM products_attributes 
				WHERE products_id = ". zen_get_prid($this->products[$i]['id']) ." and attributes_status = 1
				GROUP BY products_id, options_id
				HAVING COUNT(*) = 1");
			if ($attributes_one_added->RecordCount() > 0) {
				//get option value id
				$aoa_product_attributes1 = $db->Execute("SELECT pa.options_values_id, pov.products_options_values_quantity 
					FROM products_attributes pa, products_options_values pov 
					WHERE pa.products_id = ". zen_get_prid($this->products[$i]['id']) ." 
						and pa.options_id = ". $attributes_one_added->fields['options_id'] ." and pa.attributes_status = 1 
						and pa.options_values_id = pov.products_options_values_id and pov.products_options_values_quantity > 0");
				if ($aoa_product_attributes1->RecordCount() > 0) {
					//get full option values
					$aoa_product_attributes2 = $db->Execute("select popt.products_options_name, poval.products_options_values_name,
						 pa.options_values_price, pa.price_prefix,
						 pa.product_attribute_is_free, pa.products_attributes_weight, pa.products_attributes_weight_prefix,
						 pa.attributes_discounted, pa.attributes_price_base_included, pa.attributes_price_onetime,
						 pa.attributes_price_factor, pa.attributes_price_factor_offset,
						 pa.attributes_price_factor_onetime, pa.attributes_price_factor_onetime_offset,
						 pa.attributes_qty_prices, pa.attributes_qty_prices_onetime,
						 pa.attributes_price_words, pa.attributes_price_words_free,
						 pa.attributes_price_letters, pa.attributes_price_letters_free
						 from " . TABLE_PRODUCTS_OPTIONS . " popt, " . TABLE_PRODUCTS_OPTIONS_VALUES . " poval, " . TABLE_PRODUCTS_ATTRIBUTES . " pa
						 where pa.products_id = '" . $this->products[$i]['id'] . "' and pa.options_id = '" . (int)$attributes_one_added->fields['options_id'] . "' and pa.options_id = popt.products_options_id and pa.options_values_id = '" . (int)$aoa_product_attributes1->fields['options_values_id'] . "' and pa.options_values_id = poval.products_options_values_id and popt.language_id = '" . $_SESSION['languages_id'] . "' and poval.language_id = '" . $_SESSION['languages_id'] . "'");
					
					//add attribute option to order product attribute table
					$sql_aoa_data_array = array('orders_id' => $zf_insert_id,
							'orders_products_id' => $order_products_id,
							'products_options' => $aoa_product_attributes2->fields['products_options_name'],
							'products_options_values' => $aoa_product_attributes2->fields['products_options_values_name'],
							'options_values_price' => $aoa_product_attributes2->fields['options_values_price'],
							'price_prefix' => $aoa_product_attributes2->fields['price_prefix'],
							'product_attribute_is_free' => $aoa_product_attributes2->fields['product_attribute_is_free'],
							'products_attributes_weight' => $aoa_product_attributes2->fields['products_attributes_weight'],
							'products_attributes_weight_prefix' => $aoa_product_attributes2->fields['products_attributes_weight_prefix'],
							'attributes_discounted' => $aoa_product_attributes2->fields['attributes_discounted'],
							'attributes_price_base_included' => $aoa_product_attributes2->fields['attributes_price_base_included'],
							'attributes_price_onetime' => $aoa_product_attributes2->fields['attributes_price_onetime'],
							'attributes_price_factor' => $aoa_product_attributes2->fields['attributes_price_factor'],
							'attributes_price_factor_offset' => $aoa_product_attributes2->fields['attributes_price_factor_offset'],
							'attributes_price_factor_onetime' => $aoa_product_attributes2->fields['attributes_price_factor_onetime'],
							'attributes_price_factor_onetime_offset' => $aoa_product_attributes2->fields['attributes_price_factor_onetime_offset'],
							'attributes_qty_prices' => $aoa_product_attributes2->fields['attributes_qty_prices'],
							'attributes_qty_prices_onetime' => $aoa_product_attributes2->fields['attributes_qty_prices_onetime'],
							'attributes_price_words' => $aoa_product_attributes2->fields['attributes_price_words'],
							'attributes_price_words_free' => $aoa_product_attributes2->fields['attributes_price_words_free'],
							'attributes_price_letters' => $aoa_product_attributes2->fields['attributes_price_letters'],
							'attributes_price_letters_free' => $aoa_product_attributes2->fields['attributes_price_letters_free'],
							'products_options_id' => (int)$attributes_one_added->fields['options_id'],
							'products_options_values_id' => (int)$aoa_product_attributes1->fields['options_values_id'],
							'products_prid' => $this->products[$i]['id']);
						zen_db_perform(TABLE_ORDERS_PRODUCTS_ATTRIBUTES, $sql_aoa_data_array);
					
					//update inventory
					$aoa_stock_left = 0;
					if($aoa_product_attributes1->fields['products_options_values_quantity'] > $this->products[$i]['qty'])					
						$aoa_stock_left = $aoa_product_attributes1->fields['products_options_values_quantity'] - $this->products[$i]['qty'];
					else
						$aoa_stock_left = $this->products[$i]['qty'];
					$db->Execute( "update " . TABLE_PRODUCTS_OPTIONS_VALUES . " set products_options_values_quantity = '" . $aoa_stock_left . "' WHERE products_options_values_id = '" . $aoa_product_attributes1->fields['options_values_id'] . "'" );
				}
			}
			//@Eric: hide feature - add attribute with only one option to invoice automatically
    }

    $order_total_modules->apply_credit();//ICW ADDED FOR CREDIT CLASS SYSTEM
    $this->notify('NOTIFY_ORDER_AFTER_ORDER_CREATE_ADD_PRODUCTS');
  }


  function send_order_email($zf_insert_id, $zf_mode) {
    global $currencies, $order_totals;
    if ($this->email_low_stock != '' and SEND_LOWSTOCK_EMAIL=='1') {
      // send an email
      $email_low_stock = SEND_EXTRA_LOW_STOCK_EMAIL_TITLE . "\n\n" . $this->email_low_stock;
      zen_mail('', SEND_EXTRA_LOW_STOCK_EMAILS_TO, EMAIL_TEXT_SUBJECT_LOWSTOCK, $email_low_stock, STORE_OWNER, EMAIL_FROM, array('EMAIL_MESSAGE_HTML' => nl2br($email_low_stock)),'low_stock');
    }

    // lets start with the email confirmation
    // make an array to store the html version
    $html_msg=array();

    //intro area
    $email_order = EMAIL_TEXT_HEADER . EMAIL_TEXT_FROM . STORE_NAME . "\n\n" .
    $this->customer['firstname'] . ' ' . $this->customer['lastname'] . "\n\n" .
    EMAIL_THANKS_FOR_SHOPPING . "\n" . EMAIL_DETAILS_FOLLOW . "\n" .
    EMAIL_SEPARATOR . "\n" .
    EMAIL_TEXT_ORDER_NUMBER . ' ' .ORDER_PREFIX."-".$zf_insert_id . "\n" .
    EMAIL_TEXT_DATE_ORDERED . ' ' . strftime(DATE_FORMAT_LONG) . "\n" .
    EMAIL_TEXT_INVOICE_URL . ' ' . zen_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $zf_insert_id, 'SSL', false) . "\n\n";
    $html_msg['EMAIL_TEXT_HEADER']     = EMAIL_TEXT_HEADER;
    $html_msg['EMAIL_TEXT_FROM']       = EMAIL_TEXT_FROM;
    $html_msg['INTRO_STORE_NAME']      = STORE_NAME;
    $html_msg['EMAIL_THANKS_FOR_SHOPPING'] = EMAIL_THANKS_FOR_SHOPPING;
    $html_msg['EMAIL_DETAILS_FOLLOW']  = EMAIL_DETAILS_FOLLOW;
    $html_msg['INTRO_ORDER_NUM_TITLE'] = EMAIL_TEXT_ORDER_NUMBER;
    $html_msg['INTRO_ORDER_NUMBER']    = $zf_insert_id;
    $html_msg['INTRO_DATE_TITLE']      = EMAIL_TEXT_DATE_ORDERED;
    $html_msg['INTRO_DATE_ORDERED']    = strftime(DATE_FORMAT_LONG);
    $html_msg['INTRO_URL_TEXT']        = EMAIL_TEXT_INVOICE_URL_CLICK;
    $html_msg['INTRO_URL_VALUE']       = zen_href_link(FILENAME_ACCOUNT_HISTORY_INFO, 'order_id=' . $zf_insert_id, 'SSL', false);

    //comments area
    if ($this->info['comments']) {
      $email_order .= zen_db_output($this->info['comments']) . "\n\n";
      $html_msg['ORDER_COMMENTS'] = nl2br(zen_db_output($this->info['comments']));
    } else {
      $html_msg['ORDER_COMMENTS'] = '';
    }

    //products area
    $email_order .= EMAIL_TEXT_PRODUCTS . "\n" .
    EMAIL_SEPARATOR . "\n" .
    $this->products_ordered .
    EMAIL_SEPARATOR . "\n";
    $html_msg['PRODUCTS_TITLE'] = EMAIL_TEXT_PRODUCTS;
    $html_msg['PRODUCTS_DETAIL']='<table class="product-details" border="0" width="100%" cellspacing="0" cellpadding="2">' . $this->products_ordered_html . '</table>';


  // If we are using TaxCloud we need to add an order total item for tax
    $shippingAddress = $this->delivery;  
    if ( !isset($shippingAddress['street_address']) )  {
    	$shippingAddress = $this->billing;
    }
    
    $TAXCLOUD_ENABLE = func_taxcloud_is_enabled($shippingAddress['country_id']);	
	
	  if ($TAXCLOUD_ENABLE =='true') {   
	     
    	$taxcloudTaxTotal = $_SESSION['taxcloudTaxTotal'];  
    	if ( isset($taxcloudTaxTotal) ) {
    		$index = 0;
    		foreach ($order_totals as $ot) {
    			if ( $ot['code'] == 'ot_total' ) { 
    				$newValue = $ot['value'] + $taxcloudTaxTotal;
    				$ot['value'] = $newValue;
    				$ot['text'] = '$' . $newValue;
    				
    				$order_totals[$index + 1] = $ot;
    				    		
				$ot_tax = Array();
				$ot_tax['code'] = 'ot_tax';
				$ot_tax['title'] = 'Tax:';
				$ot_tax['text'] = '$' . $taxcloudTaxTotal;
				$ot_tax['value'] = $taxcloudTaxTotal;
				$ot_tax['sort_order'] = 500;
    				$order_totals[$index] = $ot_tax;
    			} 
    			$index++;
    		}
    	}
    }

    //order totals area
    $html_ot .= '<td class="order-totals-text" align="right" width="100%">' . '&nbsp;' . '</td> ' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">' . '---------' .'</td> </tr>' . "\n" . '<tr>';
    for ($i=0, $n=sizeof($order_totals); $i<$n; $i++) {
      $email_order .= strip_tags($order_totals[$i]['title']) . ' ' . strip_tags($order_totals[$i]['text']) . "\n";
      $html_ot .= '<td class="order-totals-text" align="right" width="100%">' . $order_totals[$i]['title'] . '</td> ' . "\n" . '<td class="order-totals-num" align="right" nowrap="nowrap">' .($order_totals[$i]['text']) .'</td> </tr>' . "\n" . '<tr>';
    }
    $html_msg['ORDER_TOTALS'] = '<table border="0" width="100%" cellspacing="0" cellpadding="2"> ' . $html_ot . ' </table>';

    //addresses area: Delivery
    $html_msg['HEADING_ADDRESS_INFORMATION']= HEADING_ADDRESS_INFORMATION;
    $html_msg['ADDRESS_DELIVERY_TITLE']     = EMAIL_TEXT_DELIVERY_ADDRESS;
    $html_msg['ADDRESS_DELIVERY_DETAIL']    = ($this->content_type != 'virtual') ? zen_address_label($_SESSION['customer_id'], $_SESSION['sendto'], true, '', "<br />") : 'n/a';
    $html_msg['SHIPPING_METHOD_TITLE']      = HEADING_SHIPPING_METHOD;
    $html_msg['SHIPPING_METHOD_DETAIL']     = (zen_not_null($this->info['shipping_method'])) ? $this->info['shipping_method'] : 'n/a';

    if ($this->content_type != 'virtual') {
      $email_order .= "\n" . EMAIL_TEXT_DELIVERY_ADDRESS . "\n" .
      EMAIL_SEPARATOR . "\n" .
      zen_address_label($_SESSION['customer_id'], $_SESSION['sendto'], 0, '', "\n") . "\n";
    }

    //addresses area: Billing
    $email_order .= "\n" . EMAIL_TEXT_BILLING_ADDRESS . "\n" .
    EMAIL_SEPARATOR . "\n" .
    zen_address_label($_SESSION['customer_id'], $_SESSION['billto'], 0, '', "\n") . "\n\n";
    $html_msg['ADDRESS_BILLING_TITLE']   = EMAIL_TEXT_BILLING_ADDRESS;
    $html_msg['ADDRESS_BILLING_DETAIL']  = zen_address_label($_SESSION['customer_id'], $_SESSION['billto'], true, '', "<br />");

    if (is_object($GLOBALS[$_SESSION['payment']])) {
      $cc_num_display = (isset($this->info['cc_number']) && $this->info['cc_number'] != '') ? /*substr($this->info['cc_number'], 0, 4) . */ str_repeat('X', (strlen($this->info['cc_number']) - 8)) . substr($this->info['cc_number'], -4) . "\n\n" : '';
      $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
      EMAIL_SEPARATOR . "\n";
      $payment_class = $_SESSION['payment'];
      $email_order .= $GLOBALS[$payment_class]->title . "\n\n";
      $email_order .= (isset($this->info['cc_type']) && $this->info['cc_type'] != '') ? $this->info['cc_type'] . ' ' . $cc_num_display . "\n\n" : '';
      $email_order .= ($GLOBALS[$payment_class]->email_footer) ? $GLOBALS[$payment_class]->email_footer . "\n\n" : '';
    } else {
      $email_order .= EMAIL_TEXT_PAYMENT_METHOD . "\n" .
      EMAIL_SEPARATOR . "\n";
      $email_order .= PAYMENT_METHOD_GV . "\n\n";
    }
    $html_msg['PAYMENT_METHOD_TITLE']  = EMAIL_TEXT_PAYMENT_METHOD;
    $html_msg['PAYMENT_METHOD_DETAIL'] = (is_object($GLOBALS[$_SESSION['payment']]) ? $GLOBALS[$payment_class]->title : PAYMENT_METHOD_GV );
    $html_msg['PAYMENT_METHOD_FOOTER'] = (is_object($GLOBALS[$_SESSION['payment']]) && $GLOBALS[$payment_class]->email_footer != '') ? nl2br($GLOBALS[$payment_class]->email_footer) : (isset($this->info['cc_type']) && $this->info['cc_type'] != '' ? $this->info['cc_type'] . ' ' . $cc_num_display . "\n\n" : '');

    // include disclaimer
    if (defined('EMAIL_DISCLAIMER') && EMAIL_DISCLAIMER != '') $email_order .= "\n-----\n" . sprintf(EMAIL_DISCLAIMER, STORE_OWNER_EMAIL_ADDRESS) . "\n\n";
    // include copyright
    if (defined('EMAIL_FOOTER_COPYRIGHT')) $email_order .= "\n-----\n" . EMAIL_FOOTER_COPYRIGHT . "\n\n";

    while (strstr($email_order, '&nbsp;')) $email_order = str_replace('&nbsp;', ' ', $email_order);

    $html_msg['EMAIL_FIRST_NAME'] = $this->customer['firstname'];
    $html_msg['EMAIL_LAST_NAME'] = $this->customer['lastname'];
    //  $html_msg['EMAIL_TEXT_HEADER'] = EMAIL_TEXT_HEADER;
    $html_msg['EXTRA_INFO'] = '';
    $this->notify('NOTIFY_ORDER_INVOICE_CONTENT_READY_TO_SEND', array('zf_insert_id' => $zf_insert_id, 'text_email' => $email_order, 'html_email' => $html_msg));
    zen_mail($this->customer['firstname'] . ' ' . $this->customer['lastname'], $this->customer['email_address'], EMAIL_TEXT_SUBJECT . EMAIL_ORDER_NUMBER_SUBJECT . $zf_insert_id, $email_order, STORE_NAME, EMAIL_FROM, $html_msg, 'checkout', $this->attachArray);

    // send additional emails
    if (SEND_EXTRA_ORDER_EMAILS_TO != '') {
      $extra_info=email_collect_extra_info('','', $this->customer['firstname'] . ' ' . $this->customer['lastname'], $this->customer['email_address'], $this->customer['telephone']);
      $html_msg['EXTRA_INFO'] = $extra_info['HTML'];

      // include authcode and transaction id in admin-copy of email
      if ($GLOBALS[$_SESSION['payment']]->auth_code || $GLOBALS[$_SESSION['payment']]->transaction_id) {
        $pmt_details = ($GLOBALS[$_SESSION['payment']]->auth_code != '' ? 'AuthCode: ' . $GLOBALS[$_SESSION['payment']]->auth_code . '  ' : '') . ($GLOBALS[$_SESSION['payment']]->transaction_id != '' ?  'TransID: ' . $GLOBALS[$_SESSION['payment']]->transaction_id : '') . "\n\n";
        $email_order = $pmt_details . $email_order;
        $html_msg['EMAIL_TEXT_HEADER'] = nl2br($pmt_details) . $html_msg['EMAIL_TEXT_HEADER'];
      }

      zen_mail('', SEND_EXTRA_ORDER_EMAILS_TO, SEND_EXTRA_NEW_ORDERS_EMAILS_TO_SUBJECT . ' ' . EMAIL_TEXT_SUBJECT . EMAIL_ORDER_NUMBER_SUBJECT . $zf_insert_id,
      $email_order . $extra_info['TEXT'], STORE_NAME, EMAIL_FROM, $html_msg, 'checkout_extra', $this->attachArray);
    }
    $this->notify('NOTIFY_ORDER_AFTER_SEND_ORDER_EMAIL', array($zf_insert_id, $email_order, $extra_info, $html_msg));
  }

}
