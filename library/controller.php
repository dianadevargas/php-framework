<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   controller.php
 *    @name:         Controller
 *    @namespace:    library
 *    @abstract:     this is the Chief! Implements the front controller design pattern
 *    @uses:         SessionRegistry, RequestRegistry, CommandHandler, View
 */
namespace library;

class Controller
{

    public static function run($basePath)
    {
        $instance = new self();
        $instance->init($basePath);
        $instance->handleRequest();
    }

    public function  init($basePath)
    {
        // Initialize Registry: can choose for session or request
        $registry     = SessionRegistry::getInstance($basePath);
        //$registry    = RequestRegistry::getInstance($basePath);

        // Set error reporting acordinly with enviroment
        if ($registry->context->enviroment == 'TESTING') {
            error_reporting(E_ALL);
        } elseif (($registry->context->enviroment == 'PRODUCTION') && $registry->config->debug) { // Debug in production if required
            error_reporting(E_ALL);
        } else {    // Production
            error_reporting(0);
        }
    }

    public function handleRequest()
    {
        $registry = SessionRegistry::getInstance();
        $cmdHnd = CommandHandler::getInstance();
        $cmd    = $cmdHnd->getCommand($registry);
        $cmd->execute($registry,View::getInstance($registry));
    }

}