<?php
use SimpleInvoices\Deprecate\Invoice;
use SimpleInvoices\Deprecate\Payment;
use SimpleInvoices\Deprecate\Email;

$logger->info('ACH API page called');
if ($_POST['pg_response_code']=='A01') {

	$logger->info('ACH validate success');

	//insert into payments
	$paypal_data ="";
	foreach ($_POST as $key => $value) { $paypal_data .= "\n$key: $value"; }
	$logger->info('ACH Data:');
	$logger->info($paypal_data);

	$check_payment = new Payment();
	$check_payment->filter='online_payment_id';
	$check_payment->online_payment_id = $_POST['pg_consumerorderid'];
	$check_payment->domain_id = '1';
    $number_of_payments = $check_payment->count();
	$logger->info('ACH - number of times this payment is in the db: '.$number_of_payments);
	
	if($number_of_payments > 0)
	{
		$xml_message = 'Online payment for invoices: '.$_POST['pg_consumerorderid'].' has already been entered into Simple Invoices';
		$logger->info($xml_message);
	}

	if($number_of_payments == '0')
	{

		$payment = new Payment();
		$payment->ac_inv_id = $_POST['pg_consumerorderid'];
		$payment->ac_amount = $_POST['pg_total_amount'];
		$payment->ac_notes = $paypal_data;
		$payment->ac_date = date( 'Y-m-d');
		$payment->online_payment_id = $_POST['pg_consumerorderid'];
		$payment->domain_id = '1';

			$payment_type = new payment_type();
			$payment_type->type = "ACH";
			$payment_type->domain_id = '1';

		$payment->ac_payment_type = $payment_type->select_or_insert_where();
		$logger->info('ACH - payment_type='.$payment->ac_payment_type);
		$payment->insert();

		$invoiceobj = new Invoice();
		$invoice = $invoiceobj->select($_POST['pg_consumerorderid']);
		$biller = getBiller($invoice['biller_id']);

		//send email
		$body =  "A PaymentsGateway.com payment of ".$_POST['pg_total_amount']." was successfully received into Simple Invoices\n";
		$body .= "for invoice: ".$_POST['pg_consumerorderid'] ;
		$body .= " from ".$_POST['pg_billto_postal_name_company']." on ".date('m/d/Y');
		$body .= " at ".date('g:i A')."\n\nDetails:\n";
		$body .= $paypal_data;

		$email = new Email();
		$email->notes = $body;
		$email->to = $biller['email'];
		$email->from = "simpleinvoices@localhost.localdomain";
		$email->subject = 'PaymentsGateway.com -Instant Payment Notification - Recieved Payment';
		$email->send ();
        $xml_message = "+++++++++<br /><br />";
		$xml_message .= "Thank you for the payment, the details have been recorded and ". $biller['name'] ." has been notified via email.";
        $xml_message .= "<br /><br />+++++++++<br />";
	}
} else {
	$xml_message = "PaymentsGateway.com payment validate failed - please contact ". $biller['name'] ;
	$logger->info('ACH validate failed');
}

echo $xml_message;
