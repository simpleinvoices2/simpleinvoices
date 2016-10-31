<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace SimpleInvoices\Mvc\Service;

use Interop\Container\ContainerInterface;
use SimpleInvoices\ModuleManager\Listener\DefaultListenerAggregate;
use SimpleInvoices\ModuleManager\Listener\ListenerOptions;
use SimpleInvoices\ModuleManager\ModuleEvent;
use SimpleInvoices\ModuleManager\ModuleManager;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class ModuleManagerFactory implements FactoryInterface
{
    /**
     * Creates and returns the module manager
     *
     * Instantiates the default module listeners, providing them configuration
     * from the "module_listener_options" key of the ApplicationConfig
     * service. Also sets the default config glob path.
     *
     * Module manager is instantiated and provided with an EventManager, to which
     * the default listener aggregate is attached. The ModuleEvent is also created
     * and attached to the module manager.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return ModuleManager
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        //$configuration    = $container->get('ApplicationConfig');
        $configuration    = [
            'module_listener_options' => [],
        ];
        $listenerOptions  = new ListenerOptions($configuration['module_listener_options']);
        $defaultListeners = new DefaultListenerAggregate($listenerOptions);
        $serviceListener  = $container->get('ServiceListener');

        $serviceListener->addServiceManager(
            $container,
            'service_manager',
            'Zend\ModuleManager\Feature\ServiceProviderInterface',
            'getServiceConfig'
        );

        $serviceListener->addServiceManager(
            'ControllerManager',
            'controllers',
            'Zend\ModuleManager\Feature\ControllerProviderInterface',
            'getControllerConfig'
        );
        $serviceListener->addServiceManager(
            'ControllerPluginManager',
            'controller_plugins',
            'Zend\ModuleManager\Feature\ControllerPluginProviderInterface',
            'getControllerPluginConfig'
        );
        $serviceListener->addServiceManager(
            'ViewHelperManager',
            'view_helpers',
            'Zend\ModuleManager\Feature\ViewHelperProviderInterface',
            'getViewHelperConfig'
        );
        //$serviceListener->addServiceManager(
        //    'RoutePluginManager',
        //    'route_manager',
        //    'Zend\ModuleManager\Feature\RouteProviderInterface',
        //    'getRouteConfig'
        //);

        $events = $container->get('EventManager');
        $defaultListeners->attach($events);
        $serviceListener->attach($events);

        $moduleEvent = new ModuleEvent;
        $moduleEvent->setParam('ServiceManager', $container);

        $adapter        = $container->get('SimpleInvoices\Database\Adapter');
        $patchManager   = $container->get('SimpleInvoices\PatchManager');
        $doneSQLPatches = $patchManager->getNumberOfDoneSQLPatches();
        
        //$moduleManager = new ModuleManager($configuration['modules'], $events);
        $moduleManager = new ModuleManager($adapter, TB_PREFIX . 'extensions', $doneSQLPatches, null, $events);
        $moduleManager->setEvent($moduleEvent);

        return $moduleManager;
    }

    /**
     * Create and return ModuleManager instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return ModuleManager
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, ModuleManager::class);
    }
}
