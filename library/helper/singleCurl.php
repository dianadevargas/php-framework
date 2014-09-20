<?php
/**
 *    @package:      Framework
 *    @author:       Diana De vargas
 *    @since:        1 August 2014
 *    @version:      2.0
 *    @filesource:   singleCurlHelper.php
 *    @name:         SingleCurlHelper
 *    @namespace:    library\helper
 *    @abstract:     Helper class to manage a secuencial CURL
 *    @uses:
 */
namespace library\helper;

 class SingleCurlHelper  extends  Model
 {
    //protected $_useragent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-GB; rv:1.9.0.14) Gecko/2009082707 Firefox/3.0.14 (.NET CLR 3.5.30729)';
    protected $_useragent = 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:2.0b10) Gecko/20110126 Firefox/4.0b10';
    protected $_url;
    protected $_followlocation;
    protected $_timeout;
    protected $_maxRedirects;
    protected $_cookieFileLocation = '/cookie.txt';
    protected $_cookiekeep = false;
    protected $_post;
    protected $_postFields;
    protected $_referer = "http://www.google.com";

    protected $_session;
    protected $_webpage;
    protected $_includeHeader;
    protected $_noBody;
    protected $_isAjax;
    protected $_status;
    protected $_respurl;
    protected $_binaryTransfer;
    protected $_err;
    protected $_msg;
    public    $authentication = 0;
    public    $auth_name      = '';
    public    $auth_pass      = '';
    public    $s;
    protected $_proxy;

    protected $_verb;
    protected $_requestBody;
    protected $_requestLength  = 0;
    protected $_acceptType;
    protected $_responseBody;
    protected $_responseInfo;
    protected $options = array();

    /**
     * Constructor inic variables with parameters or defaults
     *
     * @param string $url = ''
     * @param integer $timeOut = 30
     * @param string $acceptType='text/html'
     * @param string $verb='GET'
     * @param boolean $includeHeader = false
     * @param boolean $followlocation = true
     * @param integer $maxRedirecs = 4
     * @param boolean $binaryTransfer = false
     * @param boolean $noBody = false
     *
     * @return void
     */
     protected function __construct($url='',$timeOut = 5,$acceptType='text/html',$verb='GET',$includeHeader = false, $followlocation = true,$maxRedirecs = 4,$binaryTransfer = false,$noBody = false)
     {
        $this->_url             = $url;
        $this->_followlocation     = $followlocation;
        $this->_timeout         = $timeOut;
        $this->_maxRedirects    = $maxRedirecs;
        $this->_noBody             = $noBody;
        $this->_includeHeader     = $includeHeader;
        $this->_binaryTransfer     = $binaryTransfer;

        $this->_verb            = $verb;
        $this->_requestBody        = null;
        $this->_acceptType        = strtolower($acceptType);
        $this->_responseInfo    = null;
        $config = Config::getInstance(Context::getInstance());
        $this->_cookieFileLocation = $config->cokieFile;
        $this->_proxy = is_array($config->proxy)?$config->proxy:array();
     }

     public static function getInstance($url='',$timeOut = 5,$acceptType='text/html',$verb='GET',$includeHeader = false, $followlocation = true,$maxRedirecs = 4,$binaryTransfer = false,$noBody = false)
     {
     	return new self($url, $timeOut, $acceptType, $verb, $includeHeader, $followlocation, $maxRedirecs, $binaryTransfer, $noBody);
     }

    /**
     * buildPostBody serializa the array and Generate URL-encoded query string
     *
     * @param array $data
     * @param string $glue first character to use as a glue in the URL
     *
     * @return string
     */
     public function buildPostBody ($data = null,$glue='')
    {
        $data = ($data !== null) ? $data : array();
        if (!is_array($data))
        {
            throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
        }
        $data = ((count($data)==1) && isset($data[0]))?$data[0]:$data; // clean up extra level
        $data = (count($data)>0)?$glue.http_build_query($data, '', '&'):'';
        return $data;
    }


    /**
     * buildPostFields clean array
     *
     * @param array $data
     *
     * @return array
     */
     public function buildPostFields ($data = null,$glue='')
    {
        $data = ($data !== null) ? $data : array();
        if (!is_array($data))
        {
            throw new InvalidArgumentException('Invalid data input for postBody.  Array expected');
        }
        $data = ((count($data)==1) && isset($data[0]))?$data[0]:$data; // clean up extra level
        $data = (count($data)>0)?$data:'';
        return $data;
    }


    public function setReferer($use){
       $this->_referer = $use;
    }

    public function resetReferer(){
       $this->_referer = "http://www.google.com";;
    }
    /**
     * useAuth set the variable authentication with the parameter value
     *
     * @param boolean $use
     *
     * @return void
     */
    public function useAuth($use){
       $this->authentication = ($use == true)?1:0;
     }

    /**
     * setName set the variable auth_name with the parameter value
     *
     * @param string $name  authorization user name
     *
     * @return void
     */
     public function setName($name){
       $this->auth_name = $name;
     }

    /**
     * setName set the variable auth_pass with the parameter value
     *
     * @param string $pass  authorization password
     *
     * @return void
     */
     public function setPass($pass){
       $this->auth_pass = $pass;
     }

    /**
     * setCookiFileLocation set the variables cookieFileLocation and cookiekeep with the parameters value
     *
     * @param string $path path and file name of the cookie file
     * @param boolean $keep delete or keep the cookie file when the process finish
     *
     * @return void
     */
     public function setCookiFileLocation($path,$keep = false)
     {
         $this->_cookieFileLocation = $path;
         $this->_cookiekeep = $keep;
     }

    /**
     * setPost set the variables post to true and postFields with the parameters value
     *
     * @param array $requestBody array with the variables to send in the postFields
     *
     * @return void
     */
     public function setPost ($requestBody)
     {
         if ($requestBody == null)
             $requestBody = array();
        $this->_post = true;
        $this->_postFields = $this->buildPostFields($requestBody);
     }

    /**
     * setGet set the CURLOPT_HTTPGET in the curl to true and add the $requestBody to the url
     *
     * @param array $requestBody array with the variables to send in the url
     *
     * @return void
     */
     public function setGet ($requestBody)
     {
        $this->_post = false;
        $this->_url .= $this->buildPostBody($requestBody,'?');
        $this->opt(CURLOPT_HTTPGET        , true);
     }

    /**
     * setPut set the CURLOPT_PUT in the curl to true and add the $requestBody a file to be send in the curl
     *
     * @param array $requestBody array with the variables to send in the file
     *
     * @return void
     */
     public function setPut ($requestBody)
     {
        $this->_post = true;
        $data = $this->buildPostBody($requestBody);
        $this->_requestLength = strlen($data);
        //$fh = fopen('output', 'w+');
        $fh = tmpfile();
        fwrite($fh, $data);
        rewind($fh);

        $this->opt(CURLOPT_INFILE        , $fh);
        $this->opt(CURLOPT_INFILESIZE    , $this->_requestLength);
        $this->opt(CURLOPT_PUT            , true);
        return $fh;
     }

    /**
     * setDelete set the CURLOPT_CUSTOMREQUEST in the curl to DELETE and add the $requestBody to the url
     *
     * @param array $requestBody array with the variables to send in the curl
     *
     * @return void
     */
     public function setDelete ($requestBody)
     {
        $this->_post = false;
        $this->_url .= $this->buildPostBody($requestBody,'?');
        $this->opt(CURLOPT_CUSTOMREQUEST, 'DELETE');
     }


    /**
     * setVerify set the CURLOPT_CUSTOMREQUEST in the curl to VERIFY and add the $requestBody a file to be send in the curl
     *
     * @param array $requestBody array with the variables to send in the file
     *
     * @return void
     */
     public function setVerify ($requestBody)
     {
        $this->_post = true;
        $data = $this->buildPostBody($requestBody);
        $this->_requestLength = strlen($data);
        //$fh = fopen('output', 'w+');
        $fh = tmpfile();
        fwrite($fh, $data);
        rewind($fh);

        $this->opt(CURLOPT_INFILE        , $fh);
        $this->opt(CURLOPT_INFILESIZE    , $this->_requestLength);
        $this->opt(CURLOPT_PUT            , true);
        $this->opt(CURLOPT_CUSTOMREQUEST, 'VERIFY');
        return $fh;
     }

     /**
     * setAjax set the variable includeHeader with true and isAjax with the parameter value
     *
     * @param boolean $blnValue
     *
     * @return void
     */
     public function setAjax( $blnValue ){
        $this->_includeHeader = true;
        $this->_isAjax = $blnValue;
    }

    /**
     * setAuth set the options CURLOPT_HTTPAUTH and CURLOPT_USERPWD with the values of the auth name and password is the authentication variable is true
     *
     * @return void
     */
    protected function setAuth ()
    {
        if ($this->authentication && $this->auth_name !== null && $this->auth_pass !== null)
        {
            $this->useAuth(true);
            $this->opt(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
            $this->opt(CURLOPT_USERPWD, $this->auth_name . ':' . $this->auth_pass);
        }
    }

    /**
     * Insert in the array opt the $option key and the $value
     *
     * @param string $option
     * @param string $value
     *
     * @return void
     */
    public function opt($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Create the curl with the active options and execute it getting the response
     *
     * @param string $url
     * @param array  $requestBody parameters o variables to send
     * @param array  $verb action GET, POST, PUT or  DELETE
     *
     * @return void
     */
    public function createCurl($verb,$url=null,$requestBody=null,$useProxy=false)
     {
        $this->__flush();
         $headers = array();

         if($url != null){
            $this->_url = $url;
        }
        if (!isset($this->s) || !is_resource($this->s)){
            $this->s = curl_init();
        }

        $this->_verb = (is_null($verb))?strtoupper($this->_verb):strtoupper($verb);
        switch ($this->_verb)
        {
            case 'GET':
                $this->setGet($requestBody);
                break;
            case 'POST':
                $this->setPost ($requestBody);
                break;
            case 'PUT':
                $fh = $this->setPut($requestBody);
                break;
            case 'DELETE':
                $this->setDelete($requestBody);
                break;
            case 'VERIFY':
                $fh = $this->setVerify($requestBody);
                break;
            default:
                throw new InvalidArgumentException('Current verb (' . $this->_verb . ') is an invalid REST verb.');
        }

        $this->opt(CURLOPT_URL              , $this->_url);
        //$this->opt(CURLOPT_HTTPHEADER     , array('Accept: ' . $this->_acceptType));
        $this->opt(CURLOPT_TIMEOUT          , $this->_timeout);
        $this->opt(CURLOPT_RETURNTRANSFER   , true);
        $this->opt(CURLOPT_MAXREDIRS        , $this->_maxRedirects);
        $this->opt(CURLOPT_FOLLOWLOCATION   , $this->_followlocation);
        $this->opt(CURLOPT_COOKIEJAR        , $this->_cookieFileLocation);
        $this->opt(CURLOPT_COOKIEFILE       , $this->_cookieFileLocation);
        $this->opt(CURLOPT_USERAGENT        , $this->_useragent);
        $this->opt(CURLOPT_REFERER          , $this->_referer);
        $this->opt(CURLOPT_POST             , $this->_post);
        $this->opt(CURLOPT_HEADER           , $this->_includeHeader);
        $this->opt(CURLOPT_SSL_VERIFYPEER   , false);

        $this->setAuth();

         if($this->_post)
         {
             $this->opt(CURLOPT_POSTFIELDS, $this->_postFields);
         }

         if($this->_noBody)
         {
             $this->opt(CURLOPT_NOBODY        ,true);
         }

         if ($useProxy && count($this->_proxy)>0)
         {
            $t = rand(0, count($this->_proxy) - 1);
            $proxies     = explode(":",$this->_proxy[$t]);
            $server     = isset($proxies[0])?$proxies[0]:'';
            $port         = isset($proxies[1])?$proxies[1]:'80';
             //$this->opt(CURLOPT_HTTPPROXYTUNNEL, false);
             $this->opt(CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
             $this->opt(CURLOPT_PROXY, $server);
             $this->opt(CURLOPT_PROXYPORT, $port);
         }

         if($this->_isAjax)
         {
            $headers[] = 'X-Requested-With: XMLHttpRequest';
            $headers[] = 'Keep-Alive: 300';
            $headers[] = 'Connection: Keep-Alive';
            $headers[] = 'X-Requested-With: XMLHttpRequest';
            $headers[] = 'X-Prototype-Version: 1.6.0.2';
            $headers[] = 'Accept-Language: en-gb,en;q=0.5';
            $headers[] = "Accept-Encoding: gzip,deflate";
            $headers[] = "Accept: text/javascript, text/html, application/xml, text/xml, */*";
            $headers[] = "Content-Type: application/x-www-form-urlencoded; charset=UTF-8";
            $headers[] = 'Cookie: _smasher_session=3bcfb10c97b1043869c805dc92b7c6aa';
            $this->opt( CURLOPT_HTTPHEADERS    , $headers);
         }

         curl_setopt_array($this->s, $this->options);

         $this->_webpage        = curl_exec($this->s);
         $this->_responseInfo   = curl_getinfo($this->s);
         $this->_status         = curl_getinfo($this->s,CURLINFO_HTTP_CODE);
         $this->_respurl        = curl_getinfo($this->s,CURLINFO_EFFECTIVE_URL);
         $this->_err            = curl_errno( $this->s );
         $this->_msg            = curl_error( $this->s );

         if (isset($fh) && is_resource($fh)) {
            fclose($fh);
         }
         $this->close();
     }

    /**
     * Close the curl object
     *
     * @return void
     */
     public function close(){
        curl_close($this->s);
     }

    /**
     * Flush the curl object
     *
     * @return void
     */
     private function __flush(){
         $this->options = array();    // reset options;
     }

     /**
     * Return the Http status received in the last execution of the curl
     *
     * @return int
     */
     public function getHttpStatus()
     {
       return $this->_status;
     }

    /**
     * Return the Http URL received in the last execution of the curl
     *
     * @return string
     */
     public function getHttpUrl()
     {
       return $this->_respurl;
     }

    /**
     * Return the Http Error received in the last execution of the curl
     *
     * @return string
     */
     public function getHttpErr()
     {
       return $this->_err;
     }

    /**
     * Return the Http error message received in the last execution of the curl
     *
     * @return string
     */
     public function getHttpMsg()
     {
       return $this->_msg;
     }

    /**
     * Magic Isset
     *
     * @param string $property Property name
     *
     * @return boolean
     */
     final public function __isset($property)
     {
       $property = '_'.$property;
       if (isset($this->$property)) {
           return true;
       }
     }

    /**
     * Get Property
     *
     * @param string $property Property name
     *
     * @return mixed
     */
     final private function getProperty($property)
     {
        $value = null;

        $methodName = 'getVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            $value = call_user_func(array($this, $methodName));
        } else {
               $property = '_'.$property;
            if (isset($this->$property)) {
                $value = $this->$property;
            }
        }

        return $value;
     }

    /**
     * Set Property
     *
     * @param string $property Property name
     * @param mixed $value Property value
     *
     * @return self
     */
     final private function setProperty($property, $value)
     {
        $methodName = 'setVal' . ucwords($property);
        if(method_exists($this, $methodName)) {
            call_user_func(array($this, $methodName), $value);
        } else {
               $property = '_'.$property;
            if (isset($this->$property)) {
                $this->$property = $value;
            }
        }

        return $this;
     }



    /**
     * Display all the infor received in the last execution of the curl
     *
     * @return void
     */
     public function displayResponce()
     {
       echo 'Status :'.$this->_status.'<br>';
       echo 'Url :'.$this->_respurl.'<br>';
       echo 'Error :'.$this->_err.'<br>';
       echo 'Message :'.$this->_msg.'<br>';
       echo 'Info : <pre>'; print_r($this->_responseInfo); echo '</pre><br>';
       echo 'Page :'.$this->_webpage.'<br>';
     }

    /**
     * Return the page received in the last execution of the curl
     *
     * @return string
     */
    public function __toString(){
        if($this->_webpage) {
            return $this->_webpage;
        } else {
            return '';
        }
    }

    /**
     * Display the page received in the last execution of the curl
     *
     * @return string
     */
    public function  displayPage(){
           $content_type = isset($this->_responseInfo['content_type'])?$this->_responseInfo['content_type']:'text/html';
        header('Content-type: ' .$content_type);
        if($this->_webpage) {
            echo $this->_webpage;
        } else {
            echo '';
        }
    }

    /**
     * Close the curl object and delete the cookie file if exists
     *
     * @return void
     */
    function __destruct() {
        if (is_resource($this->s)) {
            curl_close($this->s);
        }
        if (!$this->_cookiekeep && is_file($this->_cookieFileLocation)) {
            unlink($this->_cookieFileLocation);
        }
    }

}
?>