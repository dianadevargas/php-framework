<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   model.php
 *    @name:         Model
 *    @namespace:    library
 *    @abstract:     here it goes the Model! Abstract class that implements all magic methods. Just handy
 *    @uses:         databases\DbMysql
 */
namespace library;

abstract class Model
{

    /**
     * constructor : should be protected
     * @return void
     */
    protected function __construct()
    {
    }

    /**
     *
     * public function to create object
     */
    public static function getInstance()
    {
    	$class = get_called_class();
    	return new $class();
    }

    /**
     * Magic Get
     *
     * @param string $property Property name
     *
     * @return mixed
     */
    final public function __get($property)
    {
        return $this->__getProperty($property);
    }

    /**
     * Magic Set
     *
     * @param string $property Property name
     * @param mixed $value New value
     *
     * @return self
     */
    final public function __set($property, $value)
    {
        return $this->__setProperty($property, $value);
    }

    /**
     * Magic Isset
     *
     * @param string $property Property name
     *
     * @return boolean
     */
    public function __isset($property)
    {
       if (($property[0] !== '_') && isset($this->$property)) {
           return true;
       }
       return false;
    }

    /**
     * Magic unset
     *
     * @param string $property Property name
     *
     * @return void
     */
    public function __unset($property)
    {
         if (($property[0] !== '_') && isset($this->$property)) {
        	unset($this->$property);
       	}
	}

    /**
     * Get Property
     *
     * @param string $property Property name
     *
     * @return mixed
     */
    protected function __getProperty($property)
    {
        $value = null;

        $methodName = '__getVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            $value = call_user_func(array($this, $methodName));
        } else {
            if (($property[0] !== '_') && isset($this->$property)) {
                $value = $this->$property;
            }
        }

        return $value;
    }

    /**
     * Set Property
     *
     * @param string $property Property name
     * @param mixed $value Property value
     *
     * @return self
     */
    protected function __setProperty($property, $value)
    {
        $methodName = '__setVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            return call_user_func(array($this, $methodName), $value);
        } else {
            if ($property[0] !== '_') {
                return ($this->$property = $value);
            }
        }
    }

    /**
     * Display the object
     *
     * @return void
     */
    public function printMe() {
        echo '<br />';
        echo '<pre>';
        print_r ($this);
        echo '</pre>';
    }
}