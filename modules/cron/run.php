<?php
use SimpleInvoices\Deprecate\Cron;

$cron = new Cron();
$cron->domain_id = 1;
$message = $cron->run();

$smarty->assign('message', $message);
