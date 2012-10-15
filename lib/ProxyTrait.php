<?php
/**
 * This file is part of the foglcz/DibiProxy github project.
 *
 * @author Pavel Ptacek
 * @class LGPL
 */

/**
 * The main proxy trait, used in individual drivers.
 *
 * @author Pavel Ptacek
 * @licence LGPL
 */
trait DibiProxyTrait {

    /**
     * @var string
     */
    private $proxyFile = '';

    /**
     * @var array
     */
    private $proxyData = array();

    /**
     * @var bool
     */
    private $proxyEnabled = false;

    /**
     * @var bool
     */
    private $proxyRunning = false;

    /**
     * @var string|bool
     */
    private $proxyOverride = false;

    /**
     * @var string
     */
    private $proxyCurrentHash;

    /**
     * @var int
     */
    private $proxyCursor = 0;

    /**
     * Get current hash
     * @param string $append
     * @return string
     */
    private function getCurrentHash($append = false) {
        return $this->proxyCurrentHash . ($append ? '.' . $append : '');
    }

    /**
     * Execute the function & return result
     *
     * @param string $method
     * @param string ....
     * @return mixed
     */
    private function proxyResult() {
        $args = func_get_args();
        $function = array_shift($args);

        if($this->proxyRunning) {
            return $this->proxyData[$this->getCurrentHash($function)];
        }

        $return = call_user_func_array(array($this, 'parent::' . $function), $args);

        if($this->proxyEnabled) {
            $this->proxyData[$this->getCurrentHash($function)] = $return;
        }

        return $return;
    }

    /**
     * Execute function when not in running mode
     *
     * @param string method
     * @param mixed ....
     */
    private function proxyExec() {
        $args = func_get_args();
        $function = array_shift($args);

        if(!$this->proxyRunning) {
            call_user_func_array(array($this, 'parent::' . $function), $args);
        }
    }

    /**
     * Connects & enables the driver when needed
     *
     * @param array $config
     * @throws DibiException
     */
    public function connect(array &$config) {
        if(isset($config['proxy'])) {
            $this->proxyEnabled = true;
        }

        if($this->proxyEnabled && is_string($config['proxy'])) {
            $this->proxyFile = $config['proxy'];
        }
        elseif($this->proxyEnabled) {
            $this->proxyFile = __DIR__ . '/' . __CLASS__ . '.dat';
        }

        // Lazy-load data when needed
        try {
            parent::connect($config);
        }
        catch(DibiException $ex) {
            if($this->proxyEnabled) {
                $this->proxyRunning = true;
                $this->proxyData = unserialize(file_get_contents($this->proxyFile));
            }
            else {
                throw $ex;
            }
        }
    }

    /**
     * Save data when done
     */
    public function __destruct() {
        parent::__destruct();

        if(!$this->proxyEnabled) {
            return;
        }

        if(file_exists($this->proxyFile)) {
            $data = unserialize(file_get_contents($this->proxyFile));
        }
        else {
            $data = array();
        }

        $data = array_merge($data, $this->proxyData);
        file_put_contents($this->proxyFile, serialize($data));
    }

    /**
     * Cache current query hash if running
     *
     * @param $sql
     * @return mixed
     */
    public function query($sql) {
        if($this->proxyEnabled) {
            $this->proxyCurrentHash = $this->proxyOverride ? $this->proxyOverride : $sql;
            $this->proxyOverride = false;
            $this->proxyCursor = 0;
        }

        // Fail silently
        try {
            return parent::query($sql);
        }
        catch(DibiException $e) {
            if(!$this->proxyRunning) {
                throw $e;
            }
        }

        return $this;
    }

    /**
     * # of affected rows
     */
    public function getAffectedRows() {
        return $this->proxyResult('getAffectedRows');
    }

    /**
     * Insert # of the record
     */
    public function getInsertId($sequence) {
        return $this->proxyResult('getInsertId', $sequence);
    }

    /**
     * Start transaction
     */
    public function begin($savepoint = NULL) {
        $this->proxyExec('begin', $savepoint);
    }

    /**
     * End transaction
     */
    public function commit($savepoint = NULL) {
        $this->proxyExec('commit', $savepoint);
    }

    /**
     * Rollback
     */
    public function rollback($savepoint = NULL) {
        $this->proxyExec('rollback', $savepoint);
    }

    /**
     * Get row count
     */
    public function getRowCount() {
        return $this->proxyResult('getRowCount');
    }

    /**
     * Fetch is implemented specifically, as it moves the internal cursor
     */
    public function fetch($assoc) {
        if($this->proxyRunning) {
            $return = $this->proxyData[$this->getCurrentHash($assoc ? 'fetchAssoc' : 'fetchCommon')][$this->proxyCursor++];
            return $return;
        }

        $return = parent::fetch($assoc);

        if($this->proxyEnabled) {
            if($this->proxyCursor === 0) {
                $this->proxyData[$this->getCurrentHash($assoc ? 'fetchAssoc' : 'fetchCommon')] = array();
            }
            $this->proxyData[$this->getCurrentHash($assoc ? 'fetchAssoc' : 'fetchCommon')][$this->proxyCursor++] = $return;
        }

        return $return;
    }

    /**
     * Moves to #th row
     */
    public function seek($row) {
        $this->proxyCursor = $row;
    }

    /**
     * free()
     */
    public function free() {
        $this->proxyExec('free');
    }

    /**
     * Result columns
     */
    public function getResultColumns() {
        return $this->proxyResult('getResultColumns');
    }

    /**
     * Get all tables in database
     */
    public function getTables() {
        $this->proxyCurrentHash = '__sql.getTables';
        return $this->proxyResult('getTables');
    }

    /**
     * Get all columns in a table
     */
    public function getColumns($table) {
        $this->proxyCurrentHash = '__sql.getColumns.' . $table;
        return $this->proxyResult('getColumns', $table);
    }

    /**
     * Get indexes for table
     */
    public function getIndexes($table) {
        $this->proxyCurrentHash = '__sql.getIndexes.' . $table;
        return $this->proxyResult('getIndexes', $table);
    }

    /**
     * Get foreign keys
     */
    public function getForeignKeys($table) {
        $this->proxyCurrentHash = '__sql.getForeignKeys.' . $table;
        return $this->proxyResult('getForeignKeys', $table);
    }

    /**
     * Get indices
     */
    public function getIndices($table) {
        $this->proxyCurrentHash = '__sql.getIndices.' . $table;
        return $this->proxyResult('getIndices', $table);
    }

    /**
     * Get constraints
     */
    public function getConstraints($table) {
        $this->proxyCurrentHash = '__sql.getConstraints.' . $table;
        return $this->proxyResult('getConstraints', $table);
    }

    /**
     * Get triggers metadata
     */
    public function getTriggersMeta($table = NULL) {
        $this->proxyCurrentHash = '__sql.getTriggersMeta.' . $table;
        return $this->proxyResult('getTriggersMeta', $table);
    }

    /**
     * Get triggers
     */
    public function getTriggers($table = NULL) {
        $this->proxyCurrentHash = '__sql.getTriggers.' . $table;
        return $this->proxyResult('getTriggers', $table);
    }

    /**
     * Get procedures metadata
     */
    public function getProceduresMeta() {
        $this->proxyCurrentHash = '__sql.getProceduresMeta';
        return $this->proxyResult('getProceduresMeta');
    }

    /**
     * Get procedures
     */
    public function getProcedures() {
        $this->proxyCurrentHash = '__sql.getProcedures';
        return $this->proxyResult('getProcedures');
    }

    /**
     * Get generators
     */
    public function getGenerators() {
        $this->proxyCurrentHash = '__sql.getGenerators';
        return $this->proxyResult('getGenerators');
    }

    /**
     * Get functions
     */
    public function getFunctions() {
        $this->proxyCurrentHash = '__sql.getFunctions';
        return $this->proxyResult('getFunctions');
    }

    /**
     * Set proxy override for next statement sent to the database
     * @param $ident
     */
    public function setProxyOverride($ident) {
        $this->proxyOverride = $ident;
    }
}

/**
 * Proxy exception
 */
class DibiProxyException extends DibiException {}

/**
 * Proxy data not found
 */
class DibiProxyDataNotFound extends DibiProxyException {}