<?php
/*
 * Script: index.php
 * 	Main controller file for Simple Invoices
 *
 * License:
 *	 GPL v3 or above
 */

use SimpleInvoices\Deprecate\Invoice;
use SimpleInvoices\Mvc\MvcEvent;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;

//minor change to test github emails - test

//stop browsing to files directly - all viewing to be handled by index.php
//if browse not defined then the page will exit
define("BROWSE","browse");

/**
 * Composer autoloader
 */
require_once './vendor/autoload.php';


/*
 * The include configs and requirements stuff section - start
 */


require_once("./include/init.php");
	
/*
 * The include configs and requirements stuff section - end
 */

$smarty->assign("config",$config); // to toggle the login / logout button visibility in the menu
$smarty->assign("module",$module);
$smarty->assign("view",$view);
$smarty->assign("siUrl",$siUrl);//used for template css
$smarty->assign("LANG",$LANG);
//For Making easy enabled pop-menus (see biller)
$smarty->assign("enabled",array($LANG['disabled'],$LANG['enabled']));

/*
 * Menu - hide or show menu
 */
$menu = isset($menu)?$menu: true;

/*
 * File - set which page will be displayed as the start page
 */


//if auth - make sure is valid session else skip
// Check for any unapplied SQL patches when going home
//TODO - redo this code
if (($module == "options") && ($view == "database_sqlpatches")) {
	include_once('./include/sql_patches.php');
	donePatches();
} else {
	//check db structure - if only structure and no fields then prompt for imports
	// 1 import essential data
    $skip_db_patches = false;
	//$install_tables_exists = checkTableExists(TB_PREFIX."biller");
    if ( $install_tables_exists == false )
    { 
		$module="install";
		//$view="index";
		$view == "structure" ? $view ="structure" : $view="index";
        //do installer
        $skip_db_patches = true;
		
    }
	if ( ($install_tables_exists == true) AND ($install_data_exists == false) )
    { 
	    $module = "install";
		$view == "essential" ? $view ="essential" : $view="structure";
		//$view = "essential";
        //do installer
        $skip_db_patches = true;
    }
    //count sql_patches
    // if 0 run import essential data
	// 2 import sample data
	//echo $skip_db_patches; 
	//if auth on must login before upgrade
    if ($skip_db_patches == false)
    {
		if ( ($config->authentication->enabled == 1 AND isset($auth_session->id)) OR ($config->authentication->enabled == 0) )	
		{
			include_once('./include/sql_patches.php');
			if (getNumberOfPatches() > 0 ) {
				$view = "database_sqlpatches";
				$module = "options";
				
				if($action == "run") {
					runPatches();
				} else {
					listPatches();
				}
				$menu = false;
			} else {
				//If no invoices in db then show home page as default - else show Manage Invoices page
				if ($module==null)
				{
					$invoiceobj = new Invoice();
					if ( $invoiceobj->are_there_any() > "0" )  
					{
					    $module = "invoices" ;
						$view = "manage";
					
					} else { 
					    $module = "index" ;
						$view = "index";
					}
					unset($invoiceobj);
				}
			}
		}
    }
}

/**
 * Run the application
 */
$application->run();

