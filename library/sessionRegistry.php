<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   sessionRegistry.php
 *    @name:         SessionRegistry
 *    @namespace:    library
 *    @abstract:     Concrete registry class, stores variables in the SESSION
 *    @uses:         Registry
 */
namespace library;

class SessionRegistry extends  Registry
{
    private static $_Registry;

    /**
     * constructor : reads the config file an set up the variables
     *
     * @param string $file file name
     * @param string $enviroment name of enviroment to read variables
     *
     * @return void
     */
    protected function __construct($basePath=null)
    {
    	session_cache_expire(3600);
		if (!session_id())
		    session_start();

		if(isset($_SESSION['timeout_idle']) && $_SESSION['timeout_idle'] < time())
		{
		    session_destroy();
		    session_regenerate_id();
		}
		$_SESSION['timeout_idle'] = time() + session_cache_expire();
		parent::__construct($basePath);
    }

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
       	if (isset($_SESSION[__CLASS__][$property])) {
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
       	if (isset($_SESSION[__CLASS__][$property])) {
        	unset($_SESSION[__CLASS__][$property]);
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
        	if (isset($_SESSION[__CLASS__][$property])) {
                $value = $_SESSION[__CLASS__][$property];
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
        	$_SESSION[__CLASS__][$property] = $value;
        }
    }

}