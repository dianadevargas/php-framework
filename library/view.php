<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   view.php
 *    @name:         View
 *    @namespace:    library
 *    @abstract:     this is the PR! Uses the required file and send the headers according
 *                   to the required output type
 *    @uses:         Registry
 */
namespace library;

class View extends Model
{
    protected $_Path;
    protected $_registry;

    /**
     * constructor : adds the view object
     * @return void
     */
    protected function __construct(Registry $registry=null)
    {
        $this->_registry= $registry;
    }

    /**
     *
     * public function to create object
     */
    public static function getInstance(Registry $registry=null)
    {
        $class = get_called_class();
        return new $class($registry);
    }


    /**
     * constructor : init the class
     *
     * @return void
     */
    public function init($modulePath='')
    {
        if (!empty($modulePath))
        {
            $this->_Path =  $modulePath.'views'.DIRECTORY_SEPARATOR;
        }

        // Define globals for views
        if (!defined ('PUBLIC_URL')) {
            define('PUBLIC_URL', $this->_registry->context->publicURL);
        }

    }

    /**
     * Display a view
     *
     * @return void
     */
    public function displayView($view,$type,$modulePath,$code=200,$filename='')
    {
        $vars = $this->_registry;
        $this->init($modulePath);

        if (!headers_sent())
        {
            $code = (empty($code) || ($code < 100) || ($code > 505))?200:$code;
            switch (strtolower($type))
            {
                case 'json': header("Content-Type: application/json;charset=iso-8859-1");
                             break;
                case 'file': header ( 'Content-Type: text/plain; charset=ISO-8859-1' );
                             header ( "Content-type: application/octet-stream" );
                             header ( "Content-Disposition: attachment; filename=\"$filename\"" );
                             break;
                case 'debug' :   echo '<pre>';
                             break;
                default :
                             break;
            }
        }

        if (is_file($this->_Path.$view))
        {
            include ($this->_Path.$view);
        }
    }
}