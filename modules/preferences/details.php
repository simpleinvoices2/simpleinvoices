<?php
//stop the direct browsing to this file - let index.php handle which files get displayed
checkLogin();

//if valid then do save
if ($_POST['p_description'] != "" ) {
	include("./modules/preferences/save.php");
}

#get the invoice id
$preference_id = $_GET['id'];

$preference = getPreference($preference_id);
$index_group = getPreference($preference['index_group']);

$preferences = getActivePreferences();
$defaults = getSystemDefaults();
$status = array(array('id'=>'0','status'=>$LANG['draft']), array('id'=>'1','status'=>$LANG['real']));

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

$smarty->assign('preference',$preference);
$smarty->assign('defaults',$defaults);
$smarty->assign('index_group',$index_group);
$smarty->assign('preferences',$preferences);
$smarty->assign('status',$status);
$smarty->assign('localelist',$localelist);

$smarty -> assign('pageActive', 'preference');
$subPageActive = $_GET['action'] =="view"  ? "preferences_view" : "preferences_edit" ;
$smarty -> assign('subPageActive', $subPageActive);
$smarty -> assign('active_tab', '#setting');
