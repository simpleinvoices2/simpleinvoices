<?php
use SimpleInvoices\Deprecate\Invoice;

$ni = new Invoice();
$ni->id = $_GET['id'];
$ni->recur();
