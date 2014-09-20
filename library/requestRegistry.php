<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   requestRegistry.php
 *    @name:         RequestRegistry
 *    @namespace:    library
 *    @abstract:     Concrete registry class, stores variables in an array
 *    @uses:         Registry
 */
namespace library;

class RequestRegistry extends  Registry
{
    private static $_Registry;
    private $properties = array();

    /**
     * Get the config static object
     *
     * @return self
     */
    public static function getInstance($basePath=null)
    {
        if (!isset(self::$_Registry)) {
        	self::$_Registry = parent::getInstance($basePath);
        }
        return self::$_Registry;
    }

    /**
     * Magic Isset
     *
     * @param string $property Property name
     *
     * @return boolean
     */
    final public function __isset($property)
    {
       	if (isset($this->properties[$property])) {
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
    final public function __unset($property)
    {
        if (isset($this->properties[$property])) {
        	unset($this->properties[$property]);
       	}
	}

    /**
     * Get Property
     *
     * @param string $property Property name
     *
     * @return mixed
     */
    final protected function __getProperty($property)
    {
        $value = null;

        $methodName = '__getVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            $value = call_user_func([$this, $methodName]);
        } else {
        	if (isset($this->properties[$property])) {
                return $this->properties[$property];
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
    final protected function __setProperty($property, $value)
    {
        $methodName = '__setVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            call_user_func([$this, $methodName], $value);
        } else {
            $this->properties[$property] = $value;
        }
    }

    protected function __getValContext() {
        return $this->context;
    }

    protected function __getValRequest() {
        return $this->request;
    }

    protected function __getValConfig() {
        return $this->config;
    }

}