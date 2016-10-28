<?php
/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.enabled_text.php
 * Type:     function
 * Name:     enabled_text
 * Purpose:  outputs 'enabled' or 'disabled'
 * -------------------------------------------------------------
 */
function smarty_modifier_enabled_text($string)
{
    global $LANG;
    
    if ($string == '1') {
        return htmlsafe($LANG['enabled']);
    }
    
    return htmlsafe($LANG['disabled']);
}