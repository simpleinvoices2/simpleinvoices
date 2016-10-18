<?php
namespace SimpleInvoices\ModuleManager;

use Zend\EventManager\Event;

class ModuleEvent extends Event
{
    /**
     * Module events triggered by eventmanager
     */
    const EVENT_LOAD_MODULES        = 'loadModules';
}