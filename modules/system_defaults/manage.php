<?php
use SimpleInvoices\SystemDefault\SystemDefaultManager;

global $serviceManager;

$systemDefaults = $serviceManager->get(SystemDefaultManager::class);

//stop the direct browsing to this file - let index.php handle which files get displayed
checkLogin();

//gets the long language name out of the short name
$lang = $systemDefaults->get('language', 'en_GB');
if (!empty($lang)) {
    $lang = \Locale::getDisplayName($lang, $lang);
}

$smarty->assign("defaults", getSystemDefaults());
$smarty->assign("defaultBiller", getDefaultBiller());
$smarty->assign("defaultCustomer", getDefaultCustomer());
$smarty->assign("defaultTax", getDefaultTax());
$smarty->assign("defaultPreference", getDefaultPreference());
$smarty->assign("defaultPaymentType", getDefaultPaymentType());
$smarty->assign("defaultDelete", $systemDefaults->get('delete', 0));
$smarty->assign("defaultLogging", $systemDefaults->get('logging', 0));
$smarty->assign("defaultInventory", $systemDefaults->get('inventory', 0));
$smarty->assign("defaultProductAttributes", $systemDefaults->get('product_attributes', 0));
$smarty->assign("defaultLargeDataset", $systemDefaults->get('large_dataset', 0));
$smarty->assign("defaultLanguage", $lang);
$smarty->assign('pageActive', 'system_default');
$smarty->assign('active_tab', '#setting');
