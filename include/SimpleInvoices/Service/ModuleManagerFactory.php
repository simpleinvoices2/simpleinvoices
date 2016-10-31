<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SimpleInvoices\ModuleManager\ModuleManagerInterface;
use SimpleInvoices\ModuleManager\ModuleManager;
use SimpleInvoices\ModuleManager\Listener\DefaultListenerAggregate;
use SimpleInvoices\ModuleManager\ModuleEvent;

class ModuleManagerFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return ModuleManagerInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $adapter        = $container->get('SimpleInvoices\Database\Adapter');
        $patchManager   = $container->get('SimpleInvoices\PatchManager');
        $doneSQLPatches = $patchManager->getNumberOfDoneSQLPatches();
        $events         = $container->get('EventManager');
        
        $defaultListeners = new DefaultListenerAggregate();
        $defaultListeners->attach($events);
        
        $moduleEvent = new ModuleEvent();
        $moduleEvent->setParam('ServiceManager', $container);
        
        return new ModuleManager($adapter, TB_PREFIX . 'extensions', $doneSQLPatches, null, $events);
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ModuleManagerInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, 'SimpleInvoices\ModuleManager');
    }
}