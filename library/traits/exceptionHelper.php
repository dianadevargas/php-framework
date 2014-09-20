<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   exceptionHelper.php
 *    @name:         ExceptionHelper
 *    @namespace:    library\traits
 *    @abstract:     Helper class to manage Exceptions
 *    @uses:
 */
namespace library\traits;

trait ExceptionHelper
{
    public static function ensure($expr, $message=null, $code=null, $type=null)
    {
        if ( !$expr ) {
            if (is_null($type)) {
                throw new Exception($message,$code);
            }
            else {
                throw new $type($message,$code);
            }
        }
    }
}