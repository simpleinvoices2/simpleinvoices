<?php
namespace SimpleInvoices\Deprecate;

use SimpleInvoices\SystemDefault\SystemDefaultManager;

class SqlQueries
{
    /**
     * The database handle
     * 
     * @var \PDO|null
     */
    protected $dbh;
    
    /**
     * The database handle for logs
     *
     * @var \PDO|null
     */
    protected $logDbh;
    
    protected $canLog = false;
    
    protected $config;
    
    public function __construct($config)
    {
        $this->config = $config;
        
        $auth_session = new \Zend\Session\Container('SI_AUTH');
        
        if(LOGGING) {
            //Logging connection to prevent mysql_insert_id problems. Need to be called before the second connect...
            $this->logDbg = $this->initializePDO($config);
        }
        
        $this->dbh = $this->initializePDO($config);
        
        // Cannot redfine LOGGING (withour PHP PECL runkit extension) since already true in define.php
        // Ref: http://php.net/manual/en/function.runkit-method-redefine.php
        // Hence take from system_defaults into new variable
        // Initialise so that while it is being evaluated, it prevents logging
        $this->canLog = (LOGGING && (isset($auth_session->id) && $auth_session->id > 0) && $this->getDefaultLoggingStatus());
    }
    
    public function canLog()
    {
        return $this->canLog;
    }
    
    /**
     * Chack a table exists.
     * 
     * @param string $table
     * @return boolean
     */
    function checkTableExists($table = null) 
    {
        $table == empty($table) ? TB_PREFIX."biller" : $table;
    
        //  echo $table;
        switch ($this->config->database->adapter)
        {
            case "pdo_pgsql":
                $sql = 'SELECT 1 FROM pg_tables WHERE tablename = ' . $table . ' LIMIT 1';
                break;
            case "pdo_sqlite":
                $sql = 'SELECT * FROM ' . $table . ' LIMIT 1';
                break;
            case "pdo_mysql":
            default:
                //mysql
                //$sql = "SELECT 1 FROM INFORMATION_SCHEMA.TABLES where table_name = :table LIMIT 1";
                $sql = "SHOW TABLES LIKE '".$table."'";
                break;
        }
    
        //$sth = $dbh->prepare($sql);
        $sth = $this->dbQuery($sql);
        if ($sth->fetchAll()) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * Used for logging all queries.
     * 
     * @param string $sqlQuery
     */
    public function dbLogger($sqlQuery) 
    {
        // For PDO it gives only the skeleton sql before merging with data
        $auth_session = new \Zend\Session\Container('SI_AUTH');
        
        $userid = $auth_session->id;
        if($this->canLog && (preg_match('/^\s*select/iD', $sqlQuery) == 0) && (preg_match('/^\s*show\s*tables\s*like/iD',$sqlQuery) == 0)) {
            // Only log queries that could result in data/database  modification
            $last = null;
            $tth  = null;
            $sql  = "INSERT INTO ".TB_PREFIX."log (domain_id, timestamp, userid, sqlquerie, last_id) VALUES (?, CURRENT_TIMESTAMP , ?, ?, ?)";
    
            /* SC: Check for the patch manager patch loader.  If a
             *     patch is being run, avoid $log_dbh due to the
             *     risk of deadlock.
             */
            $call_stack = debug_backtrace();
            //SC: XXX Change the number back to 1 if returned to directly
            //    within dbQuery.  The joys of dealing with the call stack.
    
            if ($call_stack[2]['function'] == 'run_sql_patch') {
                /* Running the patch manager, avoid deadlock */
                $tth = $this->dbh->prepare($sql);
            } elseif (preg_match('/^(update|insert)/iD', $sqlQuery)) {
                $last = lastInsertId();
                $tth = $this->logDbh->prepare($sql);
            } else {
                $tth = $this->logDbh->prepare($sql);
            }
            
            $tth->execute(array($auth_session->domain_id, $userid, trim($sqlQuery), $last));
            unset($tth);
        }
    }
    
    public function dbQuery($sqlQuery, array $params = []) 
    {    
        $sth = $this->dbh->prepare($sqlQuery);
        try {
            $sth->execute($params);
            //$this->dbLogger($sqlQuery);
        } catch(\Exception $e){
            echo $e->getMessage();
            echo "dbQuery: Dude, what happened to your query?:<br /><br /> ".htmlsafe($sqlQuery)."<br />".htmlsafe(end($sth->errorInfo()));
        }
        
        return $sth;
    }
    
    
    /**
     * Get the Database handle.
     * 
     * TODO: This should not be needed once refactoring is done!
     * 
     * @return \PDO|null
     * 
     */
    public function getDbHandle()
    {
        return $this->dbh;
    }
    
    public function getDefaultGeneric($param, $bool=true, $domain_id='')
    {
        global $LANG;
        
        if (empty($domain_id)) {
            $auth_session = new \Zend\Session\Container('SI_AUTH');
            $domain_id    = $auth_session->domain_id;
        } else {
            $domain_id = $domain_id;
        }
        
        $sql = "SELECT value FROM ".TB_PREFIX."system_defaults s WHERE ( s.name = :param AND s.domain_id = :domain_id)";
        $sth = $this->dbQuery($sql, [':param' => $param, ':domain_id' => $domain_id]);
        $array = $sth->fetch();
        $paramval = (($bool) ? ($array['value'] == 1 ? $LANG['enabled'] : $LANG['disabled']) : $array['value']);
        return $paramval;
    }
    
    public function getDefaultLoggingStatus() 
    {
        global $serviceManager;
        $systemDefaults = $serviceManager->get(SystemDefaultManager::class);
        return (bool) $systemDefaults->get('logging', false);
    }
    
    /**
     * Get the Database handle for Logs.
     *
     * TODO: This should not be needed once refactoring is done!
     *
     * @return \PDO|null
     *
     */
    public function getLogDbHandle()
    {
        return $this->logDbh;
    }
    
    /**
     * This is the old db_connector()
     *
     * @param unknown $config
     * @return \PDO|null
     */
    protected function initializePDO($config)
    {
        /*
         * strip the pdo_ section from the adapter
         */
        $pdoAdapter = substr($config->database->adapter, 4);
        if (!$pdoAdapter) {
            die("No PDO adapter has been defined!");
        }
    
        if(!defined('PDO::MYSQL_ATTR_INIT_COMMAND') AND $pdoAdapter == "mysql" AND $config->database->adapter->utf8 == true) {
            simpleInvoicesError("PDO::mysql_attr");
        }
    
        try{
            switch ($pdoAdapter)
            {
    
                case "pgsql":
                    $connlink = new \PDO(
                    $pdoAdapter.':host='.$config->database->params->host.';	dbname='.$config->database->params->dbname,	$config->database->params->username, $config->database->params->password
                    );
                    break;
    
                case "sqlite":
                    $connlink = new \PDO(
                    $pdoAdapter.':host='.$config->database->params->host.';	dbname='.$config->database->params->dbname,	$config->database->params->username, $config->database->params->password
                    );
                    break;
    
                case "mysql":
                    switch ($config->database->utf8)
                    {
                        case true:
    
                            $connlink = new \PDO(
                            'mysql:host='.$config->database->params->host.'; port='.$config->database->params->port.'; dbname='.$config->database->params->dbname, $config->database->params->username, $config->database->params->password,  array( \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8;")
                            );
                            break;
    
                        case false:
                        default:
                            $connlink = new \PDO(
                            $pdoAdapter.':host='.$config->database->params->host.'; port='.$config->database->params->port.'; dbname='.$config->database->params->dbname,	$config->database->params->username, $config->database->params->password
                            );
                            break;
                    }
                    break;
            }
    
        } catch(\PDOException $exception) {
            simpleInvoicesError("dbConnection", $exception->getMessage());
            die($exception->getMessage());
        }
    
        return $connlink;
    }
}