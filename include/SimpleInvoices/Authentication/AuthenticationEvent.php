<?php
namespace SimpleInvoices\Authentication;

use Zend\EventManager\Event;
use Zend\Authentication\Adapter\AdapterInterface;

class AuthenticationEvent extends Event
{
    const EVENT_AUTHENTICATE      = 'authenticate';
    const EVENT_AUTHENTICATE_POST = 'authenticate.post';
    
    /**
     * 
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * Get the authentication adapter.
     * 
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }
    
    /**
     * Set the authentication adapter.
     * 
     * @param AdapterInterface $adapter
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->setParam('authentication_adapter', $adapter);
        $this->adapter = $adapter;
        return $this;
    }
}