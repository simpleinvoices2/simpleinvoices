<?php
namespace SimpleInvoices\PatchManager;

use Zend\Db\Adapter\AdapterInterface;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\Adapter\Driver\ResultInterface;

class PatchManager
{
    /**
     * @var AdapterInterface
     */
    protected $adapter;
    
    protected $doneSQLPatchesNumber;
    
    /**
     * @var unknown
     */
    protected $table;

    public function __construct(AdapterInterface $adapter, $table)
    {
        $this->adapter = $adapter;
        $this->table   = $table;
    }
    
    public function getNumberOfDoneSQLPatches() 
    {
        if (null !== $this->doneSQLPatchesNumber) {
            return $this->doneSQLPatchesNumber;
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