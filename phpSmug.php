<?php 
/** 
 * phpSmug - phpSmug is a PHP wrapper class for the SmugMug API. The intention 
 *		     of this class is to allow PHP application developers to quickly 
 *			 and easily interact with the SmugMug API in their applications, 
 *			 without having to worry about the finer details of the API.
 *
 * @author Colin Seymour <lildood@gmail.com>
 * @version 2.1
 * @package phpSmug
 * @license LGPL 3 {@link http://www.gnu.org/copyleft/lgpl.html}
 *
 * Released under GNU Lesser General Public License Version 3({@link http://www.gnu.org/copyleft/lgpl.html})
 *
 * For more information about the class and upcoming tools and toys using it,
 * visit {@link http://phpsmug.com/}.
 *
 * For installation and usage instructions, open the README.txt file 
 * packaged with this class. If you don't have a copy, you can refer to the
 * documentation at:
 * 
 *          {@link http://phpsmug.com/docs/}
 * 
 * phpSmug is inspired by phpFlickr 2.1.0 ({@link http://www.phpflickr.com}) by Dan Coulter
 **/

/** 
 * Decide which include path delimiter to use.  Windows should be using a semi-colon
 * and everything else should be using a colon.  If this isn't working on your system,
 * comment out this if statement and manually set the correct value into $path_delimiter.
 * 
 * @var string
 **/
$path_delimiter = (strpos(__FILE__, ':') !== false) ? ';' : ':';

/**
 *  This will add the packaged PEAR files into the include path for PHP, allowing you
 * to use them transparently.  This will prefer officially installed PEAR files if you
 * have them.  If you want to prefer the packaged files (there shouldn't be any reason
 * to), swap the two elements around the $path_delimiter variable.  If you don't have
 * the PEAR packages installed, you can leave this like it is and move on.
 **/
ini_set('include_path', ini_get('include_path') . $path_delimiter . dirname(__FILE__) . '/PEAR');

/**
 * Forcing a level of logging that does NOT include E_STRICT.
 * Unfortunately PEAR and it's modules are not obliged to meet E_STRICT levels in
 * PHP 5 yet as they still need to remain backwardly compatible with PHP 4. This is 
 * likely to change when PEAR 2 is released.  Until then we force a lower log level
 * just incase phpSmug is used within an application that uses E_STRICT.
 * phpSmug.php itself is E_STRICT compliant, so it's only PEAR that's holding us back.
 **/
error_reporting(E_ALL | E_NOTICE);

/**
 * phpSmug - all of the phpSmug functionality is provided in this class
 *
 * @package phpSmug
 **/
class phpSmug {
	var $version = '2.1';
	var $cacheType = FALSE;
	var $SessionID;
	var $loginType;
	var $OAuthSecret;
	var $oauth_signature_method;
	var $cache_expire = 3600;
	var $oauth_token_secret;
	var $oauth_token;
	var $mode;
	
	/**
     * When your database cache table hits this many rows, a cleanup
     * will occur to get rid of all of the old rows and cleanup the
     * garbage in the table.  For most personal apps, 1000 rows should
     * be more than enough.  If your site gets hit by a lot of traffic
     * or you have a lot of disk space to spare, bump this number up.
     * You should try to set it high enough that the cleanup only
     * happens every once in a while, so this will depend on the growth
     * of your table.
     * 
     * @var integer
     **/
    var $max_cache_rows = 1000;
	
	/**
	 * Constructor to set up a phpSmug instance.
	 * 
	 * The Application Name (AppName) is not obligatory, but it helps 
	 * SmugMug diagnose any problems users of your application may encounter.
	 * If you're going to use this, please use a string and include your
	 * version number and URL as follows.
	 * For example "My Cool App/1.0 (http://my.url.com)"
	 *
	 * The API Key must be set before any calls can be made.  You can
     * get your own at {@link http://www.smugmug.com/hack/apikeys}
     * 
     * By default phpSmug will use the latest stable API endpoint, but 
     * you can over-ride this when instantiating the instance.
	 *
	 * @return void
	 * @param string $APIKey SmugMug API key. You can get your own from {@link http://www.smugmug.com/hack/apikeys}
	 * @param string $OAuthSecret SmugMug OAuth Secret. This is only needed if you wish to use OAuth for authentication. Do NOT include this parameter if you are NOT using OAuth.
	 * @param string $AppName (Optional) Name and version information of your application in the form "AppName/version (URI)" e.g. "My Cool App/1.0 (http://my.url.com)".  This isn't obligatory, but it helps SmugMug diagnose any problems users of your application may encounter.
	 * @param string $APIVer (Optional) API endpoint you wish to use. Defaults to 1.2.0
	 **/
	function __construct()
	{
		$args = phpSmug::processArgs(func_get_args());
        $this->APIKey = $args['APIKey'];
		$this->APIVer = (array_key_exists('APIVer', $args)) ? $args['APIVer'] : '1.2.0';
		if (array_key_exists('OAuthSecret', $args)) {
			$this->OAuthSecret = $args['OAuthSecret'];
			// Force 1.2.2 endpoint as OAuth is being used
			$this->APIVer = '1.2.2';
		}

		// Set the Application Name
		$this->AppName = (array_key_exists('AppName', $args)) ?  $args['AppName'] : 'Unknown Application';

        // All calls to the API are done via the POST method using the PEAR::HTTP_Request package.
        require_once 'HTTP/Request.php';
        $this->req = new HTTP_Request();
        $this->req->setMethod(HTTP_REQUEST_METHOD_POST);
		$this->req->addHeader('User-Agent', "{$this->AppName} using phpSmug/{$this->version}");
    }
	
	/**
	 * General debug function used for testing and development of phpSmug. 
	 *
	 * Feel free to use this in your own application development.
	 *
	 * @return string
	 * @param mixed $var Any string, object or array you want to display
	 * @static
	 **/
	static function debug($var)
	{
		echo '<pre>Debug:';
		if (is_array($var) || is_object($var)) { print_r($var); } else { echo $var; }
		echo '</pre>';	
	}
	
	/**
	 * Function enables caching.
	 *
	 * @access public
	 * @return TRUE|string Returns TRUE is caching is enabled successfully, else returns an error and disable caching.
	 * @param string $type The type of cache to use. It must be either "db" (for database caching) or "fs" (for filesystem).
	 * @param string $dsn When using type "db", this must be a PEAR::DB connection string eg. "mysql://user:password@server/database".  When using type "fs", this must be a folder that the web server has write access to. Use absolute paths for best results.  Relative paths may have unexpected behavior when you include this.  They'll usually work, you'll just want to test them.
	 * @param string $cache_dir When using type "fs". this is the directory to use for caching. This directory must exist.
	 * @param integer $cache_expire Cache timeout in seconds. This defaults to 3600 seconds (1 hour) if not specified.
	 * @param string $table If using type "db", this is the database table name that will be used.  Defaults to "smugmug_cache".
	 **/
	public function enableCache()
	{
		$args = phpSmug::processArgs(func_get_args());
		$this->cacheType = $args['type'];
        
		$this->cache_expire = (array_key_exists('cache_expire', $args)) ? $args['cache_expire'] : '3600';
		$this->cache_table  = (array_key_exists('table', $args)) ? $args['table'] : 'smugmug_cache';

        if ($this->cacheType == 'db') {
    		require_once 'DB.php';
	        $db = DB::connect($args['dsn']);
			if (PEAR::isError($db)) {
				$this->cacheType = FALSE;
				return "CACHING DISABLED: {$db->getMessage()} ({$db->getCode()})";
			}
			$this->cache_db = $db;
            
            /*
             * If high performance is crucial, you can easily comment
             * out this query once you've created your database table.
             */
            $db->query("
                CREATE TABLE IF NOT EXISTS `$this->cache_table` (
                    `request` CHAR( 35 ) NOT NULL ,
                    `response` LONGTEXT NOT NULL ,
                    `expiration` DATETIME NOT NULL ,
                    INDEX ( `request` )
                ) TYPE = MYISAM");

            if ($db->getOne("SELECT COUNT(*) FROM $this->cache_table") > $this->max_cache_rows) {
                $db->query("DELETE FROM $this->cache_table WHERE expiration < DATE_SUB(NOW(), INTERVAL $this->cache_expire SECOND)");
                $db->query('OPTIMIZE TABLE ' . $this->cache_table);
            }

        } elseif ($this->cacheType ==  'fs') {
			if (file_exists($args['cache_dir']) && (is_dir($args['cache_dir']))) {
				$this->cache_dir = realpath($args['cache_dir']).'/phpSmug';
				if (is_writeable(realpath($args['cache_dir']))) {
					if (!is_dir($this->cache_dir)) {
						mkdir($this->cache_dir, 0755);
					}
					$dir = opendir($this->cache_dir);
                	while ($file = readdir($dir)) {
                    	if (substr($file, -2) == '.cache' && ((filemtime($this->cache_dir . '/' . $file) + $this->cache_expire) < time()) ) {
                        	unlink($this->cache_dir . '/' . $file);
                    	}
                	}
				} else {
					$this->cacheType = FALSE;
					return "CACHING DISABLED: Cache Directory \"".$args['cache_dir']."\" is not writeable.";
				}
			} else 	{
				$this->cacheType = FALSE;
				return "CACHING DISABLED: Cache Directory \"".$args['cache_dir']."\" doesn't exist, is a file or is not readable.";
			}
		}
		return TRUE;
    }

	/**
	 * 	Checks the database or filesystem for a cached result to the request.
	 *
	 * @access private
	 * @return string|FALSE Unparsed serialized PHP, or FALSE
	 * @param array $request Request to the SmugMug created by one of the later functions in phpSmug.
	 **/
    private function getCached($request)
	{
		$request['SessionID']       = ''; // Unset SessionID
		$request['oauth_nonce']     = '';     // --\
		$request['oauth_signature'] = '';  //    |-Unset OAuth info
		$request['oauth_timestamp'] = ''; // --/
       	$reqhash = md5(serialize($request).$this->loginType);
		$expire = (strpos($request['method'], 'login.with')) ? 21600 : $this->cache_expire;
        if ($this->cacheType == 'db') {
            $result = $this->cache_db->getOne('SELECT response FROM ' . $this->cache_table . ' WHERE request = ? AND DATE_SUB(NOW(), INTERVAL ' . (int) $expire . ' SECOND) < expiration', $reqhash);
			if (!empty($result)) {
                return $result;
            }
        } elseif ($this->cacheType == 'fs') {
            $file = $this->cache_dir . '/' . $reqhash . '.cache';
			if (file_exists($file) && ((filemtime($file) + $expire) > time()) ) {
					return file_get_contents($file);
            }
        }
    	return FALSE;
    }

	/**
	 * Caches the unparsed serialized PHP of a request. 
	 *
	 * @access private
	 * @return null|false
	 * @param array $request Request to the SmugMug created by one of the later functions in phpSmug.
	 * @param string $response Response from a successful request() method call.
	 **/
    private function cache($request, $response)
	{
		$request['SessionID']       = ''; // Unset SessionID
		$request['oauth_nonce']     = '';     // --\
		$request['oauth_signature'] = '';  //    |-Unset OAuth info
		$request['oauth_timestamp'] = ''; // --/
		$reqhash = md5(serialize($request).$this->loginType);
        if ($this->cacheType == 'db') {
            if ($this->cache_db->getOne("SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'")) {
                $sql = 'UPDATE ' . $this->cache_table . ' SET response = ?, expiration = ? WHERE request = ?';
				$this->cache_db->query($sql, array($response, strftime('%Y-%m-%d %H:%M:%S'), $reqhash));
            } else {
				$sql = "INSERT INTO " . $this->cache_table . " (request, response, expiration) VALUES ('$reqhash', '" . strtr($response, "'", "\'") . "', '" . strftime("%Y-%m-%d %H:%M:%S") . "')"; 
				$this->cache_db->query($sql);
            }
        } elseif ($this->cacheType == 'fs') {
            $file = $this->cache_dir . '/' . $reqhash . '.cache';
            $fstream = fopen($file, 'w');
            $result = fwrite($fstream,$response);
            fclose($fstream);
            return $result;
        }
        return FALSE;
    }

	/**
	 *  Forcefully clear the cache.
	 *
	 * This is useful if you've made changes to your SmugMug galleries and want
	 * to ensure the changes are reflected by your application immediately.
	 *
	 * @access public
	 * @return string|TRUE
	 * @since 1.1.7
	 **/
    public function clearCache()
	{
   		if ($this->cacheType == 'db') {
	    	$result = $this->cache_db->query('TRUNCATE ' . $this->cache_table);
	    	if (!empty($result)) {
	        	return $result;
	    	}
	   	} elseif ($this->cacheType == 'fs') {
            $dir = opendir($this->cache_dir);
	       	if ($dir) {
				foreach (glob($this->cache_dir."/*.cache") as $filename) {
					$result = unlink($filename);
				}
				return $result;
	       	}
	   	}
		return TRUE;
	}

	/**
	 * 	Sends a request to SmugMug's PHP endpoint via POST. If we're calling
	 *  one of the login.with* or auth.get* methods, we'll use the HTTPS end point to ensure
	 *  things are secure by default
	 *
	 * @access private
	 * @return string Serialized PHP response from SmugMug, or an error.
	 * @param string $command SmugMug API command to call in the request
	 * @param array $args optional Array of arguments that form the API call
	 * @param boolean $nocache Set whether the call should be cached or not. This isn't actually used, so may be deprecated in the future.
	 **/
	private function request($command, $args = array(), $nocache = FALSE)
	{
		$this->req->clearPostData();
        
		if ((strpos($command, 'login.with')) || ((strpos($command, 'auth.get')) && $this->oauth_signature_method == 'PLAINTEXT')) {
			$proto = "https";
		} else {
			$proto = "http";
			if ((isset($this->SessionID) && is_null($this->SessionID)) && (!strpos($command, 'login.anonymously')) && !$this->OAuthSecret) {
				throw new Exception('Not authenticated. No Session ID or OAuth Token.  Please login or provide an OAuth token.');
			}
		}
		
		$this->req->setURL("$proto://api.smugmug.com/services/api/php/{$this->APIVer}/");
		
        if (substr($command,0,8) != 'smugmug.') {
            $command = 'smugmug.' . $command;
        }

		$defaultArgs = array('method' => $command,);
		if (is_null($this->OAuthSecret) || empty($this->OAuthSecret)) {
			// Use normal login methods
			$defaultArgs = array_merge($defaultArgs, array('APIKey' => $this->APIKey,
													'SessionID' => $this->SessionID,
													'Strict' => 0)
									   );
		} else {
			$this->loginType = 'oauth';
		}

        // Process arguments, including method and login data.
        $args = array_merge($defaultArgs, $args);
        ksort($args);

        if (!($this->response = $this->getCached($args)) || $nocache) {
            foreach ($args as $key => $data) {
                $this->req->addPostData($key, $data, FALSE);
            }
            
            //Send Requests - HTTP::Request doesn't raise Exceptions, so we must
			$response = $this->req->sendRequest();
			if(!PEAR::isError($response) && ($this->req->getResponseCode() == 200)) {
				$this->response = $this->req->getResponseBody();
				$this->cache($args, $this->response);
			} else {
				if ($this->req->getResponseCode() && $this->req->getResponseCode() != 200) {
					$msg = 'Request failed. HTTP Reason: '.$this->req->getResponseReason();
					$code = $this->req->getResponseCode();
				} else {
					$msg = 'Request failed: '.$response->getMessage();
					$code = $response->getCode();
				}
				throw new Exception($msg, $code);
			}
		}

		$this->parsed_response = unserialize($this->response);
		if ($this->parsed_response['stat'] == 'fail') {
			$this->error_code = $this->parsed_response['code'];
            $this->error_msg = $this->parsed_response['message'];
			$this->parsed_response = FALSE;
			throw new Exception("SmugMug API Error for method {$command}: {$this->error_msg}", $this->error_code);
		} else {
			$this->error_code = FALSE;
            $this->error_msg = FALSE;

			// The login calls don't return the mode because you can't login if SmugMug is in read-only mode.
			if (isset($this->parsed_response['mode'])) {
				$this->mode = $this->parsed_response['mode'];
			}
		}
		return $this->response;
    }
	
	/**
	 * Set a proxy for all phpSmug calls
	 *
	 * @access public
	 * @return void
	 * @param string $server Proxy server
	 * @param integer $port Proxy server port
	 **/
    public function setProxy()
	{
		$args = phpSmug::processArgs(func_get_args());
        $this->req->setProxy($args['server'], $args['port']);
    }

	/**
	 * Set Token and Token Secret for use by other methods in phpSmug.
	 *
	 * Use this method to pull in the token and token secret obtained during 
	 * the OAuth authorisation process.
	 *
	 * If OAuth is being used, this method MUST be called so phpSmug knows about
	 * the token and token secret.
	 *
	 * NOTE: It's up to the application developer using phpSmug to store the Access
	 * token and token secret in a location convenient for their application.
	 * phpSmug can not do this as all storage and caching done by phpSmug is 
	 * of a temporary nature.
	 *
	 * @access public
	 * @return void
	 * @param string $id Token ID returned by auth_getAccessToken()
	 * @param string $Secret Token secret returned by auth_getAccessToken()
	 **/
	public function setToken()
	{
		 $args = phpSmug::processArgs(func_get_args());
		 $this->oauth_token = $args['id'];
		 $this->oauth_token_secret = $args['Secret'];
	}
	 
	/**
	 * Single login function for all non-OAuth logins.
	 * 
	 * I've created this function to try and get things consistent across the 
	 * entire phpSmug 2.x functionality.  
	 * 
	 * This method will determine the login type from the arguments provided. If 
	 * no arguments are provide, anonymous login will be used.
	 *
	 * @access public
	 * @return array|false
	 * @param string $EmailAddress The user's email address
	 * @param string $Password The user's password.
	 * @param string $UserID The user's ID obtained from a previous login using EmailAddress/Password
	 * @param string $PasswordHash The user's password hash obtained from a previous login using EmailAddress/Password
	 * @uses request
	 **/
	public function login()
	{
		if (func_get_args()) {
			$args = phpSmug::processArgs(func_get_args());
			if (array_key_exists('EmailAddress', $args)) {
				// Login with password
				$this->request('smugmug.login.withPassword', array('EmailAddress' => $args['EmailAddress'], 'Password' => $args['Password']));
			} else if (array_key_exists('UserID', $args)) {
				// Login with hash
				$this->request('smugmug.login.withHash', array('UserID' => $args['UserID'], 'PasswordHash' => $args['PasswordHash']));
			}
			$this->loginType = 'authd';
			
		} else {
			// Anonymous login
			$this->loginType = 'anon';
			$this->request('smugmug.login.anonymously');
		}
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}
	
	/**
	 * 	I break away from the standard API here as recommended by SmugMug at
	 * {@link http://wiki.smugmug.com/display/SmugMug/smugmug.images.upload+1.2.0}.
	 *
	 * I've chosen to go with the HTTP PUT method as it is quicker, simpler
	 * and more reliable than using the API or POST methods.
	 * 
	 * @access public
	 * @return array|false
	 * @param integer $AlbumID The AlbumID the image is to be uploaded to
	 * @param string $File The path to the local file that is being uploaded
	 * @param string $FileName (Optional) The filename to give the file on upload
	 * @param mixed $arguments (Optional) Additional arguments. See SmugMug API documentation.
	 * @uses request
	 * @link http://wiki.smugmug.com/display/SmugMug/Uploading 
	 **/
	public function images_upload()
	{
		$args = phpSmug::processArgs(func_get_args());
		if (!array_key_exists('File', $args)) {
			throw new Exception('No upload file specified.');
		}
		
		// Set FileName, if one isn't provided in the method call
		if (!array_key_exists('FileName', $args)) {
			$args['FileName'] = basename($args['File']);
		}
		// OAuth Stuff
		if ($this->OAuthSecret) {
			$sig = $this->generate_signature('Upload', array('FileName' => $args['FileName']));
		}
		
		if (is_file($args['File'])) {
			$fp = fopen($args['File'], 'r');
			$data = fread($fp, filesize($args['File']));
			fclose($fp);
		} else {
			throw new Exception("File doesn't exist: {$args['File']}");
		}

		$upload_req = new HTTP_Request();
        $upload_req->setMethod(HTTP_REQUEST_METHOD_PUT);
		$upload_req->setHttpVer(HTTP_REQUEST_HTTP_VER_1_1);
		$upload_req->clearPostData();
		
		$upload_req->addHeader('User-Agent', "{$this->AppName} using phpSmug/{$this->version}");
		$upload_req->addHeader('Content-MD5', md5_file($args['File']));
		$upload_req->addHeader('Connection', 'keep-alive');

		if ($this->loginType == 'authd') { 
			$upload_req->addHeader('X-Smug-SessionID', $this->SessionID);
		} else {
			$upload_req->addHeader('Authorization', 'OAuth realm="http://api.smugmug.com/",
				oauth_consumer_key="'.$this->APIKey.'",
				oauth_token="'.$this->oauth_token.'",
				oauth_signature_method="'.$this->oauth_signature_method.'",
				oauth_signature="'.urlencode($sig).'",
				oauth_timestamp="'.$this->oauth_timestamp.'",
				oauth_version="1.0",
				oauth_nonce="'.$this->oauth_nonce.'"');
		}
			
		$upload_req->addHeader('X-Smug-Version', $this->APIVer);
		$upload_req->addHeader('X-Smug-ResponseType', 'PHP');
		$upload_req->addHeader('X-Smug-AlbumID', $args['AlbumID']);
		$upload_req->addHeader('X-Smug-Filename', basename($args['FileName'])); // This is actually optional, but we may as well use what we're given
		
		/* Optional Headers */
		(isset($args['ImageID'])) ? $upload_req->addHeader('X-Smug-ImageID', $args['ImageID']) : false;
		(isset($args['Caption'])) ? $upload_req->addHeader('X-Smug-Caption', $args['Caption']) : false;
		(isset($args['Keywords'])) ? $upload_req->addHeader('X-Smug-Keywords', $args['Keywords']) : false;
		(isset($args['Latitude'])) ? $upload_req->addHeader('X-Smug-Latitude', $args['Latitude']) : false;
		(isset($args['Longitude'])) ? $upload_req->addHeader('X-Smug-Longitude', $args['Longitude']) : false;
		(isset($args['Altitude'])) ? $upload_req->addHeader('X-Smug-Altitude', $args['Altitude']) : false;

		$upload_req->setURL('http://upload.smugmug.com/'.$args['FileName']);
		$upload_req->setBody($data);

        //Send Requests - HTTP::Request doesn't raise Exceptions, so we must
		$response = $upload_req->sendRequest();
		if(!PEAR::isError($response) && ($upload_req->getResponseCode() == 200)) {
			$this->response = $upload_req->getResponseBody();
		} else {
			if ($upload_req->getResponseCode() && $upload_req->getResponseCode() != 200) {
				$msg = 'Upload failed. HTTP Reason: '.$upload_req->getResponseReason();
				$code = $upload_req->getResponseCode();
			} else {
				$msg = 'Upload failed: '.$response->getMessage();
				$code = $response->getCode();
			}
			throw new Exception($msg, $code);
		}
		
		// For some reason the return string is formatted with \n and extra space chars.  Remove these.
		$replace = array('\n', '\t', '  ');
		$this->response = str_replace($replace, '', $this->response);
		$this->parsed_response = unserialize($this->response);
		
		if ($this->parsed_response['stat'] == 'fail') {
			$this->error_code = $this->parsed_response['code'];
            $this->error_msg = $this->parsed_response['message'];
			$this->parsed_response = FALSE;
			throw new Exception("SmugMug API Error for method image_upload: {$this->error_msg}", $this->error_code);
		} else {
			$this->error_code = FALSE;
            $this->error_msg = FALSE;
            $this->cache($args, $this->response);
		}
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * Dynamic method handler.  This function handles all SmugMug method calls
	 * not explicitly implemented by phpSmug.
	 * 
 	 * @access public
	 * @return array|string|TRUE
	 * @uses request
	 * @param string $method The SmugMug method you want to call, but with "." replaced by "_"
	 * @param mixed $arguments The params to be passed to the relevant API method. See SmugMug API docs for more details.
	 **/
	public function __call($method, $arguments)
	{
		$method = strtr($method, '_', '.');
		$args = array();
		foreach ($arguments as $arg) {
			if (is_array($arg)) {
				$args = array_merge($args, $arg);
			} else {
				$exp = explode('=', $arg, 2);
                $args[$exp[0]] = $exp[1];
            }
		}
		if ($this->OAuthSecret) {
			$sig = $this->generate_signature($method, $args);
			$oauth_params = array (
				'oauth_version'             => '1.0',
				'oauth_nonce'               => $this->oauth_nonce,
				'oauth_timestamp'           => $this->oauth_timestamp,
				'oauth_consumer_key'        => $this->APIKey,
				'oauth_signature_method'    => $this->oauth_signature_method,
				'oauth_signature'           => $sig
				);
			
			// Only getRequestToken won't have a token when using OAuth
			if ($method != 'auth.getRequestToken') {
				$oauth_params['oauth_token'] = $this->oauth_token;
			}
			$args = array_merge($args, $oauth_params);
		}
		$this->request($method, $args);

		// pop off the "stat", "mode" and "method" parts of the array as we don't need them anymore.
        if (is_array($this->parsed_response)) $output = array_pop($this->parsed_response);
		$output = (count($output) == '1' && is_array($output)) ? array_shift($output) : $output;
		/* Automatically set token if calling getRequestToken */
		if ($method == 'auth.getRequestToken') {
			$this->setToken($output);
		}

		return (is_string($output) && strstr($output, 'smugmug.')) ? NULL : $output;
	}
	
	 /**
	  * Return the authorisation URL.
	  *
	  * @access public
	  * @return string 
	  * @param string $Access The required level of access. Defaults to "Public"
	  * @param string $Permissions The required permissions.  Defaults to "Read"
	  **/
	 public function authorize()
	{
		 $args = phpSmug::processArgs(func_get_args());
		 $perms = (array_key_exists('Permissions', $args)) ? $args['Permissions'] : 'Public';
		 $access = (array_key_exists('Access', $args)) ? $args['Access'] : 'Read';
 		 return "http://api.smugmug.com/services/oauth/authorize.mg?Access=$access&Permissions=$perms&oauth_token={$this->oauth_token}";
	 }
	 

	 /**
	  * Static function to encode a string according to RFC3986.
	  *
	  * This is a requirement of implementing OAuth
	  *
	  * @static
	  * @access private
	  * @return string
	  * @param string $string The string requiring encoding
	  **/
	 private static function urlencodeRFC3986($string)
	 {
		return str_replace('%7E', '~', rawurlencode($string));
	 }

	 /**
	  * Method that generates the OAuth signature
	  *
	  * In order for this method to correctly generate a signature, setToken()
	  * MUST be called to set the token and token secret within the instance of
	  * phpSmug.
	  *
	  * @return string
	  * @access private
	  * @param string $apicall The API method.
	  * @param mixed $apiargs The arguments passed to the API method.
	  **/
	 private function generate_signature($apicall, $apiargs = NULL)
	 {
		$this->oauth_timestamp = time();
		$this->oauth_nonce = md5(time() . mt_rand());

		if ($apicall != 'Upload') {
			if (substr($apicall,0,8) != 'smugmug.') {
				$apicall = 'smugmug.' . $apicall;
			}
		}
		if ($this->oauth_signature_method == 'PLAINTEXT') {
			return phpSmug::urlencodeRFC3986($this->OAuthSecret).'&'.phpSmug::urlencodeRFC3986($this->oauth_token_secret);
		} else {
			$this->oauth_signature_method = 'HMAC-SHA1';
			$encKey = phpSmug::urlencodeRFC3986($this->OAuthSecret) . '&' . phpSmug::urlencodeRFC3986($this->oauth_token_secret);
			$endpoint = ($apicall == 'Upload') ? 'http://upload.smugmug.com/'.$apiargs['FileName'] : 'http://api.smugmug.com/services/api/php/'.$this->APIVer.'/';
			$method = ($apicall == 'Upload') ? 'PUT' : 'POST';
			$params = array (
				'oauth_version'             => '1.0',
				'oauth_nonce'               => $this->oauth_nonce,
				'oauth_timestamp'           => $this->oauth_timestamp,
				'oauth_consumer_key'        => $this->APIKey,
				'oauth_signature_method'    => $this->oauth_signature_method
				);
			if ($apicall != 'Upload') $params = array_merge($params, array('method' => $apicall));
			$params = (!empty($this->oauth_token)) ? array_merge($params, array('oauth_token' => $this->oauth_token)) : $params;
			if ($apicall != 'Upload') $params = (!empty($apiargs)) ? array_merge($params, $apiargs) : $params;
		    $keys = array_map(array('phpSmug', 'urlencodeRFC3986'), array_keys($params));
		    $values = array_map(array('phpSmug', 'urlencodeRFC3986'), array_values($params));
		    $params = array_combine($keys, $values);
		    // Sort by keys (natsort)
		    uksort($params, 'strnatcmp');
			$pairs = array();
			foreach ($params as $key=>$value ) {
			  if (is_array($value)) {
			    natsort($value);
			    foreach ($value as $v2) {
					$pairs[] = "$key=$v2";
			    }
			  } else {
			    $pairs[] = "$key=$value";
			  }
			}

			$string = implode('&', $pairs);
			$base_string = $method . '&' . phpSmug::urlencodeRFC3986($endpoint) . '&' .  phpSmug::urlencodeRFC3986($string);
			$sig = base64_encode( hash_hmac('sha1', $base_string, $encKey, true));
			return $sig;
		}
	 }
	  
	 /**
	  * Process arguments passed to method
	  *
	  * @static
	  * @return array
	  * @param array Arguments taken from a function by func_get_args()
	  * @access private
	  **/
	 private static function processArgs($arguments)
	 {
		$args = array();
		foreach ($arguments as $arg) {
			if (is_array($arg)) {
				$args = array_merge($args, $arg);
			} else {
				$exp = explode('=', $arg, 2);
				$args[$exp[0]] = $exp[1];
			}
		}
		return $args;
	  }
	   
}
?>
