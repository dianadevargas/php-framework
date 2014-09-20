<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   dbMysql.php
 *    @name:         DbMysql
 *    @namespace:    library\databases
 *    @abstract:     Implements the abstract Db class with Mysqli
 *    @uses:         \library\Db, \mysqli
 */
namespace library\databases;
use \library\Db;
use \mysqli;

// constants used by class
define('MYSQL_TYPES_NUMERIC', MYSQLI_TYPE_INT24.' '.MYSQLI_TYPE_LONG.' '.MYSQLI_TYPE_SHORT.' '.MYSQLI_TYPE_FLOAT.' '.MYSQLI_TYPE_LONGLONG.' '.MYSQLI_TYPE_BIT.' '.MYSQLI_TYPE_TINY.' '.MYSQLI_TYPE_DOUBLE.' '.MYSQLI_TYPE_DECIMAL.' ');
define('MYSQL_TYPES_DATE', MYSQLI_TYPE_DATETIME.' '.MYSQLI_TYPE_TIMESTAMP.' '.MYSQLI_TYPE_YEAR.' '.MYSQLI_TYPE_DATE.' '.MYSQLI_TYPE_TIME.' ');
define('MYSQL_TYPES_STRING', MYSQLI_TYPE_STRING.' '.MYSQLI_TYPE_VAR_STRING.' '.MYSQLI_TYPE_CHAR.' '.MYSQLI_TYPE_BLOB.' '.MYSQLI_TYPE_TINY_BLOB.' '.MYSQLI_TYPE_MEDIUM_BLOB.' '.MYSQLI_TYPE_LONG_BLOB.' ');

class DbMysql extends  Db {

   public $last_error;          // holds the last error. Usually mysql_error()
   public $last_query;          // holds the last query executed.
   public $row_count;           // holds the last number of rows from a select

   private $host;               // mySQL host to connect to
   private $user;               // mySQL user name
   private $pw;                 // mySQL password
   private $db;                 // mySQL database to select
   private $tables = array();
   public  $pos;

   private $db_link;            // current/last database link identifier
   private $auto_slashes;       // the class will add/strip slashes when it can
   private static $_dbObjects = array();

   protected function __construct() {

      // class constructor.  Initializations here.

      // Setup your own default values for connecting to the database here. You
      // can also set these values in the connect() function and using
      // the select_database() function.

      $this->host    = 'localhost';
      $this->user    = '';
      $this->pw     = '';
      $this->db     = '';

      $this->auto_slashes = true;
      $this->pos    = count(self::$_dbObjects);
      self::$_dbObjects[$this->pos] = $this;
      return self::$_dbObjects[$this->pos];
   }

   public function connect($host='', $user='', $pw='', $db='', $persistant=true) {

      // Opens a connection to MySQL and selects the database.  If any of the
      // function's parameter's are set, we want to update the class variables.
      // If they are NOT set, then we're giong to use the currently existing
      // class variables.
      // Returns true if successful, false if there is failure.

      if (!empty($host)) $this->host= $host;
      if (!empty($user)) $this->user= $user;
      if (!empty($pw))   $this->pw  = $pw;
      if (!empty($db))   $this->db  = $db;

      // Establish the connection.
      if ($persistant)
         $this->db_link = new mysqli($this->host, $this->user, $this->pw);
      else
         $this->db_link = new mysqli($this->host, $this->user, $this->pw);

      // Check for an error establishing a connection
      if (!$this->db_link) {
         $this->last_error = $this->db_link->connect_error;
         return false;
      }

      // Select the database
      if (!empty($this->db)) {
      	if (!$this->select_db() && !empty($db)) {
      		return false;
      	}
      }

      return $this->db_link;  // success
    }

    /**
     * Get the object
     *
     * @return self
     */
    public static function getInstance($host='', $db='')
    {
        // New object
        $obj = isset(self::$_dbObjects)?count(self::$_dbObjects):0;
        if ($obj == 0) {
            return new DbMysql();
        }

        // ask for default
        if (empty($host) && empty($db)) {
                return self::$_dbObjects[$obj-1];    // the last object
        }

        // get existing object
        $pos = false;
        foreach (self::$_dbObjects as $p => $db) {
                if ($db == $db->db && $host == $db->host) {
                        $pos = $p;
                }
        }
        if ($pos === false) {
            return new DbMysql();
        }

        return self::$_dbObjects[$pos];
    }

    private static function __getDatabaseObject($host='', $db=''){
        $pos = false;
        foreach (self::$_dbObjects as $p => $db) {
            if ($db == $db->db && $host == $db->host) {
                $pos = $p;
            }
        }
        if (($pos == false) && empty($host) && empty($db)) {
            return count(self::$_dbObjects)-1;    // the last object
        } else {
           return $pos;
        }
    }


    function close() {
        if(!$this->db_link->close()){
            $this->last_error = $this->db_link->error;
            return false;
        }
        return true;
    }

   function select_db($db='') {

      // Selects the database for use.  If the function's $db parameter is
      // passed to the function then the class variable will be updated.

      if (!empty($db)) $this->db = $db;

      if (empty($this->db)) {
      	return false;
      }

      if (!$this->db_link->select_db($this->db)) {
         $this->last_error = $this->db_link->error;
         return false;
      }
      return true;
   }

   public function selected_db() {
     return $this->db;
   }


   public function select($sql) {

      // Performs an SQL query and returns the result pointer or false
      // if there is an error.

      $this->last_query = $sql;

      $r = $this->db_link->query($sql);
      if (!$r) {
         $this->last_error = $this->db_link->error;
         return false;
      }
      if (is_object($r))
          $this->row_count = $r->num_rows;
      else
          $this->row_count = 0;
      return $r;
   }

   public function free_result($result) {
       return is_a($result, 'mysqli_result')?$result->free_result():false;
   }

   public function delete($sql) {

      // Delete a record

      $this->last_query = $sql;

      $r = $this->db_link->query($sql);
      if (!$r) {
         $this->last_error = $this->db_link->error;
         return false;
      }

      return true;
   }

   public function select_one($sql) {

      // Performs an SQL query with the assumption that only ONE column and
      // one result are to be returned.
      // Returns the one result.

      $this->last_query = $sql;

      $r = $this->db_link->query($sql);
      if (!$r) {
         $this->last_error = $this->db_link->error;
         return false;
      }
      $ret = $r->fetch_array(MYSQLI_NUM);
      if ($r->num_rows > 1 || count($ret) > 1) {
         $this->last_error = "Your query in function select_one() returned more that one result.";
         return false;
      }
      if ($r->num_rows < 1 || count($ret) < 1) {
         $this->last_error = "Your query in function select_one() returned no results.";
         return false;
      }

      $this->free_result($r);
      if ($this->auto_slashes) return stripslashes($ret[0]);
      else return $ret[0];
   }

   public function get_row($result, $type='MYSQL_BOTH') {

      // Returns a row of data from the query result.  You would use this
      // function in place of something like while($row=$this->db_link->fetch_array($r)).
      // Instead you would have while($row = $db->get_row($r)) The
      // main reason you would want to use this instead is to utilize the
      // auto_slashes feature.

      if (!$result) {
         $this->last_error = "Invalid resource identifier passed to get_row() function.";
         return false;
      }

      if ($type == 'MYSQL_ASSOC') $row = $result->fetch_array(MYSQL_ASSOC);
      if ($type == 'MYSQL_NUM') $row = $result->fetch_array(MYSQL_NUM);
      if ($type == 'MYSQL_BOTH') $row = $$result->fetch_array(MYSQL_BOTH);

      if (!$row) return false;
      if ($this->auto_slashes) {
         // strip all slashes out of row...
         foreach ($row as $key => $value) {
            $row[$key] = stripslashes($value);
         }
      }
      return $row;
   }

   public function insert_sql($sql) {

      // Inserts data in the database via SQL query.
      // Returns the id of the insert or true if there is not auto_increment
      // column in the table.  Returns false if there is an error.

      $this->last_query = $sql;

      $r = $this->db_link->query($sql);
      if (!$r) {
         $this->last_error = $this->db_link->error;
         return false;
      }

      $id = $this->db_link->insert_id;
      if ($id == 0) return true;
      else return $id;
   }

   public function update_sql($sql) {

      // Updates data in the database via SQL query.
      // Returns the number or row affected or true if no rows needed the update.
      // Returns false if there is an error.

      $this->last_query = $sql;

      $r = $this->db_link->query($sql);
      if (!$r) {
         $this->last_error = $this->db_link->error;
         return false;
      }

      $rows = $this->db_link->affected_rows;
      if ($rows == 0) return true;  // no rows were updated
      else return $rows;

   }

   public function insert_array($table, $data) {

      // Inserts a row into the database from key->value pairs in an array. The
      // array passed in $data must have keys for the table's columns. You can
      // not use any MySQL functions with string and date types with this
      // function.  You must use insert_sql for that purpose.
      // Returns the id of the insert or true if there is not auto_increment
      // column in the table.  Returns false if there is an error.

      if (empty($data)) {
         $this->last_error = "You must pass an array to the insert_array() function.";
         return false;
      }

      $cols = '(';
      $values = '(';

      foreach ($data as $key=>$value) {     // iterate values to input

         $cols .= "$key,";

         $col_type = $this->get_column_type($table, $key);  // get column type
         if (!$col_type) return false;  // error!

         // determine if we need to encase the value in single quotes
         if (is_null($value)) {
            $values .= "NULL,";
         }
         elseif (substr_count(MYSQL_TYPES_NUMERIC, "$col_type ")) {
            $values .= "$value,";
         }
         elseif (substr_count(MYSQL_TYPES_DATE, "$col_type ")) {
            $value = $this->sql_date_format($value, $col_type); // format date
            $values .= "'$value',";
         }
         elseif (substr_count(MYSQL_TYPES_STRING, "$col_type ")) {
            if ($this->auto_slashes) $value = addslashes($value);
            $values .= "'$value',";
         }
      }
      $cols = rtrim($cols, ',').')';
      $values = rtrim($values, ',').')';

      // insert values
      $sql = "INSERT INTO $table $cols VALUES $values";
      return $this->insert_sql($sql);

   }

   public function update_array($table, $data, $condition) {

      // Updates a row into the database from key->value pairs in an array. The
      // array passed in $data must have keys for the table's columns. You can
      // not use any MySQL functions with string and date types with this
      // function.  You must use insert_sql for that purpose.
      // $condition is basically a WHERE claus (without the WHERE). For example,
      // "column=value AND column2='another value'" would be a condition.
      // Returns the number or row affected or true if no rows needed the update.
      // Returns false if there is an error.

      if (empty($data)) {
         $this->last_error = "You must pass an array to the update_array() function.";
         return false;
      }

      $sql = "UPDATE $table SET";
      foreach ($data as $key=>$value) {     // iterate values to input

         $sql .= " $key=";

         $col_type = $this->get_column_type($table, $key);  // get column type
         if (!$col_type) return false;  // error!

         // determine if we need to encase the value in single quotes
         if (is_null($value)) {
            $sql .= "NULL,";
         }
         elseif (substr_count(MYSQL_TYPES_NUMERIC, "$col_type ")) {
            $sql .= "$value,";
         }
         elseif (substr_count(MYSQL_TYPES_DATE, "$col_type ")) {
            $value = $this->sql_date_format($value, $col_type); // format date
            $sql .= "'$value',";
         }
         elseif (substr_count(MYSQL_TYPES_STRING, "$col_type ")) {
            if ($this->auto_slashes) $value = addslashes($value);
            $sql .= "'$value',";
         }

      }
      $sql = rtrim($sql, ','); // strip off last "extra" comma
      if (!empty($condition)) $sql .= " WHERE $condition";

      // insert values
      return $this->update_sql($sql);
   }

   public function execute_file ($file) {

      // executes the SQL commands from an external file.

      if (!file_exists($file)) {
         $this->last_error = "The file $file does not exist.";
         return false;
      }
      $str = file_get_contents($file);
      if (!$str) {
         $this->last_error = "Unable to read the contents of $file.";
         return false;
      }

      $this->last_query = $str;

      // split all the query's into an array
      $sql = explode(';', $str);
      foreach ($sql as $query) {
         if (!empty($query)) {
            $r = $this->db_link->query($query);

            if (!$r) {
               $this->last_error = $this->db_link->error;
               return false;
            }
         }
      }
      return true;

   }

   public function get_column_type($table, $column) {

      // Gets information about a particular column using the $this->get_table_types
      // function.  Returns an array with the field info or false if there is
      // an error.

      $table = strtolower($table);
      $column = strtolower($column);
      if (!isset($this->tables[$table])) {
         $this->get_table_types($table);
      }
      if (!isset($this->tables[$table][$column])) {
         $this->last_error = "Unable to get column information on $table.$column.";
         return false;
      }
      return $this->tables[$table][$column];
   }

   public function get_table_types($table)
   {
        $table = strtolower($table);
        if (isset($this->tables[$table]) && is_array($this->tables[$table]) && !empty($this->tables[$table]))
            return;

        $result = $this->db_link->query("SELECT * FROM $table limit 1");
        if (!$result) {
           $this->last_error = $this->db_link->error;
           return false;
        }

        $this->tables[$table] = array();
        $fields = $result->field_count;
        $tableFields  = $result->fetch_fields();
        foreach ($tableFields as $val) {
            $type  = $val->type;
            $name  = strtolower($val->name);
            $this->tables[$table][$name]=$type;
        }
        $this->free_result($result);
   }

   public function sql_date_format($value) {

      // Returns the date in a format for input into the database.  You can pass
      // this function a timestamp value such as time() or a string value
      // such as '04/14/2003 5:13 AM'.

      if (gettype($value) == 'string') $value = strtotime($value);
      return date('Y-m-d H:i:s', $value);

   }

   public function print_last_error($show_query=true) {

      // Prints the last error to the screen in a nicely formatted error message.
      // If $show_query is true, then the last query that was executed will
      // be displayed aswell.

      ?>
      <div style="border: 1px solid red; font-size: 9pt; font-family: monospace; color: red; padding: .5em; margin: 8px; background-color: #FFE2E2">
         <span style="font-weight: bold">db.class.php Error:</span><br><?php echo $this->last_error; ?>
      </div>
      <?php
      if ($show_query && (!empty($this->last_query))) {
      $this->print_last_query();
      }

   }

   public function print_last_query() {

      // Prints the last query that was executed to the screen in a nicely formatted
      // box.

      ?>
      <div style="border: 1px solid blue; font-size: 9pt; font-family: monospace; color: blue; padding: .5em; margin: 8px; background-color: #E6E5FF">
         <span style="font-weight: bold">Last SQL Query:</span><br><?php echo str_replace("\n", '<br>', $this->last_query); ?>
      </div>
      <?php
   }

   public function dump_query($sql) {

      // Useful during development for debugging  purposes.  Simple dumps a
      // query to the screen in a table.
      $this->resetTime();
      $r = $this->select($sql);
      if (!$r) {
          echo "$sql<br />";
          echo $this->last_error."<br />";
          return false;
      }
      echo "<div style=\"border: 1px solid blue; font-family: sans-serif; margin: 8px;\">\n";
      echo "$sql<br />";
      if (is_resource($r))
      {
        echo "<table cellpadding=\"3\" cellspacing=\"1\" border=\"0\" width=\"100%\">\n";

        $i = 0;
        while ($row = $r->fetch_assoc()) {
           if ($i == 0) {
              echo "<tr><td colspan=\"".sizeof($row)."\"><span style=\"font-face: monospace; font-size: 9pt;\">$sql</span></td></tr>\n";
              echo "<tr>\n";
              foreach ($row as $col => $value) {
                 echo "<td bgcolor=\"#E6E5FF\"><span style=\"font-face: sans-serif; font-size: 9pt; font-weight: bold;\">$col</span></td>\n";
              }
              echo "</tr>\n";
           }
           $i++;
           if ($i % 2 == 0) $bg = '#E3E3E3';
           else $bg = '#F3F3F3';
           echo "<tr>\n";
           foreach ($row as $value) {
              echo "<td bgcolor=\"$bg\"><span style=\"font-face: sans-serif; font-size: 9pt;\">$value</span></td>\n";
           }
           echo "</tr>\n";
        }
        echo "</table>\n";
      }
      echo "</div>\n";
      $this->printTime('Total time SQL');
   }

}

?>