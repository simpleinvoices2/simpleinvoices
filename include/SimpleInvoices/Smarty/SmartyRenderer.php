<?php
namespace SimpleInvoices\Smarty;

use Zend\View\Renderer\PhpRenderer;

class SmartyRenderer extends PhpRenderer
{
    protected $smarty;
    
    public function __construct(\Smarty $smarty, $config = [])
    {
        parent::__construct($config);
        
        $this->smarty = $smarty;
    }
}