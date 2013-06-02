<?php 
	global $db,$order; 
	$id=$orders_id;
	//$id=1;
	$sql = "select customers_name, customers_email_address,customers_telephone,billing_street_address, billing_city, billing_state,billing_country,billing_postcode,delivery_street_address,delivery_city,delivery_country,delivery_state,delivery_country,delivery_postcode,order_total,orders_status from orders where orders.orders_id='".$id."' ";
	
	$cost=mysql_query("select title,text from orders_total where sort_order=200 and orders_id='".$id."' ");
	$ship_data = mysql_fetch_array($cost); 

	$cust_comments = mysql_query("select comments from orders_status_history where orders_status_history.orders_id='".$id."'") or die("unable to execute query.");
	$comments= mysql_fetch_array($cust_comments); 


	$result = mysql_query($sql) or die ("Couldn't execute query."); 
	if($row = mysql_fetch_array($result)){ 
		$xml=''; 
		$xml.='<?xml version="1.0" encoding="UTF-8"?>
			<Orders>
				<Order>
					<ReferenceNumber>'.$id.'</ReferenceNumber>
					<Name>'.$row['customers_name'].'</Name>
					<Email> '.$row['customers_email_address'].'</Email>
					<Phone> '.$row['customers_telephone'].'</Phone>
					<Billing_address1>'.$row['billing_street_address'] .'</Billing_address1>
					<Billing_address2> </Billing_address2>
					<Billing_city> ' .$row['billing_city'].' </Billing_city>
					<Billing_state> ' .$row['billing_state']. ' </Billing_state>
					<Billing_country> '.$row['billing_country'].'  </Billing_country>
					<Billing_zip> '.$row['billing_postcode']. '  </Billing_zip>
					<Shipping_address1> '.$row['delivery_street_address'].' </Shipping_address1>
					<Shipping_address2>  </Shipping_address2>
					<Shipping_city> '.$row['delivery_city'].'  </Shipping_city>
					<Shipping_state> '.$row['delivery_state'].' </Shipping_state>
					<Shipping_country> '.$row['delivery_country'].'  </Shipping_country>
					<Shipping_zip> '.$row['delivery_postcode'].'  </Shipping_zip>
					<Order_total> '.$row['order_total'].'  </Order_total>
					<Order_Status>'.$row['orders_status'].' </Order_Status>
					<Source> www.electroniccigarettesinc.com </Source>
					<Shipping_Method>'.$ship_data['title'].' </Shipping_Method>
					<Shipping_Cost>'.$ship_data['text'].'</Shipping_Cost>
					<Customer_Comments>'.$comments['comments'].'</Customer_Comments>
					';
					
					$orderproduct = mysql_query("select orders_products_id,products_id, products_quantity,products_price,final_price,products_tax from orders_products where orders_products.orders_id='".$id."'");

					while($order=mysql_fetch_array($orderproduct)){
						$sql = mysql_query("select products_options_values_id from orders_products_attributes where orders_products_id='".$order['orders_products_id']."' ");
						while($data=mysql_fetch_array($sql)){
							$xml.='<Item>';
							$xml.='<ItemID>'.$data['products_options_values_id'].'</ItemID>' ; 
							$xml.='<ItemQty>'.$order['products_quantity'].' </ItemQty>
								<Product_Price>'.$order['products_price'].' </Product_Price>
								<Product_Tax>'.$order['products_tax'].' </Product_Tax>
								<Product_Final_Price>'.$order['final_price'].' </Product_Final_Price>';
					
								$orderproductweight = mysql_query("select products_attributes_weight from orders_products_attributes where orders_products_id='".$order['products_id']."' and orders_id='".$id."' ");
								if($weight=mysql_fetch_array($orderproductweight)) 
								$xml.='<Product_weight> '.$weight['products_attributes_weight '].' </Product_weight>';
								$xml.='</Item>';
						
						}
					}
			$xml .='</Order>
			</Orders>';
		}  
		$URL = "http://www.electroniccigarettes.com/imp/manageorders/placeOrder"; 
		$ch  = curl_init();
		curl_setopt($ch, CURLOPT_URL,$URL);
		curl_setopt($ch, CURLOPT_TIMEOUT, 120);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST,'POST');
		$length = strlen($xml);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("content-type:text/xml;content-length:$length"));
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$source = curl_exec($ch); //print_R($source);exit;
		curl_close($ch); 
		$data = (explode('<Completed>',$source));	
			for($i=1;$i<=sizeof($data);$i++){
				preg_match('/<Success>.*<\/Success>/',$data[$i],$Success);
			preg_match('/<OrderID>.*<\/OrderID>/',$data[$i],$OrderID); 
			$success = strip_tags(trim($Success[0])); 
			$ims_order_id = strip_tags(trim($OrderID[0])); 
			if($success=='Dulicate' || $success=='error'){
  			    $ims_order_id='0|'.$ims_order_id;
				$data=mysql_query("Update orders set orders.ims_order_id= '".$ims_order_id."' Where orders.orders_id='".$id."'");	
			} else {
				$ims_order_id='1|'.$ims_order_id;
				$data=mysql_query("Update orders set orders.ims_order_id= '".$ims_order_id."' Where orders.orders_id='".$orders_id."'");	
			}

		}

?>