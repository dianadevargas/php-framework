<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   utilitiesHelper.php
 *    @name:         UtilitiesHelper
 *    @namespace:    library\traits
 *    @abstract:     Helper class with utility methods
 *    @uses:
 */
namespace library\traits;

trait UtilitiesHelper
{

    /**
     * Replace variables in an array
     *
     */
    static public function replaceArray ($search,$replace,$subject)
    {
        if (is_array($subject)) {
            foreach ($subject as $key => $data) {
                $subject[$key] =  self::replaceArray($search, $replace, $data);
            }
        } else {
            $subject = str_ireplace($search, $replace, $subject);
        }
        return $subject;
    }

    /**
     * Globalize an array as variables
     *
     * @param array $properties
     */
    static public function globalizeProperties (array $properties)
    {
        foreach($properties as $var => $value) {
            global $$var;
            $$var = $value;
        }
    }

    /**
    * Converts time into words like 30 min ago
    *
    * @param int $time
    * @return string
    */
    static public function wordsTiming ($time)
    {
        $time = strtotime('now') - $time; // to get the time since that moment
        $tokens = array (
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
        );
        foreach ($tokens as $unit => $text) {
            if ($time < $unit) continue;
            $numberOfUnits = floor($time / $unit);
            return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'').' Ago';
        }
    }

}