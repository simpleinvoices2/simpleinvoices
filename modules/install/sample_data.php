<?php
use SimpleInvoices\Deprecate\Import\Json;
use Zend\Db\ResultSet\ResultSet;
use Zend\Db\Adapter\Driver\ResultInterface;

$menu = false;

$samplejson = new Json();
$samplejson->file = "./databases/json/sample_data.json";
// $samplejson->debug = true;
$samplejson->pattern_find = array('si_','DOMAIN-ID','LOCALE','LANGUAGE');
$samplejson->pattern_replace = array(TB_PREFIX,'1','en_GB','en_GB');

$dbAdapter = $services->get('SimpleInvoices\Database\Adapter');
$result    = $dbAdapter->getDriver()->getConnection()->execute($samplejson->collate());

if (($result instanceof ResultInterface) && ($result->getAffectedRows())) {
    $saved = true;
} else {
    $saved = false;
}

$smarty->assign("saved", $saved);
