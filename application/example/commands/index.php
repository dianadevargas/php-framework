<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   index.php
 *    @name:         example
 *    @namespace:    application
 *    @abstract:     concrete command class. Example of use of command in an aplication
 *    				 Tool class and esay way to test small code and display database data
 *    @uses:         \library\Command, \library\Db
 */
namespace application\example;
use \library\Command;
use \library\Db;

class Example extends  Command
{
    private $splitReg  = '/# Match position between camelCase "words".
                            (?<=[a-z]|\s)  # Position is after a lowercase,
                            (?=[A-Z])   # and before an uppercase letter.
                            /x';
    private $form = array(
                        'title' => '',
                        'action' => '',
                        'method' => 'post',
                        'name'  => 'test-form',
                        'input' => array(),
                        'output' => '',
                    );

    /*
     *  When call from the URL
     */
    public function index ()
    {
        $this->viewHTML('menu.php');
    }

    public function showDatabase ()
    {
        // Connect to database
        $db = Db::getInstance();
    	if (!$db->connect($this->_registry->config->dbserver, $this->_registry->config->dbuser, $this->_registry->config->dbpass, $this->_registry->config->dbname, true))
        {
            $db->print_last_error(false);
            $db->printMe();
        }
        $tables = array();
        if (isset($this->_registry->request->request['db']))
        {
            $db->select_db($this->_registry->request->request['db']);
        }
        $aResults = $db->select("SHOW TABLES");
        while ($aRow = $db->get_row($aResults, 'MYSQL_ASSOC')) {
            $tableName = $aRow['Tables_in_'.$db->selected_db()];
            $tables[$tableName] = array('fields' => array(), 'rows' => 0);
            $bResults = $db->select("DESCRIBE $tableName");
            while ($bRow = $db->get_row($bResults, 'MYSQL_ASSOC')) {
                $tables[$tableName]['fields'][] = $bRow;
            }
            $cResults = $db->select_one("select count(*) num from $tableName");
            $tables[$tableName]['rows'] = $cResults;
        }
        $this->_registry->title = 'Database Structure';
        $this->_registry->tables = $tables;
        $this->viewHTML('tables.php');
    }

    public function showRows ()
    {
        // Connect to database
        $db = Db::getInstance();
        if (!$db->connect($this->_registry->config->dbserver, $this->_registry->config->dbuser, $this->_registry->config->dbpass, $this->_registry->config->dbname, true))
        {
            $db->print_last_error(false);
            $db->printMe();
        }
        if (isset($this->_registry->request->args[0]))
        {
            $tables = array();
            $db = Db::getInstance();
            $tableName = $this->_registry->request->args[0];
            $where = isset($this->_registry->request->request['where'])?" where ".$this->_registry->request->request['where']:'';
            $tables[$tableName] = array('fields' => array(), 'rows' => 0);
            $bResults = $db->select("select * from  $tableName $where limit 200");
            while ($bRow = $db->get_row($bResults, 'MYSQL_ASSOC')) {
                $tables[$tableName]['fields'][] = $bRow;
            }
            $cResults = $db->select_one("select count(*) num from $tableName");
            $tables[$tableName]['rows'] = $cResults;
        }
        $this->_registry->title = 'Database Structure';
        $this->_registry->tables = $tables;
        $this->viewHTML('tables.php');
    }

    public function splitReg() {
    	$form = $this->form;
        $form['title'] = 'Split Camel Case String';
        $form['action'] = 'splitReg';
        $text = isset($this->_registry->request->request['text'])?$this->_registry->request->request['text']:'';
        $form['input'] = array('name' => 'text', 'label' => 'Text', 'type' => 'text',  'value' => $text);
        $form['output'] = preg_split($this->splitReg, $text);
        $this->_registry->form = $form;
        $this->viewHTML('form.php');
    }

    public function displayUnix ()
    {
    	$form = $this->form;
    	$form['title'] = 'Convert Date and Time to Unix time';
        $form['action'] = 'displayUnix';
        $time = isset($this->_registry->request->request['date'])?$this->_registry->request->request['date']:'now';
        $form['input'] = array('name' => 'date', 'label' => 'Date (YYYY-MM-DD hh:mm:ss)', 'type' => 'text',  'value'  => $time);
        $form['output'] = strtotime($time);
        $this->_registry->form = $form;
        $this->viewHTML('form.php');
    }

    public function displayDate ()
    {
    	$form = $this->form;
    	$form['title'] = 'Convert Unix time to Date and Time';
        $form['action'] = 'displayDate';
        $date = isset($this->_registry->request->request['unix'])?(int)$this->_registry->request->request['unix']:strtotime('now');
        $form['input'] = array('name' => 'unix', 'label' => 'Unix time', 'type' => 'text',  'value'  => $date);
        $form['output'] = date('Y-m-d H:i:s',$date);
        $this->_registry->form = $form;
        $this->viewHTML('form.php');
    }

    public function convertJson ()
    {
    	$form = $this->form;
    	$form['title'] = 'Convert Json to Array';
        $form['action'] = 'convertJson';
        $json = isset($this->_registry->request->request['json'])?$this->_registry->request->request['json']:'';
        $form['input'] = array('name' => 'json', 'label' => 'Json', 'type' => 'textarea',  'value'  => $json);
        $form['output'] = json_decode($json,true);
        $this->_registry->form = $form;
        $this->viewHTML('form.php');
    }


    public function unserializeText ()
    {
    	$form = $this->form;
    	$form['title'] = 'Unserialize Text';
        $form['action'] = 'unserializeText';
        $text = isset($this->_registry->request->request['text'])?$this->_registry->request->request['text']:'';
        $form['input'] = array('name' => 'text', 'label' => 'Serialized text', 'type' => 'textarea',  'value'  => $text);
        $form['output'] = @unserialize($text);
        $this->_registry->form = $form;
        $this->viewHTML('form.php');
    }

    public function showRequest ()
    {
        $tables = array();
        $tableName = 'Request object vs PHP vars';
        $tables[$tableName] = array('fields' => array(), 'rows' => 1);
        $tables[$tableName]['fields'][] =
                            ['Request:' => '<pre>'.print_r($this->_registry->request,true).'</pre>',
                        	'PHP Vars:' => '<pre>'.
                        	'$_SERVER[REQUEST_METHOD]:'.$_SERVER['REQUEST_METHOD'].PHP_EOL.
                            '$_SERVER[REQUEST_URI]:'.$_SERVER["REQUEST_URI"].PHP_EOL.
                            '$_SERVER[QUERY_STRING]:'.$_SERVER["QUERY_STRING"].PHP_EOL.
                        	'$_REQUEST:'.print_r($_REQUEST,true).
                            '$_POST:'.print_r($_POST,true).
                            '$_GET:'.print_r($_GET,true).
                            '$_COOKIE:'.print_r($_COOKIE,true)];
        $this->_registry->title = 'Test the request object';
        $this->_registry->tables = $tables;
        $this->viewHTML('tables.php');
    }

}

/*
 *  Run if called with bootstrap
 */
$cmd = Example::getInstance();

?>
