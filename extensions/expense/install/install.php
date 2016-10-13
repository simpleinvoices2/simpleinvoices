<?php
use SimpleInvoices\Deprecate\Import;

$menu = false;

$import = new Import();
$domain_id = $auth_session->domain_id;

if (checkTableExists('expense') == false)
{
    $import->file = "./extensions/expense/install/db.sql";
    $import->pattern_find = array('si_','DOMAIN-ID','LOCALE','LANGUAGE');
    $import->pattern_replace = array(TB_PREFIX, $domain_id, 'en_GB', 'en_GB');
    //  $db->query($import->collate());
    $import->execute();
    $import->file = "./extensions/expense/install/db2.sql";
    $import->execute();
    //  $import->file = "./extensions/expense/install/db3.sql";
    //  $import->execute();
}
