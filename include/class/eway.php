<?php

class eway 
{

    public $biller;
    public $invoice;
    public $customer;
    public $preference;
	public $domain_id;
    
	public function __construct()
	{
	    $auth_session    = new Zend_Session_Namespace('Zend_Auth');
		$this->domain_id = $auth_session->domain_id;
	}

    public function pre_check()
    {
        global $logger;

        $return = 'false';
        
        //set customer,biller and preference if not defined
        if(empty($this->customer))
        {
            $this->customer = getCustomer($this->invoice['customer_id'], $this->domain_id);
        }
        if(empty($this->biller))
        {
            $this->biller = getBiller($this->invoice['biller_id'], $this->domain_id);
        }
        if(empty($this->preference))
        {
            $this->preference = getPreference($this->invoice['preference_id'], $this->domain_id);
        }

        if (
                $this->invoice['owing'] > 0 
            AND $this->biller['eway_customer_id'] != ''
            AND $this->customer['credit_card_number'] != ''
            AND in_array("eway_merchant_xml",explode(",", $this->preference['include_online_payment']))
           )
        {
            $return = 'true';
        }

        $logger->info("eway pre check: " . $return);
        return $return;
    }

    public function payment()
    {
    
        global $config;
        global $logger;

        //set customer,biller and preference if not defined
        if(empty($this->customer))
        {
            $this->customer = getCustomer($this->invoice['customer_id'], $this->domain_id);
        }
        if(empty($this->biller))
        {
            $this->biller = getBiller($this->invoice['biller_id'], $this->domain_id);
        }
        if(empty($this->preference))
        {
            $this->preference = getPreference($this->invoice['preference_id'], $this->domain_id);
        }

        $eway = new ewaylib($this->biller['eway_customer_id'],'REAL_TIME', false);

        //Eway only accepts amount in cents - so times 100
		$value = $this->invoice['total']*100;
		$eway_invoice_total = htmlsafe(trim($value));
        $logger->info("eway total: " . $eway_invoice_total);

        $enc = new encryption();
        $key = $config->encryption->default->key;	
        $credit_card_number = $enc->decrypt($key, $this->customer['credit_card_number']);

        $eway->setTransactionData("TotalAmount", $eway_invoice_total); //mandatory field
        $eway->setTransactionData("CustomerFirstName", $this->customer['name']);
    	$eway->setTransactionData("CustomerLastName", "");
        $eway->setTransactionData("CustomerAddress", "");
        $eway->setTransactionData("CustomerPostcode", "");
        $eway->setTransactionData("CustomerInvoiceDescription", "");
        $eway->setTransactionData("CustomerEmail", $this->customer['email']);
        $eway->setTransactionData("CustomerInvoiceRef", $this->invoice['index_name']);
        $eway->setTransactionData("CardHoldersName", $this->customer['credit_card_holder_name']); //mandatory field
        $eway->setTransactionData("CardNumber", $credit_card_number); //mandatory field
        $eway->setTransactionData("CardExpiryMonth", $this->customer['credit_card_expiry_month']); //mandatory field
        $eway->setTransactionData("CardExpiryYear", $this->customer['credit_card_expiry_year']); //mandatory field
        $eway->setTransactionData("Option1", "");
        $eway->setTransactionData("Option2", "");
        $eway->setTransactionData("Option3", "");
        $eway->setTransactionData("TrxnNumber", $this->invoice['id']);
        
        //special preferences for php Curl
        //pass a long set to zero value stops curl from verifying peer's certificate 
        $eway->setCurlPreferences(CURLOPT_SSL_VERIFYPEER, 0);
        $ewayResponseFields = $eway->doPayment();
        $this->message = $ewayResponseFields;
        $message ="";
        if($ewayResponseFields["EWAYTRXNSTATUS"]=="False"){
			$logger->info("Transaction Error: " . $ewayResponseFields["EWAYTRXNERROR"] . "<br>\n");
            foreach($ewayResponseFields as $key => $value)
                $message .= "\n<br>\$ewayResponseFields[\"$key\"] = $value";
			$logger->info("Eway message: " . $message . "<br>\n");
            //header("Location: trasnactionerrorpage.php");
            //exit();
            $return = 'false';		
        }else if($ewayResponseFields["EWAYTRXNSTATUS"]=="True"){


			$logger->info("Transaction Success: " . $ewayResponseFields["EWAYTRXNERROR"] . "<br>\n");
            foreach($ewayResponseFields as $key => $value)
                $message .= "\n<br>\$ewayResponseFields[\"$key\"] = $value";
			$logger->info("Eway message: " . $message . "<br>\n");
            //header("Location: trasnactionsuccess.php");
            //exit();
            $payment = new payment();
            $payment->ac_inv_id = $this->invoice['id'];
            #$payment->ac_inv_id = $_POST['invoice'];
            $payment->ac_amount = $this->invoice['total'];
            #$payment->ac_amount = $ewayResponseFields['EWAYRETURNAMOUNT']/100;
            #$payment->ac_amount = $_POST['mc_gross'];
            $payment->ac_notes = $message;
            $payment->ac_date = date( 'Y-m-d' );
            $payment->online_payment_id = $ewayResponseFields['EWAYTRXNNUMBER'];
            $payment->domain_id = $this->domain_id;

            $payment_type = new payment_type();
            $payment_type->type = "Eway";
            $payment_type->domain_id = $this->domain_id;

            $payment->ac_payment_type = $payment_type->select_or_insert_where();
            $logger->info('Paypal - payment_type='.$payment->ac_payment_type);
            $payment->insert();
            #echo $db->lastInsertID();
            $return = 'true';		
        }

        return $return ;		
    }

    function get_message()
    {
    
        return $this->message;

    }

}
