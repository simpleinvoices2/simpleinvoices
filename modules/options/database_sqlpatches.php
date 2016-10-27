<?php

//stop the direct browsing to this file - let index.php handle which files get displayed
//checkLogin();

$patchManager = $services->get('SimpleInvoices\PatchManager');
$patch        = $patchManager->getPatches();
$applied      = $patchManager->getAppliedSQLPatches()->toArray();

if ($routeMatch->getParam('action', null) === 'run') {
    if(count($applied) >= 1) {
        $result = $patchManager->applyPatches();
        
        if ($result) {
            $smarty_datas['message']= "The database patches have now been applied. You can now start working with Simple Invoices";
            $smarty_datas['html']	= "<div class='si_toolbar si_toolbar_form'><a href='index.php'>HOME</a></div>";
            $smarty_datas['refresh']=5;
        } else {
            $smarty_datas['message']= "The database patches have failed.";
        }
    } else {
        $smarty_datas['html']= "Step 1 - This is the first time Database Updates has been run";
        // TODO: what is this????
        //$smarty_datas['html']  = initialise_sql_patch();
        $smarty_datas['html'] .= "<br />
		Now that the Database upgrade table has been initialised, please go back to the Database Upgrade Manger page by clicking
		the following button to run the remaining patches.
		<div class='si_toolbar si_toolbar_form'><a href='index.php?module=options&amp;view=database_sqlpatches'>Continue</a></div>
		.";
    }
} else {
    $smarty_datas=array();
    $smarty_datas['message']= "Your version of Simple Invoices can now be upgraded.	With this new release there are database patches that need to be applied";
    $smarty_datas['html']	= <<<EOD
    	<p>
    			The list below describes which patches have and have not been applied to the database, the aim is to have them all applied.<br />
    			If there are patches that have not been applied to the Simple Invoices database, please run the Update database by clicking update
    	</p>
    	<div class="si_message_warning">Warning: Please backup your database before upgrading!</div>
    	<div class="si_toolbar si_toolbar_form"><a href="./index.php?module=options&view=database_sqlpatches&action=run" class=""><img src="images/common/tick.png" alt="" />Update</a></div>
EOD;
    foreach($patch as $p) {
        $patch_name = htmlsafe($p['name']);
        $patch_date = htmlsafe($p['date']);
        $patch_ref  = htmlsafe($p['ref']);
        
        $isApplied = false;
        if ($p['ref'] !== '0') {
            foreach($applied as $appliedPatch) {
                if ((int) $appliedPatch['sql_patch_ref'] === (int) $p['ref']) {
                    $isApplied = true;
                    break;
                } 
            }
        } else {
            $isApplied = true;
        }
        
        if ($isApplied) {
            $smarty_datas['rows'][$p['ref']]['text']	= "SQL patch $patch_ref, $patch_name <i>has</i> already been applied in release $patch_date";
            $smarty_datas['rows'][$p['ref']]['result']	='skip';
        } else {
            $smarty_datas['rows'][$p['ref']]['text']	= "SQL patch $patch_ref, $patch_name <b>has not</b> been applied to the database";
            $smarty_datas['rows'][$p['ref']]['result']	='todo';
        }
    }
}

$smarty->assign("page", $smarty_datas);
