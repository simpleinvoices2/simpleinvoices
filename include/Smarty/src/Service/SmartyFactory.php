<?php
namespace SimpleInvoices\Smarty\Service;

use Interop\Container\ContainerInterface;
use Smarty;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SmartyFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return Smarty
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $smarty = new Smarty();
        
        return $smarty;
    }

    /**
     * Create and return PhpRenderer instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return PhpRenderer
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, Smarty::class);
    }
}
