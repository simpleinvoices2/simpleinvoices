<?php
namespace SimpleInvoices\PatchManager;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Metadata\Metadata;

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
   
}