<?php

//stop the direct browsing to this file - let index.php handle which files get displayed
checkLogin();

//gets the long language name out of the short name
$lang = getDefaultLanguage();
if (!empty($lang)) {
    $lang = \Locale::getDisplayName($lang, $lang);
}

$smarty->assign("defaults", getSystemDefaults());
$smarty->assign("defaultBiller", getDefaultBiller());
$smarty->assign("defaultCustomer", getDefaultCustomer());
$smarty->assign("defaultTax", getDefaultTax());
$smarty->assign("defaultPreference", getDefaultPreference());
$smarty->assign("defaultPaymentType", getDefaultPaymentType());
$smarty->assign("defaultDelete", getDefaultDelete());
$smarty->assign("defaultLogging", getDefaultLogging());
$smarty->assign("defaultInventory", getDefaultInventory());
$smarty->assign("defaultProductAttributes", getDefaultProductAttributes());
$smarty->assign("defaultLargeDataset", getDefaultLargeDataset());
$smarty->assign("defaultLanguage", $lang);
$smarty->assign('pageActive', 'system_default');
$smarty->assign('active_tab', '#setting');
