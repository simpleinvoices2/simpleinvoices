<?php
namespace SimpleInvoices\SystemDefault;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Metadata\Metadata;
use Zend\Db\Sql\Sql;
use Zend\Session\Container;
use Zend\Db\Adapter\Driver\ResultInterface;

class SystemDefaultManager
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * Cache of system defaults
     *  
     * @var array
     */
    protected $defaults = [];
    
    /**
     * @var string
     */
    protected $table;
    
    /**
     * 
     * @var bool
     */
    protected $tableExists;
    
    public function __construct(AdapterInterface $adapter, $table = null)
    {
        $table = !empty($table) ? $table : TB_PREFIX . 'system_defaults';
        $this->adapter = $adapter;
        $this->setTable($table);
    }
    
    /**
     * Get a default system setting.
     * 
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function get($name, $default = null)
    {
        // If the table does not exist return the default value right awway.
        if (!$this->tableExists) {
            return $default;
        }
        
        // Check the cache for a fast result
        if (isset($this->defaults[$name])) {
            return $this->defaults[$name];
        }
        
        // Query the database for my setting
        $session = new Container('SI_AUTH');
        $domain_id = isset($session->domain_id) ? $session->domain_id : 1;
        
        $sql    = new Sql($this->adapter);
        $select = $sql->select($this->table);
        
        $select->where([
            'name'      => $name,
            'domain_id' => $domain_id,
        ]);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        
        if (($result instanceof ResultInterface) && ($result->isQueryResult()) && ($result->getAffectedRows())) {
            $current = $result->current();
            $this->defaults[$name] = $current['value'];
            return $current['value'];
        }
        
        return $default;
    }
    
    public function has($name)
    {
        // If the table does not exist return false right away.
        if (!$this->tableExists) {
            return false;
        }
        
        // Check the cache for a fast result
        if (isset($this->defaults[$name])) {
            return true;
        }
        
        // Query the database for my setting
        $session = new Container('SI_AUTH');
        $domain_id = isset($session->domain_id) ? $session->domain_id : 1;
        
        $sql    = new Sql($this->adapter);
        $select = $sql->select($this->table);
        
        $select->where([
            'name'      => $name,
            'domain_id' => $domain_id,
        ]);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        
        if (($result instanceof ResultInterface) && ($result->isQueryResult()) && ($result->getAffectedRows())) {
            $current = $result->current();
            $this->defaults[$name] = $current['value'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Set a default system setting.
     *
     * TODO: throw exception if something goes wrong!
     * 
     * @param string $name
     * @param mixed $value
     * @return SystemDefaultManager
     */
    public function set($name, $value)
    {
        // If the table does not exist return the default value right awway.
        if (!$this->tableExists) {
            throw new \RuntimeException(sprintf(
                'The table %s does not exists',
                $this->table
            ));
        }
        
        $sql = new Sql($this->adapter);
        
        if ($this->has($name)) {
            // It exists so do an update
            $update = $sql->update($this->table);
            $update->set([
                'value' => $value
            ]);
            $update->where([
                'name'      => $name,
                'domain_id' => $domain_id,
            ]);
            
            $statement = $sql->prepareStatementForSqlObject($update);
            $result    = $statement->execute();
            
            if (!$result instanceof ResultInterface) {
                return false;
            }

            $affectedRows = $result->getAffectedRows();
            if ($affectedRows < 1) {
                return false;
            }
            
            $this->defaults[$name] = $value;
            return true;
        } else {
            // Proceed with insert
            $insert = $sql->insert($this->table);
            $insert->values([
                'name'      => 'name',
                'value'     => $value,
                'domain_id' => $domain_id,
            ]);
            
            $statement = $sql->prepareStatementForSqlObject($insert);
            $result    = $statement->execute();
            
            if (!$result instanceof ResultInterface) {
                return false;
            }

            $affectedRows = $result->getAffectedRows();
            if ($affectedRows < 1) {
                return false;
            }
            
            $this->defaults[$name] = $value;
            return true;
        }
        
        return $this;
    }

    /**
     * Set the system defaults table.
     * 
     * @param string $table
     */
    public function setTable($table)
    {
        // TODO: Sanity checks
        
        $this->table    = $table;
        $this->defaults = [];
        
        // Check if the table exists
        $metadata = new Metadata($this->adapter);
        $tables = $metadata->getTableNames();
        $this->tableExists = in_array($table, $tables);
        
        // chain
        return $this;
    }
}