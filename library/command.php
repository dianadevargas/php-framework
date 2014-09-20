<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   command.php
 *    @name:         Command
 *    @namespace:    library
 *    @abstract:     Abstract command class. Implements the command design pattern
 *    @uses:         View, Registry
 */
namespace library;

abstract class Command extends Model
{
    protected $_class;
    protected $_View        = null;
    protected $_registry    = null;
    protected $_commands    = array();
    protected $_lastCmds    = array();
    protected $modulePath   = null;

    /**
     * constructor : adds the view object
     * @return void
     */
    final protected function __construct()
    {
        $this->_class = new \ReflectionClass(get_class($this));
    }

    /**
    * Abstarct functions
    *
    */
    abstract public function index();

    /**
    * function to call methods by URL
    *
    * @return mixed
    */
    final protected function setCommand ($method_name = '',$checkPublic = false)
    {

        if (!empty($method_name) && $this->_class->hasMethod($method_name) && (!$checkPublic || $this->_class->getMethod($method_name)->isPublic()))
        {
            array_push($this->_commands, $method_name);
        }
        else
        {
            $this->_registry->setMessages("Command '{$method_name}' not found");

            // Dont call index again avoid loops
            $callers=debug_backtrace(false);
            foreach($callers as $call) {
                if (isset($call['class']) && ($call['class'] == $this->_class->getName()) && ($call['function'] == 'index'))
                {
                    $this->viewHTML('404.html');
                    return false;
                }
            }
            array_push($this->_commands, 'index');
        }

    }


    /**
    * function to store the last command in the array
    *
    * @return void
    */
    protected function setLastCommand ($method_name,$result)
    {
        array_push($this->_lastCmds , array('cmd' => $method_name, 'result' => $result));
    }


    /**
    * function to get the last commands
    *
    * @return void
    */
    public function getLastCommand ()
    {
        return $this->_lastCmds;
    }


    /**
     * Execute the command called
     * @param Registry $registry
     */
    public function execute (Registry $registry, View $view)
    {
        $this->_registry= $registry;
        $this->_View    = $view;
        $params = array();

        // Get the command from the URL
        if (isset($this->_registry->request->basename)) {
            $this->setCommand($this->_registry->request->basename,true);
        }
        else {
            $this->setCommand();
        }

        // Execute the commands
        while (!empty($this->_commands)) {
            $cmd      = array_shift($this->_commands);
            $result   = call_user_func_array(array($this, $cmd),$params);
            $this->setLastCommand($cmd,$result);
        }
    }

    /**
    * functions to manage views
    *
    */
    final private function view ($view,$type='HTML',$code=200,$filename='')
    {
        if (isset($this->_View) && is_object($this->_View))
        {
            $this->_View->displayView($view,$type,$this->modulePath,$code,$filename);
        }
    }

    final protected function viewHTML ($view)
    {
        $this->view($view,'html');
    }

    final protected function viewJSON ($view)
    {
        $this->view($view,'json');
    }

    final protected function viewFILE ($view,$filename)
    {
        $this->view($view,'file',null,$filename);
    }

    final protected function viewDebug ($view)
    {
        $this->view($view,'debug');
    }

    final protected function view404 ($view)
    {
        $this->view($view,'html',404);
    }

}