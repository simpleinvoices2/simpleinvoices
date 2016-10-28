<?php


//stop the direct browsing to this file - let index.php handle which files get displayed
checkLogin();

//if valid then do save
if ($_POST['p_description'] != "" ) {
	include("./modules/preferences/save.php");
}
$smarty -> assign('save',$save);

$defaults = getSystemDefaults();
$preferences = getActivePreferences();

// Locale list start
$localelist = [];

if ($handle = opendir(getcwd() . '/language')) {
    while (false !== ($entry = readdir($handle))) {
        // TODO: we will need to search for PO files
        if (preg_match('/^[a-z]{2}_[A-Z]{2}\.php$/', $entry)) {
            $locale = pathinfo(getcwd() . '/language/' . $entry, PATHINFO_FILENAME);
            $localelist[$locale] = \Locale::canonicalize($locale);
        }
    }

    closedir($handle);
    
    //ksort($languages);
    asort($localelist);
}
// locale list end

$smarty->assign('preferences',$preferences);
$smarty->assign('defaults',$defaults);
$smarty->assign('localelist',$localelist);

$smarty -> assign('pageActive', 'preference');
$smarty -> assign('subPageActive', 'preferences_add');
$smarty -> assign('active_tab', '#setting');
?>
