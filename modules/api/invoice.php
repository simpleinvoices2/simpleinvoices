<?php
use SimpleInvoices\Deprecate\Invoice;
use SimpleInvoices\Deprecate\Encode;

//get invoice details

$invoiceobj = new Invoice();
// why hardcode invoice number below?
$invoice = $invoiceobj->select('1');

header('Content-type: application/xml');
echo Encode::xml($invoice);
print_r($invoice);
