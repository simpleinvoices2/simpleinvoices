<?php
namespace SimpleInvoices\View\Resolver;

use SimpleInvoices\View\Renderer\RendererInterface;

interface ResolverInterface
{
    /**
     * Resolve a template/pattern name to a resource the renderer can consume
     *
     * @param  string $name
     * @param  null|RendererInterface $renderer
     * @return mixed
     */
    public function resolve($name, RendererInterface $renderer = null);
}