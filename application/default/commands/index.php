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
 *    @uses:         \application\models\Tweets, \application\models\TwitterApp, \library\Db
 */
namespace application;
use \library\Command;

class example extends  Command
{
    public function index ()
    {
        $this->viewHTML('menu.html');
    }

    public function helloWorld ()
    {
        $this->viewHTML('hello_world.html');
    }

    public function exampleOutputFile ()
    {
        $this->viewFILE('hello_world.html','myFile.html');
    }

    public function exampleOutputJson ()
    {
        $this->viewJSON('json.php');
    }

}
/*
 *  Run if called with bootstrap
 */
$cmd = example::getInstance();
?>