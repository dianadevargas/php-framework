<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   config.php
 *    @name:         Config
 *    @namespace:    library
 *    @abstract:     here is the lawyer! Implements the singleton design pattern an stores the
 *                   configuration data in $options array
 *    @uses:         \library\traits\UtilitiesHelper, \library\traits\ExceptionHelper
 */
namespace library;
use \library\traits\UtilitiesHelper;
use \library\traits\ExceptionHelper;

class Config extends  Model
{
    use UtilitiesHelper;

    private static $_Config;
    private $_config;
    private $options = array();
    private $context;

    /**
     * constructor : reads the config file an set up the variables
     *
     * @param string $file file name
     * @param string $enviroment name of enviroment to read variables
     *
     * @return void
     */
    protected function __construct(Context $context)
    {
        $this->context = $context;
        $file = $this->context->appPath.'default/config/config.ini';
        ExceptionHelper::ensure(file_exists($file),'Default config file not found');
        $this->options = self::getConfigArray($file,$this->context->enviroment,$this->context->appPath,$this->context->basePath,$this->context->publicURL);
    }

    /**
     * Get the config static object
     *
     * @return self
     */
    public static function getInstance(Context $context=null)
    {
        if (!isset(self::$_Config)) {
            $class = get_called_class();
            self::$_Config = new $class($context);
        }
        return self::$_Config;
    }

    /**
     * reads the array of config variables and return the variables of one enviroment in an array
     *
     * @param array $config
     * @param integer $level
     * @param string $appPath
     * @param string $basePath
     * @param string $publicURL
     *
     * @return array
     */
    static public function getConfigVariables($config,$level,$enviroment,$appPath='{appPath}',$basePath='{basePath}',$publicURL='{HOME}')
    {
        $options = array();
        $env = false;
        $search = ['{appPath}','{basePath}','{HOME}'];
        $replace= [$appPath,$basePath,$publicURL];

        foreach($config as $var => $value)
        {
            if (is_array($value)) {
                if ($level == 0) {
                    if ($var == $enviroment) {
                        $options = array_merge($options,self::getConfigVariables($value,$level+1,$enviroment,$appPath,$basePath,$publicURL));
                        $env = true;
                    } elseif (!$env) {
                        $options[$var] = self::replaceArray($search, $replace, $value);
                    }
                } else {
                    $options[$var] = self::replaceArray($search, $replace, $value);
                }
            } else {
                $options[$var] = self::replaceArray($search, $replace, $value);
            }
        }
        return $options;
    }

    /**
     * load a configfile into an array
     * @param string $file
     * @return array
     */
    public function loadConfig($file)
    {
        $val = self::getConfigArray($file,$this->context->enviroment,$this->context->appPath,$this->context->basePath,$this->context->publicURL);
        if  ($val) {
            array_replace_recursive($this->options,$val);
        }
    }

    /**
     *
     * @param unknown $file
     * @param string $appPath
     * @param string $basePath
     * @param string $publicURL
     * @return Ambigous <multitype:, multitype:NULL >|boolean
     */
    static public function getConfigArray($file,$enviroment,$appPath='{appPath}',$basePath='{basePath}',$publicURL='{HOME}')
    {
        if  (is_file($file)) {
            $_config = parse_ini_file($file,1);
            return self::getConfigVariables($_config,0,$enviroment,$appPath,$basePath,$publicURL);
        }
        return false;
    }

    /**
     * Magic Isset
     *
     * @param string $key Property name
     *
     * @return boolean
     */
    final public function __isset($key)
    {
       if (isset($this->options[$key])) {
           return true;
       }
       return false;
    }

    /**
     * Magic Unset
     *
     * @param string $key Property name
     *
     * @return boolean
     */
    final public function __unset($key)
    {
        if (isset($this->options[$key])) {
           unset ($this->options[$key]);
        }
    }

    /**
     * Get Property
     *
     * @param string $key Property name
     *
     * @return mixed
     */
    final protected function __getProperty($key)
    {
        $value = null;

        $methodName = '__getVal' . ucwords($key);
        if(method_exists($this, $methodName)) {
            $value = call_user_func([$this, $methodName]);
        } else {
            if (isset($this->options[$key])) {
                return $this->options[$key];
            }
        }
        return $value;
    }

    /**
     * Set Property
     *
     * @param string $key Property name
     * @param mixed $value Property value
     *
     * @return self
     */
    final protected function __setProperty($key, $value)
    {
        $methodName = '__setVal' . ucwords($key);
        if(method_exists($this, $methodName)) {
            call_user_func([$this, $methodName], $value);
        } else {
            $this->options[$key] = $value;
        }
    }

}