<?php

class Database {

    private static $_instance;
    private $_db_name;
    private $_db_host;
    private $_db_user;
    private $_db_pass;
    private $_db_port;
    private $_connection;
    private $_results;
    private $_errors;
    private $_last_query;

    private $_force_insert = false;

    public function __construct($db_name, $db_user, $db_pass, $db_host = 'localhost', $db_port = 3307) {

        $this->_db_host = $db_host;
        $this->_db_name = $db_name;
        $this->_db_pass = $db_pass;
        $this->_db_port = $db_pass;

        $this->_connect();
    }

    /**
     * Returns the current database object. If not previously initialized it will connect & init.
     * @return static
     */
    public static function instance() {

        // Verify we don't already have a global instance. If not create a fresh one
        if( ! self::$_instance ) {
            self::$_instance = new static(DB_HOST, DB_USER, DB_PASS);
        }

        return self::$_instance;
    }

    public function __destruct() {

        $this->_free_query_results();
        $this->_disconnect();
    }

    /**
     * Connects to MySQL and selects the requested database
     * @param string|bool $database Database you would like to connect to
     */
    private function _connect($database = false) {

        // @todo: this should trigger an error instead of die.
        $this->_connection = mysql_connect($this->_db_host, $this->_db_user, $this->_db_pass) or die('Unable to connect to SQL server');

        // Select our database. Allow override of database name via $database
        mysql_select_db($database ?: $this->_db_name, $this->_connection) or die('Unable to select database "' . $database ?: $this->_db_name . '"');
    }

    /**
     * Closes current SQL connection
     */
    private function _disconnect() {

        if(is_resource($this->_connection)) {
            mysql_close($this->_connection);
        }
    }

    /**
     * Free's any currently open MySQL resources
     */
    private function _free_query_results() {

        if( ! empty($this->results) ) {
            foreach($this->_results as $result_id) {
                $this->free_query_result($result_id);
            }
        }
    }

    /**
     * Free's a MySQL resource by supplying a result_id
     * @param $result_id int result_id of the previously executed query
     * @return bool
     */
    public function free_query_result($result_id) {
        if( is_resource($this->_results[$result_id]) ) {
            mysql_free_result($this->_results[$result_id]);
            unset($this->_results[$result_id]);

            return true;
        }

        return false;
    }

    /**
     * Gets the referenced MySQL resource id from a results_id
     * @param $results_id int id of a previously executed query
     * @return mixed
     */
    public function get_mysql_resource_id($results_id) {
        return $this->_results[$results_id];
    }

    /**
     * Returns the number of available resources for a MySQL resource
     * @param $result_id
     * @return bool|int
     */
    public function count_rows($result_id) {
        if( ! is_resource($this->_results[$result_id]) ) {
            return false;
        }

        return mysql_num_rows($this->_results[$result_id]);
    }

    /**
     * Returns the next available row
     * @param $result_id
     * @param $format string the name of the class to use for storage. to retrieve an array simply set $format to false
     * @return array|bool|stdClass|mixed
     */
    public function fetch_row($result_id, $format = 'stdClass') {

        // Return false if our resource is invalid.
        if( ! is_resource($this->_results[$result_id]) ) {
            return false;
        }

        // Fetch our row from MySQL
        $row = mysql_fetch_array($this->_results[$result_id], MYSQL_ASSOC);

        // Verify if we are requesting a formatted object.
        if($format === false) {
            return $row;
        } else {

            // Create a new blank object. If an existing defined class exists use it, otherwise default to the stdClass
            $obj = class_exists($format) ? new $format() : new stdClass();

            // Fill our object with the array data
            foreach($row as $column=>$val) {
                $obj->{$column} = $val;
            }

            // Return our object
            return $obj;
        }
    }

    /**
     * Fetch all available rows for a MySQL resource
     * @param $result_id
     * @param string $format
     * @return array|bool|stdClass|mixed
     */
    public function fetch_rows($result_id, $format = 'stdClass') {

        $data = false;
        while($row = $this->fetch_row($result_id, $format)) {
            $data[] = $format;
        }

        return $data;
    }

    /**
     * Run a custom SQL query. This will return true/false for non return types (delete, update, etc)
     * and will default to returning stdClass objects for each row of a retrieval. You may specify the object type
     * and or an array by updating $format
     * @param $sql
     * @param string $format name of the class to return. Set to false to return an array.
     * @return array|bool|mixed|stdClass
     */
    public function query($sql, $format = 'stdClass') {

        $result_id = $this->_query($sql);

        if( ! $this->get_error($result_id) && ! $this->count_rows($result_id) ) {
            return true;
        }

        // Retrieval our rows
        $data = $this->fetch_rows($result_id, $format);

        $this->free_query_result($result_id);

        return ! empty($data) ? $data : false;
    }

    /**
     * Executes an SQL query and returns a result_id ($this->results[]) that references a MySQL resource
     * @param string $sql SQL Query to execute
     * @return int
     */
    private function _query($sql) {

        // Save our current query in case we need to reference it later
        $this->_last_query = $sql;

        // Calculate the current results id based off the current size
        $results_id = count($this->_results);

        // Save our results id in our stack
        if( ! $resource_id = mysql_query($sql, $this->_connection) ) {
            $this->_save_error(mysql_error(), $sql, $results_id);
        }

        // Store our resource_id in our stack
        $this->_results[$results_id] = $resource_id;

        // Return just the results_id
        return $results_id;
    }

    /**
     * Insert's a data set into a table. This pre-computes an insert query. If $this->_force_insert is set to true
     * then all fields will bet inserted with an "ON DUPLICATE KEY UPDATE" command.
     * @param string $table table to insert into
     * @param array|object $data data to be inserting
     * @return int
     */
    private function _insert($table, $data) {

        // If we were supplied an object, convert it to an array before continuing.
        if( is_object($data) ) {
            $data = get_object_vars($data);
        }

        // We need to escape each column in the case that a user tries to insert a column with a reserved name
        foreach($data as &$column => $value) {
            $column = "`$column`";
        }

        // Generate a basic SQL insert query
        $sql = "INSERT INTO $table (" . implode(', ', array_keys($data)) . ") VALUES (" . implode(', ', array_values($data)) . ')';

        // Check if we currently are pending for a forced insert
        if($this->_force_insert == true) {
            $sql .= ' ON DUPLICATE KEY UPDATE ' . $this->_get_duplicate_key_insert_string($data);
        }

        // Execute our query. If it returns successfully then return our referenced id
        return $this->_query($sql);
    }

    /**
     * Insert's a data set into a table This can accept both arrays & objects
     * @param string $table
     * @param array|object $data
     * @return int
     */
    public function insert($table, $data) {
        return $this->_insert($table, $data);
    }

    /**
     * Mimics's Database->insert with the exception that all items are set to "ON DUPLICATE KEY UPDATE" to over write
     * any possibly existing rows.
     * @param string $table
     * @param array|object $data
     * @return int
     */
    public function force_insert($table, $data) {

        // Store the current state so that way we can revert back to it (in the case that it it was globally enabled)
        $force_state = $this->_force_insert;

        // Enable the forced_insert flag
        $this->_force_insert = true;

        // Execute
        $result = $this->_insert($table, $data);

        // Revert our state to the previously set
        $this->_force_insert = $force_state;

        return $result;
    }

    private function _get_duplicate_key_insert_string(array $data) {

        $insert_values = array();
        foreach($data as $column => $value) {
            $insert_string[] = "`$column` = VALUES('$value')";
        }

        return implode(', ', $insert_values);
    }

    /*
     * These following objects should be similar to Wigum::Core:: functionality that lets you "search" for a single instance of an object
     */
    public function get_object() {
        throw new Exception('This function is currently not supported');
    }

    public function get_objects() {
        throw new Exception('This function is currently not supported');
    }

    public function delete_object() {
        throw new Exception('This function is currently not supported');
    }

    /**
     * Gets an error from the current stack. This allows us to modify the raw data if required.
     * @param $error_id
     * @return mixed|stdClass
     */
    public function get_error($error_id) {
        return $this->_errors[$error_id];
    }

    /**
     * Save a new error into the error stack
     * @param string $error error message received
     * @param string $query query that was executed
     * @param $result_id
     */
    public function _save_error($error, $query, $result_id) {

        // Create a new generic class to hold the error (this is so we can expand upon this later)
        $e = new stdClass();
        $e->error = $error;
        $e->query = $query;

        $this->_errors[$result_id ?: count($this->_results)] = $e;
    }

    /**
     * Checks the last error stored from the current instance
     * @return stdClass|bool
     */
    public static function get_last_error() {
        return end(self::instance()->_errors) ?: false;
    }
}