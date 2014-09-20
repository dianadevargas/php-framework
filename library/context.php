<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   context.php
 *    @name:         Context
 *    @namespace:    library
 *    @abstract:     here is the accountant! Implements the singleton design pattern an stores the
 *                   contextual variables
 *    @uses:         traits\ExceptionHelper
 */
namespace library;
use \library\traits\ExceptionHelper;

class Context extends  Model
{
    protected $server;
    protected $server_name;
    protected $system;
    protected $address;
    protected $self;
    protected $basePath;
    protected $baseURL;
    protected $publicPath;
    protected $publicURL;
    protected $appPath;
    protected $enviroment;
    private static $_Context;


    /**
     * constructor : reads the $_SERVER variables and set up the server http
     * requires the constant BASE_PATH base directory where the app lives
     *
     * @return void
     */
    protected function __construct($basePath=null)
    {
        ExceptionHelper::ensure(!empty($basePath) || defined(BASE_PATH),'Base path is not defined for the application');
        $this->__init(empty($basePath)?BASE_PATH:$basePath);
    }

    /**
     * Get the context static object
     *
     * @return self
     */
    public static function getInstance($basePath=null)
    {
        if (!isset(self::$_Context)) {
        	$class = get_called_class();
        	self::$_Context = new $class($basePath);
        }
        return self::$_Context;
    }

    /**
     *  Load all the control variables
     */
    private  function __init($basePath)
    {
        $this->server = 'http';
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") {
                $this->server .= "s";
        }
        $this->server .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $this->server_name = $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"];
        } else {
            $this->server_name = $_SERVER["SERVER_NAME"];
        }
        $this->server .= $this->server_name;
        $this->system = (stripos(strtolower(php_uname ("s")), 'windows') !== false)?'WINDOWS':'LINUX';
        $this->address = isset($_SERVER['SERVER_ADDR'])?$_SERVER['SERVER_ADDR']:'';

        // Get base the path and url
        $this->self         = $_SERVER['PHP_SELF'];
        $this->basePath     = $basePath;
        $this->publicPath   = defined ('PUBLIC_PATH')?PUBLIC_PATH:BASE_PATH.'public_html/';
        $this->baseURL      = self::getBaseURL();
        $this->publicURL    = defined ('PUBLIC_URL')?PUBLIC_URL:$this->server.$this->baseURL;
        $this->appPath      = defined('APP_PATH')?APP_PATH:BASE_PATH.'application/';
        $this->libraryPath  = defined('LIBRARY_PATH')?APP_PATH:BASE_PATH.'library/';

        // Testing Localhost
        if (($this->address == '127.0.0.1') || stripos($this->server_name, 'localhost') !== false) {
            $this->enviroment = 'TESTING';
        } else {    // Production
            $this->enviroment = 'PRODUCTION';
        }
    }

    public static function getBaseURL ()
    {
        if (defined('PUBLIC_URL')) {
            $baseURL  = self::cleanPath (PUBLIC_URL);
        }
        else {
            $baseURL  = self::cleanPath (pathinfo($_SERVER['PHP_SELF'],PATHINFO_DIRNAME));
            $baseURL .= ($baseURL != '/')?'/':'';
            $pos      = strrpos($baseURL,'application');
            $baseURL  = $pos?substr($baseURL,0,$pos):$baseURL;
        }
        return $baseURL;
    }

    public static function cleanPath ($str)
    {
        $str = str_replace('\\','/',$str);
        $parts = explode('/',$str);
        $clean = array();
        foreach ($parts as $val) {
            $val = trim($val);
            if (!empty($val)) {
                $clean[] =     $val;
            }
        }
        if (count($clean) > 0 && $clean[count($clean)-1] == '/') {
            array_pop($clean);
        }
        $str = implode('/',$clean);
        $str = !preg_match('|^[a-z,A-Z]+:|', $str)?'/'.$str:$str;
        return $str;
    }

}