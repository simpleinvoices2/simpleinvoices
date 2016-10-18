<?php
namespace SimpleInvoices\ModuleManager\Feature;

use SimpleInvoices\ModuleManager\ModuleManagerInterface;

/**
 * Init provider interface
 */
interface AutoloaderProviderInterface
{
    /**
     * Initialize workflow
     *
     * @param  ModuleManagerInterface $manager
     * @return void
     */
    public function init(ModuleManagerInterface $manager);
}