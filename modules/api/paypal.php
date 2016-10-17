<?php
use SimpleInvoices\Deprecate\Invoice;
use SimpleInvoices\Deprecate\Payment;
use SimpleInvoices\Deprecate\Email;
use SimpleInvoices\Deprecate\Payment\Type as PaymentType;
use Zend\Mail\Message;

$p = new paypal_class;             // initiate an instance of the class
#$p->paypal_url = 'https://www.sandbox.paypal.com/cgi-bin/webscr';   // testing paypal url
$p->paypal_url = 'https://www.paypal.com/cgi-bin/webscr';     // paypal url

$xml_message="";

$logger->info('Paypal API page called');
if ($p->validate_ipn()) {
#if (!empty($_POST)) {

	$logger->info('Paypal validate success');

	//insert into payments
	$paypal_data ="";
	foreach ($p->ipn_data as $key => $value) { $paypal_data .= "\n$key: $value"; }
	$logger->info('Paypal Data:');
	$logger->info($paypal_data);
	//get the domain_id from the paypal invoice
	$custom_array = explode(";", $p->ipn_data['custom']);
	#$custom_array = explode(";", $_POST['custom']);

	$logger->info('Paypal - custom='.$_POST['custom']);
	foreach ($custom_array as $key => $value)
	{
		if( strstr($value,"domain_id:"))
		{
			$logger->info('Paypal - value='.$value);
			$domain_id = substr($value, 10);
			#$domain_id = substr($domain_id, 0, -1);
		}
	}

	$logger->info('Paypal - domain_id='.$domain_id.'EOM');
	
	//check if payment has already been entered

	$check_payment = new Payment();
	$check_payment->filter='online_payment_id';
	$check_payment->online_payment_id = $p->ipn_data['txn_id'];
	$check_payment->domain_id = $domain_id;
    $number_of_payments = $check_payment->count();
	$logger->info('Paypal - number of times this payment is in the db: '.$number_of_payments);
	
	if($number_of_payments > 0)
	{
		$xml_message .= 'Online payment '.$p->ipn_data['tnx_id'].' has already been entered into Simple Invoices - exiting for domain_id='.$domain_id;
		$logger->info($xml_message);
	}

	if($number_of_payments == '0')
	{

		$payment = new Payment();
		$payment->ac_inv_id = $p->ipn_data['invoice'];
		#$payment->ac_inv_id = $_POST['invoice'];
		$payment->ac_amount = $p->ipn_data['mc_gross'];
		#$payment->ac_amount = $_POST['mc_gross'];
		$payment->ac_notes = $paypal_data;
		#$payment->ac_notes = $paypal_data;
		$payment->ac_date = date( 'Y-m-d', strtotime($p->ipn_data['payment_date']));
		#$payment->ac_date = date( 'Y-m-d', strtotime($_POST['payment_date']));
		$payment->online_payment_id = $p->ipn_data['txn_id'];
		$payment->domain_id = $domain_id;

			$payment_type = new PaymentType();
			$payment_type->type = "Paypal";
			$payment_type->domain_id = $domain_id;

		$payment->ac_payment_type = $payment_type->select_or_insert_where();
		$logger->info('Paypal - payment_type='.$payment->ac_payment_type);
		$payment->insert();

		$invoiceobj = new Invoice();
		$invoice = $invoiceobj->select($p->ipn_data['invoice']);

		$biller = getBiller($invoice['biller_id']);

		//send email
		$body =  "A Paypal instant payment notification was successfully recieved into Simple Invoices\n";
		$body .= "from ".$p->ipn_data['payer_email']." on ".date('m/d/Y');
		$body .= " at ".date('g:i A')."\n\nDetails:\n";
		$body .= $paypal_data;

		$mailMessage = new Message();
		$mailMessage->setFrom('simpleinvoices@localhost.localdomain');
		$mailMessage->addTo($biller['email']);
		$mailMessage->setSubject('Instant Payment Notification - Recieved Payment');
		$mailMessage->setBody($body);
		$mailMessage->setEncoding('utf-8');
		
		$services->get('SimpleInvoices\Mail\TransportInterface')->send($mailMessage);

		$xml_message['data'] .= $body;
	}
} else {

	$xml_message .= "Paypal validate failed" ;
	$logger->info('Paypal validate failed');
}

header('Content-type: application/xml');
try 
{
    $xml = new encode();
    $xml->xml( $xml_message );
    echo $xml;
} 
catch (Exception $e) 
{
    echo $e->getMessage();
}
