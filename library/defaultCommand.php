<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   defaultCommand.php
 *    @name:         DefaultCommand
 *    @namespace:    library
 *    @abstract:     concrete command class. Implements the command design pattern
 *    @uses:         View, Registry
 */
namespace library;

class DefaultCommand extends  Command
{

    public function index ()
    {
        $this->viewHTML('index.html');
    }

}
?>