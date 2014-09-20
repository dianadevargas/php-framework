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
    echo "Process for upload events from kissmetrics a PHP daemon.\n";
    echo "\n";
    echo "Usage:\n";
    echo "\tDaemon.php [options]\n";
    echo "\n";
    echo "\toptions:\n";
    echo "\t\t--help display this help message\n";
    echo "\t\t--log=<filename> The location of the log file (default in config.ini)\n";
    echo "\t\t--env=(testing|producction)\n";
    echo "\t\t--max=number of files to process\n";
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
            case '--max':
                $maxFiles = $args[1];
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

class Cron_EventLoad extends \library\Model
{
    use \library\traits\Loging;
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
        $this::inCron();
    }

    public function loadEvents ($index=array(),$level=0)
    {
        $skipFiles = $this->kissmetrics->loadEvents($index);
        foreach ($skipFiles as $k => $file)
        {
            if ($this->kissmetrics->checkBitacora($file))
            {
                $this->printTime('SkipFiles uploaded : '.$file['filename']);
                unset($skipFiles[$k]);
            }
            else
            {
                $this->printTime('SkipFiles re-try : '.$file['filename']);
            }
        }
        if (!empty($skipFiles) && ($level < 5))
        {
            $level++;
            $this->loadEvents($skipFiles,$level);
        }
        return;
    }

    public function listEventsToUpload ()
    {
        $this::printTime('Files pendding to upload : ');
        $index = $this->kissmetrics->readBucketFile('index.csv');
        $files = array();
        foreach ($index as $k => $file)
        {
            if (!$this->kissmetrics->checkBitacora($file))
            {
                $this->printTime('To upload : '.$file['filename']);
                $files[] = $file;
            }
        }
        return $files;
    }

    public function setMaxFiles($maxFiles)
    {
        $this->kissmetrics->setMaxFiles($maxFiles);
    }

    public function newUsers()
    {
        return $this->kissmetrics->newUsers();
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
$log = (isset($log)&&!empty($log))?$log:$logDir.'/eventLoad.'.date('Ymd',  strtotime('now')).'.log';
$awsAccessKey       = $config['awsAccessKey'];
$awsSecretAccessKey = $config['awsSecretAccessKey'];
$kissmetricsBucket  = $config['kissmetricsBucket'];

// ready to run
echo "using log: $log";
\library\traits\Loging::setLog($log);
\library\traits\Loging::printTime("Status: starting up eventsLoad.php ".date('Y-m-d H:i:s',  strtotime('now')));

// The gear - load database and run
$db = \library\Db::getInstance($config['dbserver'], $config['dbname']);
if (!$db->connect($config['dbserver'], $config['dbuser'], $config['dbpass'], $config['dbname'], true))
{
    \library\traits\Loging::printTime("Error connecting with database : ".$db->last_error);
    exit(1);
}
$eventLoad = Cron_EventLoad::getInstance();

// Upload Events
if (isset($maxFiles))
    $eventLoad->setMaxFiles($maxFiles);

$index = $eventLoad->listEventsToUpload ();
if (!empty($index))
{
    $eventLoad->loadEvents($index); // All events
}
else
{
    $new = $eventLoad->newUsers(); // All users
    \library\traits\Loging::printTime("New users: $new");
}
\library\traits\Loging::printTime("Status: finish eventsLoad.php ".date('Y-m-d H:i:s',  strtotime('now')));
exit(0);
?>
