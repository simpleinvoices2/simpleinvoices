<?php
use SimpleInvoices\Deprecate\Invoice;
use SimpleInvoices\Deprecate\Product;
use SimpleInvoices\Deprecate\Inventory;

if ($_POST['op'] =='edit' AND !empty($_POST['product_id']))
{
    $saved = "false";
    
    $inventory = new Inventory();
    $inventory->id=$_GET['id'];
    $inventory->domain_id= $auth_session->domain_id;
    $inventory->product_id=$_POST['product_id'];
    $inventory->quantity=$_POST['quantity'];
    $inventory->cost=$_POST['cost'];
    $inventory->date=$_POST['date'];
    $inventory->note=$_POST['note'];
    $result = $inventory->update();
    
    $saved = !empty($result) ? "true" : "false";
}      

$invoices = new Invoice();
$invoices->sort = 'id';
$invoice_all = $invoices->select_all('count');

$get_inventory = new Inventory();
$get_inventory->id = $_GET['id'];
$inventory = $get_inventory->select();

$productobj  = new Product();
$product_all = $productobj->get_all();

$smarty->assign('product_all',$product_all);
$smarty->assign('saved',$saved);
$smarty->assign('inventory',$inventory);

$smarty->assign('pageActive', 'inventory');
$smarty->assign('subPageActive', 'inventory_edit');
$smarty->assign('active_tab', '#product');
