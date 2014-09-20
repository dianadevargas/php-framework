<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   commandHandler.php
 *    @name:         CommandHandler
 *    @namespace:    library
 *    @abstract:     Handle the command and execute it. Implements the abstract factory design pattern to create the commands
 *    @uses:
 */
namespace library;

class CommandHandler extends Model
{
    private static $_Command;
    private static $_base_cmd		= null;
    private static $_default_cmd	= null;

    /**
     * constructor : should be protected
     * @return void
     */
    protected function __construct()
    {
    	self::$_default_cmd = DefaultCommand::getInstance();
    	self::$_base_cmd = new \ReflectionClass('library\Command');
    }

    /**
     * Get the Bootstrap static object
     *
     * @return self
     */
    public static function getInstance()
    {
        if (!isset(self::$_Command)) {
        	self::$_Command = parent::getInstance();
        }
        return self::$_Command;
    }


    /**
     * Get the context path
     *
     * @return self
     */
    public function getCommand(Registry $registry)
    {

        /* find the directory where the context sits */
        $parts = $registry->request->path;
        if (!empty($parts))
            $parts[] = '';
        $file_path = implode(DIRECTORY_SEPARATOR, $parts);
        $file_name = $registry->request->basename.'.php';

        /* Search for the folder in the application folder */
        $file_dir = $registry->context->appPath.strtolower($file_path);

        if (!is_dir($file_dir) && (count($parts) > 2)) {
            /* if no found try to find one level down */
            $file_name = $parts[count($parts) - 2].'.php';
            unset($parts[count($parts) - 2]);
            $file_path = implode(DIRECTORY_SEPARATOR, $parts);
            $file_dir = $registry->context->appPath.strtolower($file_path);
        }

        /* Load the config file for this module */
        if (!is_dir($file_dir) || empty($file_path)) { /* we didn't find the module. use the default module. the config is already up */
            $file_dir = $registry->context->appPath.'default'.DIRECTORY_SEPARATOR;
        }
        else {
            $registry->loadConfig($file_dir.'config'.DIRECTORY_SEPARATOR.'config.ini');
        }

        /* Find the directory for the commands in this module */
        $full_file_name = $file_dir.'commands'.DIRECTORY_SEPARATOR.$file_name;
        if (!is_file($full_file_name)) {
            $full_file_name = $file_dir.'commands'.DIRECTORY_SEPARATOR.'index.php';
            if (!is_file($full_file_name)) {
                $file_dir       = $registry->context->appPath.'default'.DIRECTORY_SEPARATOR;
                $full_file_name = $registry->context->appPath.'default'.DIRECTORY_SEPARATOR.'commands'.DIRECTORY_SEPARATOR.'index.php';
            }
        }
        include $full_file_name;

        // Check that the command exist in the file
        if (!isset($cmd)) {
        	$registry->setMessages('CMD variable not found');
        	$cmd = clone self::$_default_cmd;
        	$file_dir = $registry->context->appPath.'default'.DIRECTORY_SEPARATOR;
        }
        else {
	        $reflex = new \ReflectionClass(get_class($cmd));
	        if (!$reflex->isSubclassOf(self::$_base_cmd)) {
	        	$registry->setMessages('CMD not found');
	        	$cmd = clone self::$_default_cmd;
	        	$file_dir = $registry->context->appPath.'default'.DIRECTORY_SEPARATOR;
	        }
        }
        $cmd->modulePath = $file_dir;
        return $cmd;
    }

}