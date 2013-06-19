<?php
    require_once 'anet_php_sdk/AuthorizeNet.php'; // Make sure this path is correct.
    $transaction = new AuthorizeNetCP('0810106', 'Nsdkb3Xbg5hLKp8');// '5RYaWVg354DDt46u');
    $transaction->amount = '0.03';
    $transaction->setSandbox(false);
    
    
    $transaction->setCustomField('email', "nice.ray.jin@gmail.com");
    $transaction->setCustomField('device_type', "4");
//    $transaction->setCustomField('x_market_type', "2");
    $transaction->setCustomField('x_version', "3.1");
    $transaction->setCustomField('x_card_num', "5123440"); //card number  5155760000409640
    $transaction->setCustomField('x_exp_date', "0416");
    $transaction->setCustomField('x_first_name', "Ray");
    $transaction->setCustomField('x_last_name', "Jin");
    $transaction->setCustomField('x_delim_data', TRUE);
    
    //$transaction->setCustomField('posturl', "https://www.eProcessingNetwork.Com/cgi-bin/an/transact.pl");
    
    //$transaction->setCustomField('x_type', "AUTH_CAPTURE");
    
    $response = $transaction->authorizeAndCapture();
    var_dump($response);
    if ($response->approved) {
        echo "<h1>Success The test credit card has been charge</h1>";
        echo "Transaction ID: " . $response->transaction_id;
    } else {
        echo $response->error_message;
    }
    /*
"x_login=0810106
     * &x_tran_key=Nsdkb3Xbg5hLKp8&
     * x_relay_response=FALSE&
     * x_delim_data=TRUE&
     * x_delim_char=%7C&
     * x_encap_char=%2A&x_version=3.1&x_method=CC&x_amount=0.00&x_currency_code=USD&x_card_num=6011000990139424&x_exp_date=0613&x_card_code=345&x_email_customer=FALSE&x_email_merchant=TRUE&x_cust_id=38164&x_invoice_num=1002747-jgoSN1&x_first_name=Maddox&x_last_name=Pitt&x_company=&x_address=12312&x_city=sfsdf&x_state=Alaska&x_zip=2341&x_country=United+States&x_phone=234234&x_email=gold.brain.pitt%40gmail.com&x_ship_to_first_name=Maddox&x_ship_to_last_name=Pitt&x_ship_to_address=12312&x_ship_to_city=sfsdf&x_ship_to_state=Alaska&x_ship_to_zip=2341&x_ship_to_country=United+States&x_description=SmokePass+Electronic+Cigar+-+1800+Puff+%28qty%3A+1%29+&x_recurring_billing=NO&x_customer_ip=1&x_po_num=Jun-13-2013+07%3A23%3A27&x_freight=0.00&x_tax_exempt=FALSE&x_tax=0.00&x_duty=0&x_allow_partial_Auth=FALSE&Date=June+13%2C+2013%2C+7%3A23+pm&IP=1&Session=dut2ni717thfum4pi9hgkij7a6"
     
     * * url "https://www.eProcessingNetwork.Com/cgi-bin/an/order.pl"
     * 
     * return :
     * "*2*|*1*|*2*|*This transaction has been declined.*|* CARD NO. ERROR *|*Reenter - AVS System Unavailable (R)*|*20130613122709-0810106-92203-0*|*1002747-rLLqOG*|*SmokePass Electronic Cigar - 1800 Puff (qty: 1) *|*0.00*|*CC*|**|*38164*|*Maddox*|*Pitt*|**|*12312*|*sfsdf*|*Alaska*|*2341*|*United States*|*234234*|**|*gold.brain.pitt@gmail.com*|*Maddox*|*Pitt*|**|*12312*|*sfsdf*|*Alaska*|*2341*|*United States*|*0.00*|*0*|*0.00*|*FALSE*|*Jun-13-2013 07:26:27*|*59E4366C17EA19F7C6C7325725F49F98*|*P*|"
*/
    
?>

