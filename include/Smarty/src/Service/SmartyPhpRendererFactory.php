<?php
namespace SimpleInvoices\Smarty\Service;

use Interop\Container\ContainerInterface;
use SimpleInvoices\Smarty\View\Renderer\PhpRenderer;
use Smarty;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class SmartyPhpRendererFactory implements FactoryInterface
{
    /**
     * @param  ContainerInterface $container
     * @param  string $name
     * @param  null|array $options
     * @return PhpRenderer
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $renderer = new PhpRenderer( $container->get(Smarty::class) );
        //$renderer->setHelperPluginManager($container->get('ViewHelperManager'));
        $renderer->setResolver($container->get('ViewResolver'));

        return $renderer;
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
        return $this($container, PhpRenderer::class);
    }
}
