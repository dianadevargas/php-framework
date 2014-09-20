<?php
/**    
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014 by 
 *    @version:      2.0                   
 *    @filesource:   botstrap.php
 *    @name:         
 *    @namespace:    application
 *    @abstract:     runs the show!
 *    @uses:         application\Autoload, \library\Controller           
 */
date_default_timezone_set('UTC');

require_once 'autoload.php';
$auto = application\Autoload::getInstance();

// Run Front Controller Object
\library\Controller::run($auto->basePath);
?>