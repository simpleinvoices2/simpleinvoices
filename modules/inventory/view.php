<?php
use SimpleInvoices\Deprecate\Inventory;

$get_inventory = new Inventory();
$get_inventory->id = $_GET['id'];
$inventory = $get_inventory->select();

$smarty->assign('inventory', $inventory);
$smarty->assign('pageActive', 'inventory');
$smarty->assign('subPageActive', 'inventory_view');
$smarty->assign('active_tab', '#product');
