<?php
namespace SimpleInvoices\Smarty\Service;

use Interop\Container\ContainerInterface;
use SimpleInvoices\Smarty\View\Resolver\TemplatePathStack;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;


class SmartyTemplatePathStackFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return TemplatePathStack
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $instance = new TemplatePathStack();
        //$instance->addPaths($orig->getPaths()->toArray());
        $instance->setDefaultSuffix('tpl');
        
        return $instance;
    }
    
    /**
     * Create and return PhpRenderer instance
     *
     * For use with zend-servicemanager v2; proxies to __invoke().
     *
     * @param ServiceLocatorInterface $container
     * @return TemplatePathStack
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, TemplatePathStack::class);
    }
}