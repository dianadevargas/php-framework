#!/usr/bin/php
<?php
$log = '/var/log/dashboard/kissmetrics.log';
 
/**
 * Method for displaying the help and default variables.
 **/
function displayUsage(){
    global $log;
 
    echo "\n";
    echo "Process for upload events from kissmetrics a PHP daemon.\n";
    echo "\n";
    echo "Usage:\n";
    echo "\tDaemon.php [options]\n";
    echo "\n";
    echo "\toptions:\n";
    echo "\t\t--help display this help message\n";
    echo "\t\t--log=<filename> The location of the log file (default '$log')\n";
    echo "\n";
}//end displayUsage()
 
//configure command line arguments
if($argc > 0){
    foreach($argv as $arg){
        $args = explode('=',$arg);
        switch($args[0]){
            case '--help':
                return displayUsage();
            case '--log':
                $log = $args[1];
                break;
        }//end switch
    }//end foreach
}//end if
 
//fork the process to work in a daemonized environment
file_put_contents($log, "Status: starting up.\n", FILE_APPEND);
$pid = pcntl_fork();
if($pid == -1){
	file_put_contents($log, "Error: could not daemonize process.\n", FILE_APPEND);
	return 1; //error
}
else if($pid){
        return pcntl_wait($status, WNOHANG); //protect against zombie children, one wait vs one child 
}
else{
    //the main process
    while(true){
        file_put_contents($log, 'Heart beat ...', FILE_APPEND);
        sleep(10000);
    }//end while
}//end if
 
?>
