<?php
use SimpleInvoices\Deprecate\Inventory;
use SimpleInvoices\Deprecate\Encode;

ini_set('max_execution_time', 600); //600 seconds = 10 minutes

$inventory = new Inventory();
$inventory->domain_id = 1;
$message = $inventory->check_reorder_level();

try {
    //json
    //header('Content-type: application/json');
    //echo Encode::json( $message, 'pretty' );
    
    //xml
    ob_end_clean();
    header('Content-type: application/xml');
    echo Encode::xml( $message );
} catch (Exception $e) {
    echo $e->getMessage();
}