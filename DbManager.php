<?php

/**
 * Filename DbManager.php.
 *
 * Used to connect mysql database and run mysql database queries.
 *
 * @author Ashutosh Jha
 */
class DbManager {

    /**
     * Database host.
     *
     * @var string
     */
    protected $dbHost = '';

    /**
     * Database user.
     *
     * @var string
     */
    protected $dbUser = '';

    /**
     * Database password.
     *
     * @var string
     */
    protected $dbPassword = '';

    /**
     * Database name.
     *
     * @var string
     */
    protected $dbName = '';

    /**
     * Persistent database connection.
     *
     * @var string
     */
    protected $dbPcon = false;

    /**
     * Last inserted id.
     *
     * @var string
     */
    private $lastInsertId = null;

    /**
     * Last result.
     *
     * @var resource
     */
    private $lastResult;

    /**
     * Total number of rows.
     *
     * @var integer
     */
    private $totalNumRows;

    /**
     * Affected rows.
     *
     * @var interger
     */
    private $affectedRows;

    /**
     * Single row.
     *
     * @var object
     */
    private $row;

    /**
     * Multiple rows.
     *
     * @var object
     */
    private $rows;

    /**
     * Mysql link resource.
     *
     * @var resource
     */
    private $mysqlLink = 0;

    /**
     * Single ton instance.
     *
     * @var object
     */
    protected static $instance = null;

    /*
     * Class contructor.
     * @param boolean $pcon For persistent connection.
     *                      When we need to run multiple queries one followed by next;
     */

    public function __construct($pcon = false) {
        if ($pcon === true) {
            $this->dbPcon = true;
        }
        // Read Config for getting database config.
        $this->dbHost = DBM_HOST;
        $this->dbUser = DBM_USERNAME;
        $this->dbPassword = DBM_PASSWORD;
        $this->dbName = DBM_DATABASE;

        // Connect Database.
        $this->connectDb();
    }

    /**
     * Class distructor.
     */
    public function __destruct() {
        $this->disconnect();
    }

    /*
     * Class single ton instance.
     */

    public static function getInstance() {
        if (empty(self::$instance)) {
            self::$instance = new DbManager();
        }
        return self::$instance;
    }

    /**
     * Connect to specified MySQL server.
     *
     * @return boolean Returns TRUE on success or FALSE on error.
     */
    protected function connectDb() {
        // Added check for host, username and password.
        if (empty($this->dbHost) || empty($this->dbUser) || empty($this->dbName)) {
            return false;
        }
       
        // Open persistent or normal connection.
        if ($this->dbPcon) {
            $this->mysqlLink = mysqli_connect('p:' . $this->dbHost, $this->dbUser, $this->dbPassword,$this->dbName);
        } else {
            $this->mysqlLink =  mysqli_connect($this->dbHost, $this->dbUser, $this->dbPassword, $this->dbName);
        }
        
        if (!$this->mysqlLink) {
            exit('ERROR ESTABLISHING DATABASE CONNECTION TO HOST ::' . $this->dbHost);
        }
        // Set character encoding.
        mysqli_set_charset ($this->mysqlLink , 'utf8');
    }

    /**
     * Close current MySQL connection.
     *
     * @return object Returns TRUE on success or FALSE on error.
     */
    private function disconnect() {
        $success = mysqli_close($this->mysqlLink);
        if (!$success) {
            return false;
        } else {
            unset($this->lastSql);
            unset($this->lastResult);
            unset($this->mysqlLink);
            unset($this->mysqlLinkAgg);
        }
        return $success;
    }

    /**
     * Its execute query on repective database. Generic function assign query result. Also populate last inserted id, row, rows, affected rows and number of rows.
     *
     * @param string $query Sql query.
     *
     * @return Returns result set of query.
     */

    public function query($query) {
        // Make default value empty.
        $this->affectedRows = 0;
        $this->lastInsertId = null;
        $this->rows = array();
        $this->totalNumRows = 0;
        $this->row = array();
        $this->lastResult = '';
        if (strlen($query) > 0) {
            $this->lastResult = mysqli_query($this->mysqlLink, $query) or die(mysql_error());
            if ($this->lastResult) {
                if (gettype($this->lastResult) == 'object') {
                    /*
                     * For successful SELECT, SHOW, DESCRIBE or EXPLAIN queries mysqli_query() will return a mysqli_result object. For other successful queries mysqli_query() will return TRUE.
                     */

                    // Total number of rows.
                    $this->totalNumRows = mysqli_num_rows($this->lastResult);
                    if ($this->totalNumRows > 0) {
                        // Get rows.
                        while ($row = mysqli_fetch_object($this->lastResult)) {
                            $this->rows[] = $row;
                        }
                        // Get single row.
                        if (count($this->rows) > 0) {
                            $this->row = $this->rows[0];
                        }
                    }
                } else if ($this->lastResult === true) {
                    /*
                     * SQL statements, INSERT, UPDATE, DELETE, DROP, etc, mysqli_query() returns TRUE on success or FALSE on error.
                     */
                    // Affected rows based on last query.
                    $this->affectedRows = mysqli_affected_rows($this->mysqlLink);
                    // Last Inseted ID.
                    $this->lastInsertId = mysqli_insert_id($this->mysqlLink);
                }
            }
        }
        return $this->lastResult;
    }

    /**
     * Returns the last autonumber ID field from a previous INSERT query.
     *
     * @return integer ID number from previous INSERT query.
     */
    public function getLastInsertID() {
        return $this->lastInsertId;
    }

    /**
     * Returns the total number of rows from a previous INSERT query.
     *
     * @return integer number from previous INSERT query.
     */
    public function getTotalNumberOfRows() {
        return $this->totalNumRows;
    }

    /**
     * Returns the total number of affected rows from a previous query.
     *
     * @return integer number from previous query.
     */
    public function getAffectedRows() {
        return $this->affectedRows;
    }

    /**
     * Returns the total number of rows from a previous query.
     *
     * @return array of records previous query.
     */
    public function getRow() {
        return $this->row;
    }

    /**
     * Returns the a row of from a previous query.
     *
     * @return object of a record.
     */
    public function getRows() {
        return $this->rows;
    }

    /**
     * Insert Object.
     *
     * @param string $table   The table Name.
     * @param object &$object The table object in $object->field_name = value format.
     *
     * @return integer
     */
    public function insertObject($table, &$object) {
        $fields = array();
        $values = array();
        $insertId = null;
        $fmtsql = "INSERT INTO $table ( %s ) VALUES ( %s ) ";
        foreach (get_object_vars($object) as $k => $v) {
            if (is_array($v) || is_object($v) || $v === null) {
                continue;
            }
            $fields[] = "`$k`";
            $values[] = "'" . $this->getEscaped($v) . "'";
        }
        if (count($fields) > 0 && count($values) > 0) {
            $query = sprintf($fmtsql, implode(',', $fields), implode(',', $values));
            $this->query($query);
            $insertId = $this->lastInsertId;
        }
        return $insertId;
    }

    /**
     * Update Object.
     *
     * @param string  $table       Table Name.
     * @param object  &$object     Table object, $object->field_name = value.
     * @param array  $where        Where clause, ['field' => 'value'].
     *
     * @return string
     */
    public function updateObject($table, &$object, array $where) {
        // To Do: Handle Exception.
        $fmtsql = "UPDATE $table SET %s WHERE %s";
        $affectedRows = '';
        foreach (get_object_vars($object) as $k => $v) {
            
            if (is_array($v) || is_object($v) || $v === null) {
                continue;
            }
            $updateField = "`$k`";
            $updateValue = "'" . $this->getEscaped($v) . "'";
            $update[] = "`$k` = $updateValue";
        }

        foreach ($where as $field => $value) {
            $whereClause[] = "`$field` = '$value'";
        }

        if (count($update) > 0 && count($where) > 0) {
            $query = sprintf($fmtsql, implode(', ', $update), implode(' AND ', $whereClause));
            $this->query($query);
            $affectedRows = $this->affectedRows;
        }
        return $affectedRows;
    }

    /**
     * Get a database escaped string.
     *
     * @param string $text Text to escape.
     *
     * @return string
     */
    public function getEscaped($text) {
        return mysqli_real_escape_string($this->mysqlLink, $text);
    }
}