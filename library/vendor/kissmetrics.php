<?php
/*
 * This app gets data from kissmetrics bucket
 *
 */
namespace library\vendor;
use \library\vendor\amazons3\S3;
use \library\Model;
use \library\traits\Loging;

class Kissmetrics extends  Model
{
    use Loging;
    private $db;
    private $s3;
    private $awsSecretAccessKey;
    private $kissmetricsBucket;
    private $maxFiles = 200;

    /*
     * Constructor
     * $db db : dashboard database
     * $awsAccessKey string
     * $awsSecretAccessKey string
     * $kissmetricsBucket string
     * $dbprod db : production database
     */
    protected  function __construct($db,$awsAccessKey,$awsSecretAccessKey,$kissmetricsBucket) {
        parent::__construct();
        $this->db = $db;
        $this->db->connect();
        $this->kissmetricsBucket = $kissmetricsBucket;
        $this->s3 = new S3($awsAccessKey,$awsSecretAccessKey);
    }

    static public function getInstance() {
    	return new self(func_get_arg(0),func_get_arg(1),func_get_arg(2),func_get_arg(3));
    }

    /*
     * Load the events from files in array of filenames or index.csv
     * index array : list of filename to upload if empty read the index.csv
     * checkInBucket: chek if files exist before try to read it
     */
    public function loadEvents ($index=array(),$checkInBucket=false)
    {
        // Read the status file
        $this->printTime('Start process');
        $status = $this->readBucketFile('status.csv');
        $this->printTime($status);

        // Read the index file
        if (empty($index) || $checkInBucket)
        {
            if (empty($index))
            {
                $index = $this->readBucketFile('index.csv');
                //$this->printTime($index);
            }
            // Read bucket list
            $bucket = $this->s3->getBucket($this->kissmetricsBucket);
            $bucketList = array();
            foreach ($bucket as $file){
                if (strpos( $file['name'], 'revisions/') !== false)
                {
                    $bucketList[] = $file['name'];
                }
            }
            foreach($index as $k => $file)
            {
                if (!in_array($file['filename'], $bucketList))
                {
                    unset($index[$k]);
                }
            }
        }
        $now = strtotime('now');
        $today = strtotime(date('Y-m-d',$now)); // at 00:00:00 hour

        $this->printTime('Read all files. Get bitacora');
        $bitacora = $this->getBitacora();
        $this->printTime($bitacora);

        // Upload the bucket list
        $this->printTime('Start uploading the events');
        $skipFiles = array();
        $i = 0;
        foreach($index as $k => $file)
        {
            if (((int)$file['max_timestamp'] >= strtotime($bitacora['events_bitacora_max_timestamp']))
             && ($file['filename'] != $bitacora['events_bitacora_filename'])
             )
            {
                if ($this->saveBitacora($file,$now))
                {
                    $json = $this->readBucketJson($file['filename']);
                    $this->saveEvents($json,$now);
                    $this->printTime('Uploaded file:'.$file['filename']);
                    $i++;
                    if ($i >= $this->maxFiles)
                        break;
                }
                else
                {
                    $this->printTime('Skip file:'.$file['filename']);
                    $skipFiles[] = $file;
                }
                $this->flush();
            }
        }
        $this->updateInsertedEvents($now);
        $this->printTime('End process');
        return $skipFiles;
    }

    /*
     * flush ob if not in cron.
     */
    private function flush()
    {
        if (!self::$inCron)
        {
            ob_flush();
            flush();
        }
    }

    public function inCron()
    {
        self::$inCron = true;
    }

    /*
     * Set max number of files to process in one call
     */
    public function setMaxFiles($maxFiles)
    {
        $this->maxFiles = is_numeric($maxFiles)?$maxFiles:$this->maxFiles;
    }

    /*
     *  Read a file from the bucket and return it in an array
     * $fileName string
     * return array
     */
    public function readBucketFile($fileName)
    {
        $url = S3::getAuthenticatedURL($this->kissmetricsBucket, $fileName, 60, false, false);
        $file = array();
        if (($handle = @fopen($url, "rb")) !== FALSE) {
            $i = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // read file headers
                if ($i == 0)
                {
                    $keys = $data;
                }
                else
                {
                    $obj = array();
                    foreach($data as $k => $v)
                        $obj[$keys[$k]] = $v;
                    $file[] = $obj;
                }
                $i++;
            }
            fclose($handle);
        }
        return $file;
    }


    /*
     *  Read a file from the bucket and return it in an array
     * $fileName string
     * return array
     */
    public function readBucketJson($fileName)
    {
        $url = S3::getAuthenticatedURL($this->kissmetricsBucket, $fileName, 60, false, false);
        $file = array();
        if (($handle = fopen($url, "rb")) !== FALSE)
        {
            while (($json = fgets($handle)) !== FALSE)
            {
                $file[] = json_decode($json, true);
            }
            fclose($handle);
        }
        return $file;
    }

    /*
     *  get the last inserted record in the events bitacora
     * return array : bitacora record
     */
    public function getBitacora ()
    {
        $query ="SELECT events_bitacora_filename, events_bitacora_max_timestamp
                FROM events_bitacora
                WHERE events_bitacora_max_timestamp = (SELECT MAX( events_bitacora_max_timestamp ) FROM events_bitacora ) limit 1 ";
        $pResult = $this->db->select($query);

        if (!$bitacora = $this->db->get_row($pResult, 'MYSQL_ASSOC'))
        {
            $bitacora = array(
              'events_bitacora_filename' => '',
              'events_bitacora_max_timestamp' => 0,
            );
        }
        return $bitacora;
    }

    /*
     * Check if a file object exist already in the events bitacora
     *
     * file onject
     * return boolean : the file exists
     */
    public function checkBitacora($file)
    {
        $filename = $file['filename'];
        $min_timestamp = date('Y-m-d H:i:s',(int)$file['min_timestamp']);
        $max_timestamp = date('Y-m-d H:i:s',(int)$file['max_timestamp']);
        $sql = "SELECT events_bitacora_id FROM events_bitacora WHERE events_bitacora_filename='$filename' and events_bitacora_min_timestamp ='$min_timestamp' and events_bitacora_max_timestamp = '$max_timestamp'";
        if($this->db->select_one($sql)){
                return true;
        }else{
                return false;
        }
    }

    /*
     * Save a file object inthe events bitacora
     *
     */
    protected function saveBitacora($file, $now, $id=0)
    {
        $array = array (
            'events_bitacora_filename' => $file['filename'],
            'events_bitacora_min_timestamp' => (int)$file['min_timestamp'],
            'events_bitacora_max_timestamp' => (int)$file['max_timestamp'],
            'events_bitacora_timestamp' => $now,
         );
        if($id == '0')
        {
                $id = $this->db->insert_array('events_bitacora', $array);
        }
        else
        {
                $res = $this->db->update_array('events_bitacora', $array, "events_bitacora_id='".$id."'");
                if ($res === false)
                    return $res;
        }
        $this->printTime($file);
        return $id;
    }

    /*
     * save the events in a file into the events and identifies table
     */
    protected function saveEvents($file, $now)
    {
        $filelist = array();
        foreach ($file as $json)
        {
            $event  = array();
            $id     = array();
            $key = '';

            // Insert identifier
            if (isset($json['_p2']))
            {
                $id['identify_alias']   = $json['_p2'];
                if (isset($json['_p'])) {
                    $id['identify_person']  = $json['_p'];
                }
                if (isset($json['_t'])) {
                    $id['identify_time']    = $json['_t'];
                }
                $id['identify_processed_time']= $now;
                $this->_saveIdentify($id);
            }
            else
            {
                // Insert events
                if (isset($json['_n']))
                {
                    switch (strtolower($json['_n']))
                    {
                        case 'visited site' :   $eventName = 'siteLoad';
                                                $eventType = 'site';
                                                break;
                        case 'search engine hit':$eventName = 'siteLoadOrganic';
                                                $eventType = 'site';
                                                break;
                        case 'ad campaign hit':$eventName = 'marketingLanding';
                                                $eventType = 'marketing';
                                                break;
                        default :               $eventName = $json['_n'];
                                                if (stripos(strtolower($json['_n']), 'active') === 0)
                                                    $eventType = 'active';
                                                elseif (stripos(strtolower($json['_n']), 'site') === 0)
                                                    $eventType = 'site';
                                                elseif (stripos(strtolower($json['_n']), 'widget') === 0)
                                                    $eventType = 'widget';
                                                elseif (stripos(strtolower($json['_n']), 'social') === 0)
                                                    $eventType = 'social';
                                                else
                                                    $eventType = 'other';
                                                break;
                    }
                    $event['event_type']    = $eventType;
                    $event['event_name']    = $eventName;
                    $key                    = $json['_n'];
                }
                elseif (isset($json['returning']) && ($json['returning'] == 1))
                {
                    $event['event_type'] = 'site';
                    $event['event_name'] = 'siteReturning';
                }
                if (isset($json['_p'])) {
                    $event['event_person']  = $json['_p'];
                    $key                   .= $json['_p'];
                }
                if (isset($json['_t'])) {
                    $event['event_time']    = $json['_t'];
                    $key                   .= $json['_t'];
                }
                $event['event_property']= json_encode($json);
                $event['event_processed_time']= $now;
                if (!in_array($key, $filelist)) {
                    $this->_saveEvent($event);
                    $filelist[] = $key;   // do NOT store duplicates
                }
            }
        }
    }

    public function newUsers()
    {
        // Update the users table with new users in the production database
        $lastUser = $this->_lastUser();
        $newUsers = $this->_getProdUsersToInsert($lastUser);

        foreach ($newUsers as $key => $user) {
            $this->_saveUser($user);
        }
        // Insert the default users
        $this->insertDefaultUserInst();
        return count($newUsers);
    }
    /*
     * Update and create the intermedian tables
     */
    public function updateInsertedEvents($now=null)
    {
        // Update the users table with new users in the production database
        $this->newUsers();

        /*
         *  Insert new user identifiers
         */

        // person by email or user_hash in users table
        $where = empty($now)?'':"date_format(identifies.identify_processed_time,'%Y-%m-%d %H:%i:%s') = '".date('Y-m-d H:i:s',$now)."' and";
        $query ="INSERT INTO `user_identifies` (user_identifies_person, user_id) "
               ." SELECT distinct identifies.identify_person, users.user_id "
               ." FROM identifies "
               ." join users on identifies.identify_person = user_email or identifies.identify_person = user_hash "
               ." left join user_identifies on user_identifies.user_identifies_person = identifies.identify_person "
               ." WHERE $where user_identifies_person is null ";
        $this->db->select($query);

        // alias by email or user_hash in users table
        $query ="INSERT INTO `user_identifies` (user_identifies_person, user_id) "
               ." SELECT distinct identifies.identify_alias, users.user_id "
               ." FROM identifies "
               ." join users on identifies.identify_alias = user_email or identifies.identify_alias = user_hash "
               ." left join user_identifies on user_identifies.user_identifies_person = identifies.identify_alias "
               ." WHERE $where user_identifies_person is null ";
        $this->db->select($query);

        // person by alias in the user identify table
        $query ="INSERT INTO `user_identifies` (user_identifies_person, user_id) "
               ." SELECT identifies.identify_person, max(alias.user_id) "
               ." FROM identifies "
               ." join user_identifies as alias on alias.user_identifies_person = identifies.identify_alias "
               ." left join user_identifies as ready on ready.user_identifies_person = identifies.identify_person "
               ." WHERE $where ready.user_identifies_person is null "
               ." group by identifies.identify_person  ";
        $this->db->select($query);

        // alias by alias in the user identify table
        $query ="INSERT INTO `user_identifies` (user_identifies_person, user_id) "
               ." SELECT identifies.identify_alias, max(alias.user_id) "
               ." FROM identifies "
               ." join user_identifies as alias on alias.user_identifies_person = identifies.identify_person "
               ." left join user_identifies as ready on ready.user_identifies_person = identifies.identify_alias "
               ." WHERE $where ready.user_identifies_person is null "
               ." group by identifies.identify_alias  ";
        $this->db->select($query);

        // person by email or user_hash in users table
        $where = empty($now)?'':"date_format(event_processed_time,'%Y-%m-%d %H:%i:%s') = '".date('Y-m-d H:i:s',$now)."' and";
        $query ="INSERT INTO `user_identifies` (user_identifies_person, user_id) "
               ." SELECT distinct events.event_person, users.user_id "
               ." FROM events "
               ." join users on events.event_person = users.user_email or events.event_person = users.user_hash "
               ." left join user_identifies on user_identifies.user_identifies_person = event_person "
               ." WHERE $where user_identifies.user_identifies_person is null ";
        $this->db->select($query);

        /*
         *  Update users last active date
         */
        $where = empty($now)?'':"date_format(event_processed_time,'%Y-%m-%d %H:%i:%s') = '".date('Y-m-d H:i:s',$now)."' and";
        $active ="SELECT user_id, max(event_time) time FROM events "
              ." join user_identifies on user_identifies_person = event_person "
              ." where $where event_type is not null and event_type not in ('download','other','formspring') "
              ." group by user_id ";
        $dResults = $this->db->select($active);
        while ($aRow = $this->db->get_row($dResults, 'MYSQL_ASSOC')) {
            $query ="update users "
                    ." set user_last_active = '".$aRow['time']."' "
                    ." where user_id = ".$aRow['user_id']." and (user_last_active is null or user_last_active < '".date('Y-m-d',strtotime($aRow['time']))."') ";
            $this->db->select($query);
        }
        $this->db->free_result($dResults);

        /*
         *  Installations
         *  status :
         *      start -> downloadStart event
         *      success -> downloadSuccess event without downloadStart
         *      finish -> downloadSuccess event and downloadStart
         */
        $time = empty($now)?strtotime('now'):$now;

        // Insert Installations
        $starts = array();
        $where = empty($now)?'':"date_format(event_processed_time,'%Y-%m-%d %H:%i:%s') = '".date('Y-m-d H:i:s',$now)."' and";
        $active ="SELECT distinct event_person, event_time, event_property,  user_id  FROM events "
              ." left join user_identifies on user_identifies_person = event_person "
              ." where $where event_type is not null and event_type = 'download' and event_name = 'downloadstart' ";
        $dResults = $this->db->select($active);
        while ($aRow = $this->db->get_row($dResults, 'MYSQL_ASSOC')) {
            $prop = json_decode($aRow['event_property']);
            $page = isset($prop->page)?parse_url($prop->page,PHP_URL_HOST):'';
            $obj = array(
                  "user_id" => $aRow['user_id'],
                  "install_tmp_user_id" => $aRow['event_person'],
                  "install_extension" => (isset($prop->installer)?$prop->installer:''),
                  "install_browser" => (isset($prop->browser)?$prop->browser:''),
                  "install_platform" => (isset($prop->os)?$prop->os:''),
                  "install_referral" => (isset($prop->referrer)?$prop->referrer:''),
                  "install_source" => (isset($prop->page)?(empty($page)?$prop->page:$page):''),
                  "install_affiliate_id" => (isset($prop->affiliate_id)?$prop->affiliate_id:''),
                  "install_campaign" => (isset($prop->campaign)?$prop->campaign:''),
                  "install_offer" => (isset($prop->offer_name)?$prop->offer_name:''),
                  "install_status" => (empty($aRow['user_id'])?'start':'finish'),
                  "install_time" => $aRow['event_time'],
                  "install_tsu" => $aRow['event_time'],
                  "install_processed_time" => date('Y-m-d H:i:s',$time)
            );
            foreach ($obj as $k => $v)
            {
                if (empty($v) && ($k!='install_browser') && ($k!='install_platform'))
                    unset($obj[$k]);
                else
                    $obj[$k]=addslashes($v);
            }
            $key = array_keys($obj);
            $query =" insert into installs (".implode(",",$key).") values ('".implode("','",$obj)."')";
            $id = $this->db->insert_sql($query);
            if ($id === true || ($id == 0))
            {
                $starts[] = $obj;
            }
        }
        if (is_resource($dResults))
          $this->db->free_result($dResults);

        // try to found existing installs status = 'success' to update info with the start event data
        if (!empty($starts))
        {
            foreach ($starts as $k => $obj)
            {
                $set = array(" install_status = 'finish' "," install_processed_time = '".$obj['install_processed_time']."' ");
                if (isset($obj['user_id']))
                  $set[] = " user_id = ifnull(user_id,'".$obj['user_id']."') ";
                if (isset($obj['install_extension']))
                  $set[] = " install_extension = ifnull(install_extension,'".$obj['install_extension']."') ";
                if (isset($obj['install_referral']))
                  $set[] = " install_referral = '".$obj['install_referral']."' ";
                if (isset($obj['install_affiliate_id']))
                  $set[] = " install_affiliate_id = '".$obj['install_affiliate_id']."' ";
                if (isset($obj['install_offer']))
                  $set[] = " install_offer = '".$obj['install_offer']."' ";
                if (isset($obj['install_campaign']))
                  $set[] = " install_campaign = '".$obj['install_campaign']."' ";
                $query = "update installs set ".implode(",",$set);
                $query .= " where install_status = 'success' and install_browser = '".$obj['install_browser']."' and install_platform = '".$obj['install_platform']."' and install_time >= '".$obj['install_time']."' and ";
                $query .= isset($obj['user_id'])?" (install_tmp_user_id = '".$obj['install_tmp_user_id']."' or user_id = '".$obj['user_id']."' ) ":" install_tmp_user_id = '".$obj['install_tmp_user_id']."' ";
                $id = $this->db->update_sql($query);
            }
        }


        // try to found existing installs status = 'start' to update info with the success events data
        $starts = array();
        $where = empty($now)?'':"date_format(event_processed_time,'%Y-%m-%d %H:%i:%s') = '".date('Y-m-d H:i:s',$now)."' and";
        $active ="SELECT distinct event_person, event_time, event_property,  user_id  FROM events "
              ." left join user_identifies on user_identifies_person = event_person "
              ." where $where event_type is not null and event_type = 'download' and event_name = 'downloadsuccess' ";
        $dResults = $this->db->select($active);
        while ($aRow = $this->db->get_row($dResults, 'MYSQL_ASSOC')) {
            $prop = json_decode($aRow['event_property']);
            $page = isset($prop->page)?parse_url($prop->page,PHP_URL_HOST):'';
            $obj = array(
                  "user_id" => $aRow['user_id'],
                  "install_tmp_user_id" => $aRow['event_person'],
                  "install_extension" => (isset($prop->installer)?$prop->installer:''),
                  "install_browser" => (isset($prop->browser)?$prop->browser:''),
                  "install_platform" => (isset($prop->os)?$prop->os:''),
                  "install_referral" => (isset($prop->referrer)?$prop->referrer:''),
                  "install_source" => (isset($prop->page)?(empty($page)?$prop->page:$page):''),
                  "install_affiliate_id" => (isset($prop->affiliate_id)?$prop->affiliate_id:''),
                  "install_campaign" => (isset($prop->campaign)?$prop->campaign:''),
                  "install_offer" => (isset($prop->offer_name)?$prop->offer_name:''),
                  "install_status" => 'success',
                  "install_time" => $aRow['event_time'],
                  "install_tsu" => $aRow['event_time'],
                  "install_processed_time" => date('Y-m-d H:i:s',$time)
            );
            foreach ($obj as $k => $v)
            {
                if (empty($v) && ($k!='install_browser') && ($k!='install_platform'))
                    unset($obj[$k]);
                else
                    $obj[$k]=addslashes($v);
            }
            $set = array(" install_status = 'finish' "," install_processed_time = '".$obj['install_processed_time']."' ");
            if (isset($obj['user_id']))
              $set[] = " user_id = ifnull(user_id,'".$obj['user_id']."') ";
            if (isset($obj['install_extension']))
              $set[] = " install_extension = ifnull(install_extension,'".$obj['install_extension']."') ";
            if (isset($obj['install_tsu']))
              $set[] = " install_tsu = '".$obj['install_time']."' ";
            $query = "update installs set ".implode(",",$set);
            $query .= isset($obj['user_id'])?" where (install_status = 'start' or (install_status = 'finish'  and user_id is null)) ":" where install_status = 'start' ";
            $query .= " and install_browser = '".$obj['install_browser']."' and install_platform = '".$obj['install_platform']."' and install_time < '".$obj['install_time']."' and ";
            $query .= isset($obj['user_id'])?" (install_tmp_user_id in (select user_identifies_person from user_identifies where user_id = '".$obj['user_id']."') or user_id = '".$obj['user_id']."' ) ":" install_tmp_user_id = '".$obj['install_tmp_user_id']."' ";

            $id = $this->db->update_sql($query);
            if (($id === true) && ($id !== 1))
            {
                $starts[] = $obj;
            }
        }
        if (is_resource($dResults))
          $this->db->free_result($dResults);

        // try to found existing installs ststus = 'success' to update info with the start data
        if (!empty($starts))
        {
            foreach ($starts as $k => $obj)
            {
                $key = array_keys($obj);
                $query =" insert into installs (".implode(",",$key).") values ('".implode("','",$obj)."')";
                $id = $this->db->insert_sql($query);
            }
        }

        // Update user_inst
        $this->insertUserInst($now);
        //$this->updateUserInst($now);
    }

    /*
     * to update with installation data
     */
    public function updateUserInst($now=null)
    {
        $this->insertUserInst($now);
        return;

        // Update user_inst
        $where = empty($now)?'':"date_format(event_processed_time,'%Y-%m-%d %H:%i:%s') = '".date('Y-m-d H:i:s',$now)."' and";
        $active ="SELECT user_id, max(event_time) time FROM events "
              ." join user_identifies on user_identifies_person = event_person "
              ." where $where event_type is not null and event_type not in ('download','other','formspring') "
              ." group by user_id ";
        $dResults = $this->db->select($active);
        while ($aRow = $this->db->get_row($dResults, 'MYSQL_ASSOC')) {
            $query ="update `user_inst` "
                    ." set user_inst_last_active = '".$aRow['time']."' "
                    ." where user_id = ".$aRow['user_id']." and (user_inst_last_active is null or user_inst_last_active < '".date('Y-m-d',strtotime($aRow['time']))."') ";
            $this->db->select($query);
        }
        $this->db->free_result($dResults);
    }

    public function insertDefaultUserInst()
    {
        // Insert a default installation no browser or os
        $query ="INSERT INTO `user_inst`(`user_id`,`tsi`,`tsu`)
                SELECT users.user_id, users.tsi, users.tsi FROM users
                left join user_inst on user_inst.user_id = users.user_id
                where user_inst_id is null ";
        $this->db->select($query);

        return;
    }

    public function insertUserInst($now=null)
    {
        // Insert new installation from Installs table
        $where = empty($now)?'':"date_format(install_processed_time,'%Y-%m-%d %H:%i:%s') = '".date('Y-m-d H:i:s',$now)."' and";
        $query ="INSERT INTO `user_inst` "
               ."(`user_id`, `user_inst_extension`, `user_inst_browser`, `user_inst_platform`, "
               ." `user_inst_referral`, `user_inst_source`, `user_inst_affiliate_id`, "
               ." `user_inst_campaign`, `user_inst_offer`, `user_inst_status`, `install_id`, `tsi`, `tsu`) "
               ."  SELECT installs.`user_id`, `install_extension`, `install_browser`, `install_platform`, "
               ."  `install_referral`, `install_source`, `install_affiliate_id`, "
               ."  `install_campaign`, `install_offer`, 'active',  installs.`install_id`, `install_time`, `install_tsu` "
               ."  from installs "
               ."  left join user_inst on user_inst.user_id = installs.user_id and user_inst.user_inst_browser = installs.install_browser and user_inst.user_inst_platform = installs.install_platform "
               ."  where $where user_inst_id is null and installs.user_id is not null and install_status <> 'start' ";
        $this->db->select($query);

        // Delete default Installs
        $default ="SELECT user_inst.user_id, count(*) FROM user_inst "
              ." JOIN user_inst as d on  d.user_id = user_inst.user_id and d.user_inst_browser = '' and d.user_inst_platform = '' "
              ." where user_inst.user_inst_browser <> '' or user_inst.user_inst_platform <> '' "
              ." group by user_inst.user_id ";
        $dResults = $this->db->select($default);
        while ($aRow = $this->db->get_row($dResults, 'MYSQL_ASSOC')) {
            $query ="DELETE FROM user_inst "
                  ." where user_id = '".$aRow['user_id']."' and user_inst_browser = '' and user_inst_platform = '' ";
            $this->db->delete($query);
        }
        return;
    }

    /*
     *  return the last user_id from the users table
     */
    private function _getProdUsersToInsert ($lastUser)
    {
        $lastUser = is_numeric($lastUser)?$lastUser:0;
        $query ="SELECT id as user_id, email as user_email, user_hash, tsi from user where id > $lastUser";
        $result = array();
        $dResults = $this->db->select($query);
        while ($aRow = $this->db->get_row($dResults, 'MYSQL_ASSOC')) {
                $result[] = $aRow;
        }
        $this->db->free_result($dResults);
        return $result;
    }

    /*
     * Sum events from a date and update the crunch_bitacora and the crunch_events tables
     */
    public function crunchEvents ()
    {
        // Read the status file
        $this->printTime('Start process');
        $now = strtotime('now');

        $this->printTime('Get bitacora');
        $lastCrunchDay = $this->getCrunchBitacoraLastDay();
        $lastEventDay = $this->getBitacoraLastDay();
        $this->printTime("Last crunch day:$lastCrunchDay. Last event day:$lastEventDay.");

        // Crunch events
        $init = date('Y-m-d',  strtotime("$lastCrunchDay +1 day"));
        for($d=$init; $d < $lastEventDay; $d=date('Y-m-d',  strtotime("$d +1 day")))
        {
            if ($this->saveCrunchEvents($d, $now))
            {
                $this->saveCrunchBitacora($d, $now);
                $this->printTime('Crunch date:'.$d);
            }
            else
            {
                $this->printTime('Error crunching date:'.$d);
                break;
            }
        }
        $this->printTime('Finish');
   }

    /*
     * save crunch_events for a date
     */
    protected function saveCrunchEvents($date, $now)
    {
        // Insert new installation from Installs table
        $query ="INSERT INTO `crunch_events` "
               ."(event_type, event_name, event_occur, event_time, event_processed_time) "
               ."SELECT event_type, event_name, count(*), DATE(event_time), '".date('Y-m-d H:i:s',$now)."' FROM events "
               ."where DATE(event_time) = DATE('".date('Y-m-d',strtotime($date))."') and (event_name is not null or event_type is not null) and event_type not in ('formspring')"
               .'group by event_name, event_type, DATE(event_time)';
        $r = $this->db->select($query);
        return ($r === false)?false:true;
    }


    /*
     * Save a date in the crunch bitacora
     *
     */
    protected function saveCrunchBitacora($date, $now, $id=0)
    {
        $array = array (
            'crunch_bitacora_date' => date('Y-m-d',strtotime($date)),
            'crunch_bitacora_timestamp' => $now,
         );
        if($id == '0')
        {
                $id = $this->db->insert_array('crunch_bitacora', $array);
        }
        else
        {
                $res = $this->db->update_array('crunch_bitacora', $array, "crunch_bitacora_id='".$id."'");
                if ($res === false)
                    return $res;
        }
        $this->printTime($array);
        return $id;
    }


    /*
     *  get the last date in the events bitacora
     * return array : bitacora record
     */
    public function getBitacoraLastDay ()
    {
        $query ="SELECT MAX( events_bitacora_max_timestamp ) date  FROM events_bitacora";
        $date = $this->db->select_one($query);
        return ($date?date('Y-m-d', strtotime($date)):0);
    }

    /*
     *  get the last date in the events bitacora
     * return array : bitacora record
     */
    public function getCrunchBitacoraLastDay ()
    {
        $query ="SELECT MAX( crunch_bitacora_date ) date  FROM crunch_bitacora";
        $date = $this->db->select_one($query);
        if (empty($date))
        {
            $query ="SELECT MIN( events_bitacora_min_timestamp ) date  FROM events_bitacora";
            $date = $this->db->select_one($query);
            $date = $date?date('Y-m-d', strtotime("$date -1 day")):'2013-05-01';
        }
        return date('Y-m-d', strtotime($date));
    }

    /*
     *  return the last user_id from the users table
     */
    private function _lastUser ()
    {
        $query ="SELECT max(user_id) from users";
        return $this->db->select_one($query);
    }

    /*
     * Save or update a user in the users table
     */
    private function _saveUser($array, $id=0)
    {
        if($id == '0')
        {
                $id = $this->db->insert_array('users', $array);
        }
        else
        {
                $res = $this->db->update_array('users', $array, "user_id='".$id."'");
                if ($res == false)
                    return $res;
        }
        return $id;
    }

    /*
     * Save or update an event in the events table
     */
    private function _saveEvent($array, $id=0)
    {
        if($id == '0')
        {
                $id = $this->db->insert_array('events', $array);
        }
        else
        {
                $res = $this->db->update_array('events', $array, "events_id='".$id."'");
                if ($res == false)
                    return $res;
        }
        return $id;
    }

    /*
     *  save or update an identitfy event in the identifies table
     */
    private function _saveIdentify($array, $id=0)
    {
        if($id == '0')
        {
                $id = $this->db->insert_array('identifies', $array);
        }
        else
        {
                $res = $this->db->update_array('identifies', $array, "identifies_id='".$id."'");
                if ($res == false)
                    return $res;
        }
        return $id;
    }

}
?>
