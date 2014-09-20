<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   database.php
 *    @name:         Database
 *    @namespace:    library\interfaces
 *    @abstract:     Signature methods for all Db concrete classes
 *    @uses:         \library\Db, \mysqli
 */
namespace library\interfaces;

interface Database  {

    public function connect($host='', $user='', $pw='', $db='', $persistant=true) ;

    /**
     * Get the object
     *
     * @return self
     */
    public static function getInstance($host='', $db='');

    /**
     * Close database connection
     *
     * @return boolean
     */
    public function close();

    /**
     * Select database
     *
     * @return boolean
     */
    function select_db($db='');

    /**
     * Return name of selected database
     *
     * @return string
     */
    function selected_db();


    // Performs an SQL query and returns the result pointer or false
    // if there is an error.
    function select($sql);

    function free_result($result);

    function delete($sql);

    // Performs an SQL query with the assumption that only ONE column and
    // one result are to be returned.
    // Returns the one result.
    function select_one($sql);

    function get_row($result, $type='MYSQL_BOTH');

    // Useful during development for debugging  purposes.  Simple dumps a
    // query to the screen in a table.
    function dump_query($sql);

    // Inserts data in the database via SQL query.
    // Returns the id of the insert or true if there is not auto_increment
    // column in the table.  Returns false if there is an error.
    function insert_sql($sql);

    // Updates data in the database via SQL query.
    // Returns the number or row affected or true if no rows needed the update.
    // Returns false if there is an error.
    function update_sql($sql);

    // Inserts a row into the database from key->value pairs in an array. The
    // array passed in $data must have keys for the table's columns. You can
    // not use any MySQL functions with string and date types with this
    // function.  You must use insert_sql for that purpose.
    // Returns the id of the insert or true if there is not auto_increment
    // column in the table.  Returns false if there is an error.
    function insert_array($table, $data) ;

    // Updates a row into the database from key->value pairs in an array. The
    // array passed in $data must have keys for the table's columns. You can
    // not use any MySQL functions with string and date types with this
    // function.  You must use insert_sql for that purpose.
    // $condition is basically a WHERE claus (without the WHERE). For example,
    // "column=value AND column2='another value'" would be a condition.
    // Returns the number or row affected or true if no rows needed the update.
    // Returns false if there is an error.
    function update_array($table, $data, $condition);

    // executes the SQL commands from an external file.
    function execute_file ($file);

    // Gets information about a particular column using the $this->db_link->fetch_field
    // function.  Returns an array with the field info or false if there is
    // an error.
    function get_column_type($table, $column);

    function get_table_types($table);

    // Returns the date in a format for input into the database.  You can pass
    // this function a timestamp value such as time() or a string value
    // such as '04/14/2003 5:13 AM'.
    function sql_date_format($value);

    // Prints the last error to the screen in a nicely formatted error message.
    // If $show_query is true, then the last query that was executed will
    // be displayed aswell.
    function print_last_error($show_query);

    // Prints the last query that was executed to the screen in a nicely formatted
    // box.
    function print_last_query() ;

}

?>