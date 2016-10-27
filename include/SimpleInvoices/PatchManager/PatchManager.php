<?php
namespace SimpleInvoices\PatchManager;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Metadata\Metadata;
use Zend\Db\ResultSet\ResultSet;

class PatchManager
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    /**
     * Number of done SQL patches.
     * 
     * @var int
     */
    protected $doneSQLPatchesNumber;
    
    protected $sqlPatchesCount = 0;
    
    /**
     * @var unknown
     */
    protected $table;

    protected $isActive = false;
    
    /**
     * Constructor
     * 
     * @param AdapterInterface $adapter The database adapter
     * @param unknown          $table   The table name for SQL patches.
     */
    public function __construct(AdapterInterface $adapter, $table = null)
    {
        $this->adapter = $adapter;
        if (null === $table) {
            $this->table = TB_PREFIX . 'sql_patchmanager';
        } else {
            $this->table   = $table;
        }
        
        // Check for the table
        $metadata = new Metadata($this->adapter);
        $tables   = $metadata->getTableNames();
        if (in_array($this->table, $tables)) {
            $this->isActive = true;
        }
    }
    
    protected function _applyPatch($ref, $patch)
    {
        try {
            $result = $this->adapter->getDriver()->getConnection()->execute($patch['patch']);
            //var_dump($result);die();
        } catch (\PDOException $e) {
            echo $ref . "\n\n<br />";
            throw $e;
            return false;
        } catch (\Exception $e) {
            echo $ref . "\n\n<br />";
            throw $e;
        }

        $sql    = new Sql($this->adapter);
        $insert = $sql->insert($this->table);
        $insert->values([
            'sql_patch_ref' => $ref,
            'sql_patch'     => $patch['name'],
            'sql_release'   => $patch['date'],
            'sql_statement' => $patch['patch']
        ]);
        
        $statement = $sql->prepareStatementForSqlObject($insert);
        $result    = $statement->execute();
        
        if (($result instanceof ResultInterface) && ($result->getAffectedRows())) {
            return $result->getAffectedRows();
        }
        
        return 0;
    }
    
    public function applyPatches()
    {
        if (!$this->isActive) {
            return false;
        }
        
        $patches        = $this->getPatches();
        $appliedPatches = $this->getAppliedSQLPatches()->toArray();
        
        $this->adapter->getDriver()->getConnection()->beginTransaction();
        
        try {
            foreach($patches as $patch) {
                if ($ref === 0) {
                    // Ignore patch 0
                    continue;
                }
                
                $found = false;
                foreach ($appliedPatches as $p) {
                    if ((int) $p['sql_patch_ref'] === (int) $patch['ref']) {
                        $found = true;
                        break;
                    }
                }
            
                if (!$found) {
                    // Should apply the patch
                    $result = $this->_applyPatch($ref, $patch);
                    if ($result === 0) {
                        $this->adapter->getDriver()->getConnection()->rollback();
                        return false;
                    }
                }
            }
            
            $this->adapter->getDriver()->getConnection()->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
            throw $e;
        }
        
        return false;
    }
    
    /**
     * Get the number of done SQL patches.
     *
     * @throws Exception\RuntimeException
     * @return int
     */
    public function getAppliedSQLPatches()
    {
        if (!$this->isActive) {
            return 0;
        }
    
        $resultSet = new ResultSet();
        $select    = new Select($this->table);
        $sql       = new Sql($this->adapter);
    
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
    
        $resultSet->initialize($result);
        
        return $resultSet;
    }
    
    /**
     * Get the number of done SQL patches.
     * 
     * @throws Exception\RuntimeException
     * @return int
     */
    public function getNumberOfDoneSQLPatches() 
    {
        if (null !== $this->doneSQLPatchesNumber) {
            return $this->doneSQLPatchesNumber;
        }
        
        if (!$this->isActive) {
            return 0;
        }
        
        $select = new Select($this->table);
        $select->columns([
            'count' => new Expression('count(sql_patch)'),
        ]);
        
        $sql = new Sql($this->adapter);
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $result    = $statement->execute();
        
        if (($result instanceof ResultInterface) && $result->isQueryResult() && $result->getAffectedRows()) {
            $data = $result->current();
            if (isset($data['count']) && is_numeric($data['count'])) {
                // Store the result to avoid multiple queries for this value
                $this->doneSQLPatchesNumber = ((int) $data['count']);
                return $this->doneSQLPatchesNumber;
            }
        }
        
        throw new Exception\RuntimeException('Unable to get the number of done SQL patches.');
    }
    

    public function hasNewPatches()
    {
        // -1 substracts patch #0
        $patches = $this->getPatches();
        if ($this->getNumberOfDoneSQLPatches() === count($patches)) {
            return false;
        }
        
        return true;
    }
    
    public function isActive()
    {
        return $this->isActive;
    }
    
    public function getPatches()
    {
        return json_decode(file_get_contents('databases/mysql/patches.json'), true);
    }
}