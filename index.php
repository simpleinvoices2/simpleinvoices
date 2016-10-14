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

/**
 * Escapes a filename
 *
 * @param string $str The string to escape
 * @return string The escaped string
 */
function filenameEscape($str)
{
    // Returns an escaped value.
    $safe_str = preg_replace('/[^a-z0-9\-_\.]/i','_',$str);
    return $safe_str;
}

$module = isset($_GET['module']) ? filenameEscape($_GET['module'])  : null;
$view   = isset($_GET['view'])   ? filenameEscape($_GET['view'])    : null;
$action = isset($_GET['case'])   ? filenameEscape($_GET['case'])    : null;

require_once("./include/init.php");


/*
	GetCustomPath: override template or module with custom one if it exists, else return default path if it exists
	---------------------------------------------
	@name: name or dir/name of the module or template (without extension)
	@mode: template or module
*/

function GetCustomPath($name,$mode='template'){
	$my_custom_path="./custom/";
	$use_custom=1;
	if($mode=='template'){
		if($use_custom and file_exists("{$my_custom_path}default_template/{$name}.tpl")){
			$out=".{$my_custom_path}default_template/{$name}.tpl";
		}
		elseif(file_exists("./templates/default/{$name}.tpl")){
			$out="../templates/default/{$name}.tpl";
		}
	}
	if($mode=='module'){
		if($use_custom and file_exists("{$my_custom_path}modules/{$name}.php")){
			$out="{$my_custom_path}modules/{$name}.php";
		}
		elseif(file_exists("./modules/{$name}.php")){
			$out="./modules/{$name}.php";
		}
	}
	return $out;
}
	

foreach($config->extension as $extension)
{
	/*
	* If extension is enabled then continue and include the requested file for that extension if it exists
	*/	
	if($extension->enabled == "1")
	{
		//echo "Enabled:".$value['name']."<br><br>";
		if(file_exists("./extensions/$extension->name/include/init.php"))
		{
			require_once("./extensions/$extension->name/include/init.php");
		}
	}
}
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


/*
 * dont include the header if requested file is an invoice template - for print preview etc.. header is not needed 
 */

if (($module == "invoices" ) && (strstr($view,"template"))) {
    /*
     * If extension is enabled load the extension php file for the module	
     * Note: this system is probably slow - if you got a better method for handling extensions let me know
     */
    $extensionInvoiceTemplateFile = 0;
    foreach($config->extension as $extension)
    {
        /*
         * If extension is enabled then continue and include the requested file for that extension if it exists
         */	
        if($extension->enabled == "1") {
            //echo "Enabled:".$value['name']."<br><br>";
            if(file_exists("./extensions/$extension->name/modules/invoices/template.php")) {
                include_once("./extensions/$extension->name/modules/invoices/template.php");
                $extensionInvoiceTemplateFile++;
            }
        }
    }

    /*
     * If no extension php file for requested file load the normal php file if it exists
     */
    if( ($extensionInvoiceTemplateFile == 0) AND ($my_path=GetCustomPath("invoices/template",'module') ) )  {
        /* (soif) This /modules/invoices/template.php is empty : Should we really keep it? */
        include_once($my_path);
    }
    
    exit(0);
}

/*
 * xml or ajax page requeset - start
 */

	if( strstr($module,"api") OR (strstr($view,"xml") OR (strstr($view,"ajax")) ) )
	{	
		$extensionXml = 0;
		foreach($config->extension as $extension)
		{
            /*
             * If extension is enabled then continue and include the requested file for that extension if it exists
             */	
			if($extension->enabled == "1")
			{
				if(file_exists("./extensions/$extension->name/modules/$module/$view.php")) 
				{
					include("./extensions/$extension->name/modules/$module/$view.php");
					$extensionXml++;
				}
			}
		}

        /*
         * If no extension php file for requested file load the normal php file if it exists
         */
		if($extensionXml == 0 and $my_path=GetCustomPath("$module/$view",'module')){
			include($my_path);
		}

		exit(0);
	}
/*
 * xml or ajax page request - end
 */

$file= "$module/$view";

/*
 * Prep the page - load the header stuff - start
 */

	// To remove the js error due to multiple document.ready.function() 
	// 	in jquery.datePicker.js, jquery.autocomplete.conf.js and jquery.accordian.js 
	//	 without instances in manage pages - Ap.Muthu
	/*
     * TODO: fix the javascript or move datapicker to extjs to fix this hack - not nice
     */

/*
 * If extension is enabled load its javascript files	- start
 * Note: this system is probably slow - if you got a better method for handling extensions let me know
 */
	$extension_jquery_files = "";
	
	foreach($config->extension as $extension)
	{
		/*
         * If extension is enabled then continue and include the requested file for that extension if it exists
         */	
		if($extension->enabled == "1")
		{
			if(file_exists("./extensions/$extension->name/include/jquery/$extension->name.jquery.ext.js")) {
				$extension_jquery_files .= "<script type=\"text/javascript\" src=\"./extensions/$extension->name/include/jquery/$extension->name.jquery.ext.js\"></script>";
			}
		}
	}
	
	$smarty -> assign("extension_jquery_files",$extension_jquery_files);
/*
 * If extension is enabled load its javascript files	- end
 */


/**
 * This would be the dispatch!!
 * ==============================================================================================
 * * ---------------------------------------- START ---------------------------------------------
 * Include the php file for the requested page section - start
 */

/*
 * If extension is enabled load the extension php file for the module
 * Note: this system is probably slow - if you got a better method for handling extensions let me know
 */
$extensionPHPFile = 0;
foreach($config->extension as $extension)
{
    /*
     * If extension is enabled then continue and include the requested file for that extension if it exists
     */
    if($extension->enabled == "1")
    {
        if(file_exists("./extensions/$extension->name/modules/" . $module . '/' . $view . ".php")) {

            include_once("./extensions/$extension->name/modules/" . $module . '/' . $view . ".php");
            $extensionPHPFile++;
        }
    }
}

/*
 * If no extension php file for requested file load the normal php file if it exists
 */
if ( ($extensionPHPFile == 0) &&  $my_path = GetCustomPath($module . '/' . $view, 'module') ) {
    include($my_path);
}

/**
 * Include the php file for the requested page section - end
 * ----------------------------------------- END ------------------------------------------------
 * ==============================================================================================
 */

/**
 * Run the application
 */
$application->run();

