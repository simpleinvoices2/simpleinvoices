<?php
namespace SimpleInvoices\Smarty;

class ConfigProvider
{
    /**
     * Retrieve configuration for Smarty.
     *
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencyConfig(),
            'view_manager'  => $this->getViewManagerConfig(),
        ];
    }

    /**
     * Retrieve dependency config for Smarty.
     *
     * @return array
     */
    public function getDependencyConfig()
    {
        return [
            'factories' => [
                Smarty::class => Service\SmartyFactory::class,
                View\Strategy\PhpRendererStrategy::class => Service\SmartyStrategyFactory::class,
                View\Renderer\PhpRenderer::class => Service\SmartyPhpRendererFactory::class,
                View\Resolver\TemplatePathStack::class => Service\SmartyTemplatePathStackFactory::class,
            ],
        ];
    }
    
    /**
     * Retrieve dependency config for Smarty.
     *
     * @return array
     */
    public function getViewManagerConfig()
    {
        return [
            'strategies' => [
                View\Strategy\PhpRendererStrategy::class,
            ],
        ];
    }
}
