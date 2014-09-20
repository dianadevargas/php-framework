<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   registry.php
 *    @name:         Registry
 *    @namespace:    library
 *    @abstract:     Abstract class that implements the multiton and abstract factory design patterns
 *    @uses:         Context, Request, Config
 */
namespace library;

Abstract class Registry extends  Model
{
    protected $context;
    protected $request;
    protected $config;
    protected $_messages = array();

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
        $this->context  = Context::getInstance($basePath);
        $this->request  = Request::getInstance();
        $this->config   = Config::getInstance($this->context);
    }

    /**
     * Get the config static object
     *
     * @return self
     */
    public static function getInstance($basePath=null)
    {
        $class = get_called_class();
        return new $class($basePath);
    }

    /**
     *
     * @return \library\Request Object
     */
    static public function getRequest() {
        $inst = self::getInstance();
        return $inst->request;
    }

    /**
     *
     * @return \library\ontext Object
     */
    static public function getContext() {
        $inst = self::getInstance();
        return $inst->context;
    }

    /**
     *
     * @return \library\Config Object
     */
    static public function getConfig() {
        $inst = self::getInstance();
        return $inst->config;
    }

    static public function loadConfig($file) {
        $inst = self::getInstance();
        $inst->config->loadConfig($file);
    }

    /**
     *
     * @return message array
     */
    public function getMessages() {
        return $this->_messages;
    }

    /**
     *
     * insert a new message in the array
     */
    public function setMessages($msg) {
        array_push($this->_messages,$msg);
    }

    /**
     *
     * reset the message array
     */
    public function resetMessages($msg) {
   		$this->_messages = array();
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