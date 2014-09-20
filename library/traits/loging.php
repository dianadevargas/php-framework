<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   loging.php
 *    @name:         Loging
 *    @namespace:    library\traits
 *    @abstract:     Helper class with utility methods fot log
 *    @uses:
 */
namespace library\traits;

trait Loging
{
    private static $microtime_start = null;
    protected static $inCron = false;
    protected static $log = '';


    static public function inCron()
    {
        self::$inCron = true;
    }

    static public function setLog($file)
    {
        self::$log = $file;
    }

    static public function printTime($text='',$logit=false)
    {
        if (is_array($text)) {
            $text = json_encode ($text);
        }

        $logit = $logit || self::$inCron;

        if(self::$microtime_start === null)
        {
            self::$microtime_start = microtime(true);
            if ($logit)
            {
                $class = isset($this)?get_class($this):'Model';
                if (empty(self::$log))
                    syslog (LOG_INFO, "$class : $text (0.0)");
                else
                    file_put_contents(self::$log, "$text (0.0)".PHP_EOL, FILE_APPEND);
            }
            else
            {
                 echo "$text (0.0)<br>".PHP_EOL;
            }
        }
        else
        {
            $microtime_end = microtime(true);
            if ($logit)
            {
                if (empty(self::$log))
                    syslog (LOG_INFO, "$text (".number_format($microtime_end - self::$microtime_start,4).')');
                else
                    file_put_contents(self::$log, "$text (".number_format($microtime_end - self::$microtime_start,4).")".PHP_EOL, FILE_APPEND);
            }
            else
            {
                echo  "$text (".number_format($microtime_end - self::$microtime_start,4).')<br>'.PHP_EOL;
            }
            self::$microtime_start = $microtime_end;
        }
    }

    public static function resetCssCounter($i=1)
    {
        self::$css_count = is_numeric($i)?$i:1;
    }

    public static  function resetTime()
    {
        self::$microtime_start = microtime(true);
    }

}