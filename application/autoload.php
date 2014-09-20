<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014 by
 *    @version:      2.0
 *    @filesource:   autoload.php
 *    @name:         Autoload
 *    @namespace:    application
 *    @abstract:     clases autoload object
 *    @uses:
 */
namespace application;

Class Autoload
{
    public $appPath;
    public $basePath;
    public $libraryPath;

    /**
     * constructor : should be protected
     * @return void
     */
    protected function __construct()
    {
        // find base dir and url
        if (!defined ('APP_PATH')) {
            $this->appPath = pathinfo(__FILE__,PATHINFO_DIRNAME).DIRECTORY_SEPARATOR;
        }
        else {
            $this->appPath = APP_PATH;
        }

        if (!defined ('BASE_PATH')) {
            $pos        = strrpos($this->appPath,'application');
            $this->basePath     = $pos?substr($this->appPath,0,$pos):$this->appPath;
        }
        else {
            $this->basePath = BASE_PATH;
        }

        if (!defined ('LIBRARY_PATH')) {
            $this->libraryPath = $this->basePath.'library'.DIRECTORY_SEPARATOR;
        }
        else {
            $this->libraryPath = LIBRARY_PATH;
        }

        spl_autoload_register([$this,'replaceUnderscores']);
        spl_autoload_register([$this,'nameSpaceAutoload']);
    }

    /**
     *
     * public function to create object
     */
    public static function getInstance()
    {
        return new self();
    }

    /**
     * Magic class load : load the given class  replacing the underscores with directory separators
     * to find the Class file
     *
     * @param string $class_name
     */
    function replaceUnderscores($class_name)
    {
        $parts = explode('_', $class_name);
        $class_name = array_pop($parts);
        if (!empty($parts)) {
            $parts[] = '';
        }
        $class_file = str_replace($class_name[0], strtolower($class_name[0]), $class_name).'.php';

        /* Search for the file in the Library folder */
        $class_file_name = $this->libraryPath.str_replace('\\',DIRECTORY_SEPARATOR,strtolower(implode(DIRECTORY_SEPARATOR,$parts)).$class_file);

        /* If not found Search for the file in the model folder */
        if (!is_file($class_file_name)) {
            /* Search for the file in the application folder inside the model folder*/
            $class_file_name = $this->appPath.strtolower(implode(DIRECTORY_SEPARATOR,$parts)).'models'.DIRECTORY_SEPARATOR.$class_file;
        }

        if (is_file($class_file_name)) {
            require_once ($class_file_name);
        }

    }

    /**
     * Magic class load : load the given path replacing the \ with directory separators
     * to find the Class file
     *
     * @param string $path
     */
    function nameSpaceAutoload($path)
    {
        if (preg_match('/\\\\/',$path)) {
           $path = str_replace('\\', DIRECTORY_SEPARATOR, $path);
        }
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $class_name = array_pop($parts);
        if (!empty($parts)) {
            $parts[] = '';
        }
        $class_file     = str_replace($class_name[0], strtolower($class_name[0]), $class_name).'.php';
        $class_file_name= str_replace('\\',DIRECTORY_SEPARATOR,strtolower(implode(DIRECTORY_SEPARATOR,$parts)).$class_file);
        $class_file_path= (empty($parts)?$this->libraryPath:$this->basePath).$class_file_name;

        if (file_exists($class_file_path)) {
            require_once ($class_file_path);
        }
        elseif(count($parts) > 1) {
            $last = array_pop($parts);
            array_splice($parts,1,0,['default']);
            $class_file_name = $this->basePath.str_replace('\\',DIRECTORY_SEPARATOR,strtolower(implode(DIRECTORY_SEPARATOR,$parts)).DIRECTORY_SEPARATOR.$class_file);

            if (file_exists($class_file_name)) {
               require_once ($class_file_name);
            }
        }
    }

}

?>