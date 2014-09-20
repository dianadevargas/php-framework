<?php
namespace cron;

set_time_limit(0);
Global $awsAccessKey;
Global $awsSecretAccessKey;
Global $kissmetricsBucket;
Global $db;
Global $dbprod;
/**
 * Method for displaying the help and default variables.
 **/
function displayUsage(){
    echo "\n";
    echo "Process for crunch events .\n";
    echo "\n";
    echo "Usage:\n";
    echo "\tDaemon.php [options]\n";
    echo "\n";
    echo "\toptions:\n";
    echo "\t\t--help display this help message\n";
    echo "\t\t--log=<filename> The location of the log file (default in config.ini)\n";
    echo "\t\t--env=(testing|producction)\n";
    echo "\n";
}//end displayUsage()

//configure command line arguments
if(isset($argc) && ($argc > 0)){
    foreach($argv as $arg){
        $args = explode('=',$arg);
        switch($args[0]){
            case '--help':
                return displayUsage();
            case '--log':
                $log = $args[1];
                break;
            case '--env':
                $env = $args[1];
                break;
        }//end switch
    }//end foreach
}//end if

// load the classes from library
$appPath = dirname(__FILE__);
$basePath = str_ireplace('cron', '', $appPath);
$libPath  = $basePath.'library/';
include_once $libPath.'traits\loging.php';
include_once $libPath.'traits\utilitiesHelper.php';
include_once $libPath.'traits\ExceptionHelper.php';
include_once $libPath.'interfaces\database.php';
include_once $libPath.'db.php';
include_once $libPath.'databases\dbMysql.php';
include_once $libPath.'model.php';
include_once $libPath.'config.php';
include_once $libPath.'vendor\amazons3\s3.php';
include_once $libPath.'vendor\kissmetrics.php';

class Cron_EventCrunch extends \library\Model
{
    private $kissmetrics;
    /*
     *  When call from the URL
     */

    protected function __construct() {
        parent::__construct();
        Global $awsAccessKey;
        Global $awsSecretAccessKey;
        Global $kissmetricsBucket;
        Global $db;
        $this->kissmetrics = \library\vendor\Kissmetrics::getInstance($db, $awsAccessKey, $awsSecretAccessKey, $kissmetricsBucket);
    }

    public function crunchEvents ()
    {
        $this->kissmetrics->crunchEvents();
        return;
    }
}

// Start the process
// check if we are running from command line
if(isset($argc) && ($argc > 0)){
    \library\traits\Loging::inCron();
}

if (isset($env) && strtoupper($env) == 'TESTING')
{
    define('APPLICATION_ENVIRONMENT', 'TESTING');
    error_reporting(E_ALL);
} else {	// Production
    define('APPLICATION_ENVIRONMENT', 'PRODUCTION');
    error_reporting(E_STRICT);
}

// load configuration file
$config = \library\Config::getConfigArray($basePath.'application/default/config/config.ini',APPLICATION_ENVIRONMENT);
$logDir = $config['logDir'];
if (!is_dir($logDir))
    mkdir ($logDir);
$log = (isset($log)&&!empty($log))?$log:$logDir.'/eventCrunch.'.date('Ymd',  strtotime('now')).'.log';
$awsAccessKey       = $config['awsAccessKey'];
$awsSecretAccessKey = $config['awsSecretAccessKey'];
$kissmetricsBucket  = $config['kissmetricsBucket'];

// ready to run
echo "using log: $log";
\library\traits\Loging::setLog($log);
\library\traits\Loging::printTime("Status: starting up eventsCrunch.php ".date('Y-m-d H:i:s',  strtotime('now')));

// The gear - load database and run
$db = \library\Db::getInstance($config['dbserver'], $config['dbname']);
if (!$db->connect($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname'], true))
{
    \library\traits\Loging::printTime("Error connecting with database : ".$db->last_error);
    exit(1);
}
$eventCrunch = Cron_EventCrunch::getInstance();
$eventCrunch->crunchEvents(); // All methods
\library\traits\Loging::printTime("Status: finish eventsCrunch.php ".date('Y-m-d H:i:s',  strtotime('now')));
exit(0);
?>
