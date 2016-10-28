<?php
use SimpleInvoices\Deprecate\Import\Json;

$menu = false;

if ( (checkTableExists(TB_PREFIX."customers") == true) AND (checkDataExists() == false) )
{
    //JSON import
    $importjson = new Json();
    $importjson->file = "./databases/json/essential_data.json";
    //$importjson->debug = true;
    $importjson->pattern_find = array('si_','DOMAIN-ID','LOCALE','LANGUAGE');
    $importjson->pattern_replace = array(TB_PREFIX,'1','en_GB','en_GB');
    //dbQuery($importjson->collate());
    //$db->query($importjson->collate());
    
    $dbAdapter = $services->get('SimpleInvoices\Database\Adapter');
    $dbAdapter->query($importjson->collate(), \Zend\Db\Adapter\Adapter::QUERY_MODE_EXECUTE);
}
