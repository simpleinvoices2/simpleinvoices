<?php
use SimpleInvoices\Deprecate\Invoice;

checkLogin();

$sql = "DELETE FROM ".TB_PREFIX."cron WHERE id = :id AND domain_id = :domain_id";
$sth = dbQuery($sql, ':id', $_GET['id'], ':domain_id', $auth_session->domain_id) or die(htmlsafe(end($dbh->errorInfo())));
$saved = !empty($sth) ? "true" : "false";

$invoices = new Invoice();
$invoices->sort = 'id';
$invoice_all = $invoices->select_all('count');

$smarty->assign('invoice_all',$invoice_all);
$smarty->assign('saved',$saved);

$smarty->assign('pageActive', 'cron');
$smarty->assign('subPageActive', 'cron_manage');
$smarty->assign('active_tab', '#money');
