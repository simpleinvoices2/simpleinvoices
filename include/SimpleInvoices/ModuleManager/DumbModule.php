<?php
namespace SimpleInvoices\ModuleManager;

use Zend\EventManager\EventInterface;
/**
 * This class is used for old modules that where not classes but a 
 * structure of files. In order to get refactoring done step-by-step
 * we need to allow backward compatibility and that is the goal of this
 * class.
 * 
 * TODO: deprecate and delete this class.
 */
class DumbModule
{
    public $extensionPath;
   
    public function onBootstrap(EventInterface $event)
    {
        if ($this->extensionPath) {
            if (file_exists($this->extensionPath . '/include/init.php')) {
                include_once $this->extensionPath . '/include/init.php';
            }
        }
    }
}