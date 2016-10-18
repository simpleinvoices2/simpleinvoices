<?php
namespace SimpleInvoices\ModuleManager\Model;


class Module
{
    public $id;
    public $name;
    public $description;
    public $domain_id;
    public $enabled;
    
    public function exchangeArray($data)
    {
        $this->domain_id = $data['domain_id'];
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->description = $data['description'];
        $this->enabled = (bool) $data['enabled'];
    }
    
    /**
     * Check if the module is enabled.
     * 
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }
    
    /**
     * Enable or disable the module.
     * 
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = (bool) $enabled;
        return $this;
    }
}