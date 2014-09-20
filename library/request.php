<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   request.php
 *    @name:         Request
 *    @namespace:    library
 *    @abstract:     Implements the singleton design pattern an stores the requests variables and commands send by the user
 *    @uses:
 */
namespace library;

class Request extends  Model
{
    protected $path;
    protected $basename;
    protected $query;
    protected $request;
    protected $post;
    protected $get;
    protected $args;
    protected $cookies;
    protected $method;
    private static $_Request;


    /**
     * constructor : reads the $_SERVER variables and set up the server http
     * requires the constant BASE_PATH base directory where the app lives
     *
     * @return void
     */
    protected function __construct()
    {
        $this->__init();
        $this->__getCommand();
    }

    /**
     * Get the context static object
     *
     * @return self
     */
    public static function getInstance()
    {
        if (!isset(self::$_Request)) {
        	self::$_Request = parent::getInstance();
        }
        return self::$_Request;
    }

    /**
     *  Load all the control variables
     */
    private  function __init()
    {
    	// Get the get, post and request variables and clean them
    	if (isset($_SERVER['REQUEST_METHOD'])) {
    	    $this->method = $_SERVER['REQUEST_METHOD'];
	        $this->request = array();
	        foreach($_REQUEST as $key => $val) {
	            $key = strtolower($key);
	            $this->request[$key] = is_string($val)?urldecode($val):$val;
	        }

	        $this->post = array();
	        foreach($_POST as $key => $val) {
	            $key = strtolower($key);
	            $this->post[$key] = $val;
	        }

	        $this->get = array();
	        foreach($_GET as $key => $val) {
	            $key = strtolower($key);
	            $this->get[$key] = is_string($val)?urldecode($val):$val;
	        }

	        $get = array_keys($_GET);
	        foreach($get as $key => $val) {
	            $this->args[$key] = urldecode($val);
	        }

	        $this->cookies = array();
	        foreach($_COOKIE as $key => $val) {
	            $key = strtolower($key);
	            $this->cookies[$key] = $val;
	        }
    	}

		if(isset($_SERVER['argv'])){
		    foreach($_SERVER['argv'] as $arg){
		    	if (strpos($arg, '=')) {
		        	list($key, $val) = explode('=',$arg);
		        	$this->args[$key] = $val;
		        }
		    }
		}

    }

    /**
     * getCommand : get the route from the url to extract the module name and the url parts
     *
     * @return void
     */
    private function __getCommand()
    {
            /*** get the route from the url ***/
            $route  = (empty($_SERVER["REQUEST_URI"])) ? '' : $_SERVER["REQUEST_URI"];
            $query  = (empty($_SERVER["QUERY_STRING"])) ? '' : $_SERVER["QUERY_STRING"];

            $this->basename = 'index';
            $this->query 	= array();
            $this->path 	= array();
            if (!empty($route))
            {
                /*** get the parts of the route ***/
                $parse = @parse_url ($route);

                /* save the query variables in an array if not alredy loaded in the request variable*/
                $query = isset($parse['query'])?$parse['query']:$query;

                /* Include query variables just in case :) */
                if (!empty($query)) {
                 	parse_str($query,$this->query);
                }

               	/* get variables from query */
                if (empty($this->request) && !empty($this->query))
                {
                    foreach($this->query as $key => $val) {
                        $key = strtolower($key);
                        $this->request[$key] = is_string($val)?urldecode($val):$val;
                    }

                    /* load query variables as values with param_0 ... n  this allow calls as mysite.com/index?sendthis */
                    $get = array_keys($this->query);
                    foreach($get as $key => $val) {
                        $this->request["param_$key"] = (empty($this->request[strtolower($val)]))?urldecode($val):$this->request[strtolower($val)];
                    }
                }


                $parse = @parse_url ( trim(str_ireplace($query, '', $route)) );
                /* if the path is empty call index */
                if (preg_match('|/$|', $parse['path']) && !isset($parse['query']))
                {
                    $parse['path'] .= 'index';
                }

                $path_parts = isset($parse['path'])?pathinfo($parse['path']):array();
                $dir 		= (isset($path_parts['dirname']) && $path_parts['dirname'] != '.')?explode('/', str_replace('\\','/',$path_parts['dirname'])):array();
                $basename 	= isset($path_parts['filename'])?$path_parts['filename']:'';
                $this->basename = !empty($basename)?str_replace($basename[0], strtolower($basename[0]), $basename):$this->basename;

                /* clean the path from empty dir */
                foreach ($dir as $key => $val) {
                    if (empty($val)) {
                        unset($dir[$key]);
                    }
                }

                $this->path = (!empty($dir)?array_values($dir):array());
        }
    }
}