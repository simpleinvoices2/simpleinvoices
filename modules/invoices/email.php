<?php
/*
 * Script: email.php
 * 	Email invoice page
 *
 * License:
 *	 GPL v3 or above
 *
 * Website:
 * 	http://www.simpleinvoices.org
 */

use SimpleInvoices\Deprecate\Invoice;
use SimpleInvoices\Deprecate\Export;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Part as MimePart;
use Zend\Mime\Mime;

//stop the direct browsing to this file - let index.php handle which files get displayed
checkLogin();

#get the invoice id
$invoice_id = $_GET['id'];

$invoiceobj = new Invoice();
$invoice = $invoiceobj->select($invoice_id);

$preference = getPreference($invoice['preference_id']);
$biller = getBiller($invoice['biller_id']);
$customer = getCustomer($invoice['customer_id']);
$invoiceType = getInvoiceType($invoice['type_id']);

#create PDF name
$spc2us_pref = str_replace(" ", "_", $invoice['index_name']);
$pdf_file_name = $spc2us_pref  . '.pdf';
      
if ($_GET['stage'] == 2 ) {

	#echo $block_stage2;
	
	// Create invoice
	$export = new Export();
	$export->format = "pdf";
	$export->file_location = 'file';
	$export->module = 'invoice';
	$export->id = $invoice_id;
	$export->execute();

	#$attachment = file_get_contents('./tmp/cache/' . $pdf_file_name);

	$mimeMessage = new MimeMessage();
	
	$htmlPart       = new MimePart($_POST['email_notes']);
	$htmlPart->type = 'text/html';
	$textPart       = new MimePart($_POST['email_notes']);
	$textPart->type = 'text/plain';
	$mimeMessage->setParts([$textPart, $htmlPart]);
	
	$contentPart = new MimePart($mimeMessage->generateMessage());
	$contentPart->type = 'multipart/alternative;' . PHP_EOL . ' boundary="' . $mimeMessage->getMime()->boundary() . '"';
	
	$attachment = new MimePart(fopen('./tmp/cache/' . $pdf_file_name, 'r'));
	$attachment->setFileName($pdf_file_name);
	$attachment->type = 'application/pdf';
	$attachment->encoding    = Mime::ENCODING_BASE64;
	$attachment->disposition = Mime::DISPOSITION_ATTACHMENT;
	
	$body = new MimeMessage();
	$body->setParts(array($contentPart, $attachment));
	
	$mailMessage = new Message();
	$mailMessage->setFrom($_POST['email_from'], $biller['name']);
	$mailMessage->addTo($_POST['email_to']);
	if (!empty($_POST['email_bcc'])) {
	   $mailMessage->setBcc($_POST['email_bcc']);
	}
	$mailMessage->setSubject($_POST['email_subject']);
	$mailMessage->setBody($body);
	$mailMessage->setEncoding('utf-8');
	
	$services->get('SimpleInvoices\Mail\TransportInterface')->send($mailMessage);
	
	// TODO: Make it more elegant and use a template with translation
	//       Also make sure it has been sent, no check right now
	$message  = '<html>';
	$message .= '<meta http-equiv="refresh" content="2;URL=index.php?module=invoices&amp;view=manage">';
	$message .= '<body><p>' . $pdf_file_name . ' has been emailed.</p></body>';
	$message .= '</html>';
	die($message);
}

//stage 3 = assemble email and send
else if ($_GET['stage'] == 3 ) {
	$message = "How did you get here :)";
}

$smarty->assign('message', $message);
$smarty->assign('biller', $biller);
$smarty->assign('customer', $customer);
$smarty->assign('invoice', $invoice);
$smarty->assign('preferences', $preference);
$smarty->assign('pageActive', 'invoice');
$smarty->assign('active_tab', '#money');
