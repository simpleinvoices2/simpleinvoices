<?php
namespace SimpleInvoices\ModuleManager\Feature;

/**
 * Autoloader provider interface
 */
interface AutoloaderProviderInterface
{
    /**
     * Return an array for passing to \Zend\Loader\AutoloaderFactory.
     *
     * @return array
     */
    public function getAutoloaderConfig();
}