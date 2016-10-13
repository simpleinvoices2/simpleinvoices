<?php
use SimpleInvoices\Deprecate\Import\Json;

$menu = false;

$samplejson = new Json();
$samplejson->file = "./databases/json/sample_data.json";
// $samplejson->debug = true;
$samplejson->pattern_find = array('si_','DOMAIN-ID','LOCALE','LANGUAGE');
$samplejson->pattern_replace = array(TB_PREFIX,'1','en_GB','en_GB');
if($db->query($samplejson->collate()) )
{
    $saved=true;
} else {
    $saved=false;
}

$smarty->assign("saved", $saved);
