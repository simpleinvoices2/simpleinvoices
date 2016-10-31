<?php
namespace SimpleInvoices\Service;

use Interop\Container\ContainerInterface;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Resolver\ResolverInterface;
use Zend\View\Resolver\TemplatePathStack;

class ViewTemplatePathStackFactory implements FactoryInterface
{
    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string             $requestedName
     * @param  null|array         $options
     * @return ResolverInterface
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        // Settings
        $theme             = 'default';
        $themes_folder     = './templates';
        $use_custom        = true;
        $my_custom_path    = './custom';
        
        // TODO: Make this better
        $config = $container->get('SimpleInvoices\Config');
        
        // Load extensions
        $extensions = $container->get('ModuleManager')->getModules();
        
        // Paths
        // ============================================
        $paths = [];
        
        // extension templates
        // TODO: Make this better
        foreach($extensions as $extension) {
            if ($extension->enabled == "1") {
                $extensionThemesPath = './extensions/' . $extension->name . '/templates';
                
                if (file_exists($extensionThemesPath . '/' . $theme)) {
                    $paths[] = $extensionThemesPath . '/' . $theme;
                }
                
                if (file_exists($extensionThemesPath . '/default')) {
                    $paths[] = $extensionThemesPath . '/default';
                }
            }
        }
        
        // custom templates
        if (($use_custom) && is_dir($my_custom_path)) {
            $paths[] = $my_custom_path . '/default_template/';
        }
        
        // The theme template
        if (is_dir($themes_folder . '/' . $theme)) {
            $paths[] = $themes_folder . '/' . $theme . '/';
        }
        
        // Inheritance for the default theme as default
        if (strcmp($theme, 'default') !== 0) {
            $paths[] = $themes_folder . '/default/';
        }
        
        // The resolver
        // ============================================
        $templatePathStack = new TemplatePathStack();
        $templatePathStack->addPaths($paths);
        $templatePathStack->setDefaultSuffix('tpl');
        // Return the resolver
        return $templatePathStack;
    }

    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return ResolverInterface
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return $this($serviceLocator, TemplatePathStack::class);
    }
}