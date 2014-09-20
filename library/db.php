<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   context.php
 *    @name:         Context
 *    @namespace:    library
 *    @abstract:     well the DB! Implements the abstract factory design pattern to create the database
 *                   objects
 *    @uses:         databases\DbMysql
 */
namespace library;
use \library\databases\DbMysql;
use \library\interfaces\Database;

Abstract Class Db implements Database {

    private static $_dbType;

    /**
     * Get the db static object
     *
     * @return self
     */
    public static function getInstance($host='', $db='',$type='')
    {
        self::$_dbType = !empty($type)?strtolower(trim($type)):(isset(self::$_dbType)?self::$_dbType:'mysql');

        switch (self::$_dbType) {
            case 'mysql': return DbMysql::getInstance($host, $db);
                          break;
            default     : return DbMysql::getInstance($host, $db);
                          break;
        }
    }

}

?>