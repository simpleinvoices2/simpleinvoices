<?php
namespace SimpleInvoices\Mvc\Service;

use Interop\Container\ContainerInterface;
use SimpleInvoices\Mvc\DispatchListener;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use SimpleInvoices\Mvc\Controller\ControllerManager;

class DispatchListenerFactory implements FactoryInterface
{
    /**
     * Create the default dispatch listener.
     *
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return DispatchListener
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        return new DispatchListener($container->get(ControllerManager::class));
    }
    
    /**
     * Create and return DispatchListener instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return DispatchListener
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DispatchListener::class);
    }
}