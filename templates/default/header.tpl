<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html
		PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
		"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
{strip}
	{include file='../custom/hooks.tpl'}
	{assign var='tmp_lang_module' value="title_module_`$module`"}{assign var='tmp_lang_module' value=$LANG.$tmp_lang_module|default:$LANG.$module|default:$module}
	{assign var='tmp_lang_view' value="title_view_`$view`"}{assign var='tmp_lang_view' value=$LANG.$tmp_lang_view|default:$LANG.$view|default:$view}
	{$smarty.capture.hook_head_start}
{/strip}
	<title>{$tmp_lang_module} : {$tmp_lang_view} - {$LANG.simple_invoices} </title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta http-equiv="Content-Script-Type" content="text/javascript" />
	<meta http-equiv="Content-Style-Type" content="text/css" />
	<meta name="robots" content="noindex, nofollow" />
	<link rel="shortcut icon" href="{$basePath}/images/common/favicon.ico" />

{literal}
	<link rel="stylesheet" type="text/css" href="{/literal}{$basePath}{literal}/public/assets/jquery/wysiwyg/wysiwyg.css" />
	<link rel="stylesheet" type="text/css" href="{/literal}{$basePath}{literal}/public/assets/jquery/jquery.plugins.css" title="default" media="screen" />
	<link rel="stylesheet" type="text/css" href="{/literal}{$basePath}{literal}/public/assets/jquery/rte/rte.css" />	
	<link rel="stylesheet" type="text/css" href="{/literal}{$basePath}{literal}/public/assets/jquery/cluetip/jquery.cluetip.css" />

	<link rel="stylesheet" type="text/css" href="{/literal}{$basePath}{literal}/templates/default/css/main.css" media="all"/>
	<link rel="stylesheet" type="text/css" href="{/literal}{$basePath}{literal}/templates/default/css/print.css" media="print" />
<!--[if IE]>
	<link rel="stylesheet" type="text/css" href="{/literal}{$basePath}{literal}/templates/default/css/main_ie.css" media="all" />
<![endif]-->

	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery-1.2.6.min.js"></script>
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery.init.js"></script>
	<!-- jQuery Files -->
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery-ui-personalized-1.6rc2.packed.js"></script>	
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/cluetip/jquery.hoverIntent.minified.js"></script>
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/cluetip/jquery.cluetip.js"></script>
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery.flexigrid.1.0b3.pack.js"></script>
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery.plugins.js"></script>
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/wysiwyg/wysiwyg.modified.packed.js"></script>
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery.livequery.pack.js"></script>
{/literal}
    {$extension_jquery_files }
	{include file='./public/assets/jquery/jquery.functions.js.tpl'}
	{include file='./public/assets/jquery/jquery.conf.js.tpl'}
{literal}

	<!--<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery.conf.js.tpl"></script>-->

{/literal}
	{if $config->debug->level == "All"}
	<link rel="stylesheet" type="text/css" href="{$basePath}/public/assets/blackbirdjs/blackbird.css" />	
	<script type="text/javascript" src="{$basePath}/public/assets/blackbirdjs/blackbird.js"></script>
	{/if}
{literal}
	<script type="text/javascript" src="{/literal}{$basePath}{literal}/public/assets/jquery/jquery.validationEngine.js"></script>
{/literal}

{$smarty.capture.hook_head_end}
</head>
<body class="body_si body_module_{$module} body_view_{$view}">
{$smarty.capture.hook_body_start}
<div class="si_grey_background"></div>
