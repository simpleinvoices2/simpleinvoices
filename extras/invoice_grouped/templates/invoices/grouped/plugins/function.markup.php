<?php
use SimpleInvoices\I18n\SiLocal;

function smarty_function_markup($params, &$smarty)
{	
    $subtotal = 0;
    foreach ($params['cost'] as $key=>$value) {
		if ($value['product']['custom_field1'] == $params['group']) {
            $subtotal = $value['tax_amount'] + $subtotal;
        }
    }
    $subtotal = SiLocal::number($subtotal);	
    return $subtotal;	
}
