<?php 
/** 
 * phpSmug - phpSmug is a PHP wrapper class for the SmugMug API. The intention 
 *		     of this class is to allow PHP application developers to quickly 
 *			 and easily interact with the SmugMug API in their applications, 
 *			 without having to worry about the finer details of the API.
 *
 * @author Colin Seymour <lildood@gmail.com>
 * @version 3.4
 * @package phpSmug
 * @license GPL 3 {@link http://www.gnu.org/copyleft/gpl.html}
 * @copyright Copyright (c) 2008 Colin Seymour
 * 
 * This file is part of phpSmug.
 *
 * phpSmug is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * phpSmug is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with phpSmug.  If not, see <http://www.gnu.org/licenses/>.
 *
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
 * 
 * Please help support the maintenance and development of phpSmug by making
 * a donation ({@link http://phpsmug.com/donate/}).
 **/

/**
 * We define our own exception so application developers can differentiate these
 * from other exceptions.
 */
class PhpSmugException extends Exception {}

/**
 * phpSmug - all of the phpSmug functionality is provided in this class
 *
 * @package phpSmug
 **/
class phpSmug {
	static $version = '3.4';
	private $cacheType = FALSE;
	var $SessionID;
	var $loginType;
	var $OAuthSecret;
	var $oauth_signature_method;
	var $cache_expire = 3600;
	var $oauth_token_secret;
	var $oauth_token;
	var $mode;
	private $secure = false;
	private $req;
	private $adapter = 'curl';
	
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
	 * @return	void
	 * @param	string	$APIKey SmugMug API key. You can get your own from {@link http://www.smugmug.com/hack/apikeys}
	 * @param	string	$OAuthSecret SmugMug OAuth Secret. This is only needed if
	 *					you wish to use OAuth for authentication. Do NOT include
	 *					this parameter if you are NOT using OAuth.
	 * @param	string	$AppName (Optional) Name and version information of your
	 *					application in the form "AppName/version (URI)"
	 *					e.g. "My Cool App/1.0 (http://my.url.com)".
	 *					This isn't obligatory, but it helps SmugMug diagnose any
	 *					problems users of your application may encounter.
	 * @param	string	$APIVer (Optional) API endpoint you wish to use.
	 *					Defaults to 1.2.2
	 **/
	function __construct()
	{
		$args = phpSmug::processArgs( func_get_args() );
        $this->APIKey = $args['APIKey'];
		if ( array_key_exists( 'OAuthSecret', $args ) ) {
			$this->OAuthSecret = $args['OAuthSecret'];
			// Force 1.2.2 endpoint as OAuth is being used.
			$this->APIVer = '1.2.2';
		}
		// Over ride the above if an APIVer is provided.  This is only needed to keep support for 1.2.1 and lower APIs.
		$this->APIVer = ( array_key_exists( 'APIVer', $args ) ) ? $args['APIVer'] : '1.2.2';

		// Set the Application Name
		$this->AppName = ( array_key_exists( 'AppName', $args ) ) ?  $args['AppName'] : 'Unknown Application';

		// All calls to the API are done via POST using my own constructed httpRequest class
		$this->req = new httpRequest();
		$this->req->setConfig( array( 'adapter' => $this->adapter, 'follow_redirects' => TRUE, 'max_redirects' => 3, 'ssl_verify_peer' => FALSE, 'ssl_verify_host' => FALSE, 'connect_timeout' => 60 ) );
		$this->req->setHeader( array( 'User-Agent' => "{$this->AppName} using phpSmug/" . phpSmug::$version, 'Content-Type' => 'application/x-www-form-urlencoded' ) );
    }
	
	/**
	 * General debug function used for testing and development of phpSmug.
	 *
	 * Feel free to use this in your own application development.
	 *
	 * @static
	 * @access	public
	 * @param	mixed		$var Any string, object or array you want to display
	 * @param	boolean		$echo Print the output or not.  This is only really used
	 *						for unit testing.
	 * @return string
	 **/
	public static function debug( $var, $echo = TRUE )
	{
		ob_start();
		$out = '';
		echo '<pre>Debug:';
		if ( is_array( $var ) || is_object( $var ) ) { print_r( $var ); } else { echo $var; }
		echo '</pre>';
		if ( $echo ) { ob_end_flush(); } else { $out = ob_get_clean(); }
		return $out;
	}
	
	/**
	 * Function enables caching.
	 *
	 * Params can be passed as an associative array or a set of param=value strings.
	 *
	 * phpSmug uses the PEAR MDB2 module to interact with the database. You will
	 * need to install PEAR, the MDB2 module and corresponding database driver yourself
	 * in order to use database caching.
	 *
	 * @access	public
	 * @param	string		$type The type of cache to use. It must be either
	 *						"db" (for database caching) or "fs" (for filesystem).
	 * @param	string		$dsn When using type "db", this must be a PEAR::MDB2
	 *						connection string eg. "mysql://user:password@server/database".
	 *						This option is not used for type "fs".
	 * @param	string		$cache_dir When using type "fs", this is the directory
	 *						to use for caching. This directory must exist and be
	 *						writable by the web server. Use absolute paths for
	 *						best results.  Relative paths may have unexpected
	 *						behavior when you include this.  They'll usually work,
	 *						you'll just want to test them.
	 * @param	integer		$cache_expire Cache timeout in seconds. This defaults
	 *						to 3600 seconds (1 hour) if not specified.
	 * @param	string		$table If using type "db", this is the database table
	 *						name that will be used.  Defaults to "phpsmug_cache".
	 * @return	mixed		Returns TRUE if caching is enabled successfully, else
	 *						returns an error and disables caching.
	 **/
	public function enableCache()
	{
		$args = phpSmug::processArgs( func_get_args() );
		$this->cacheType = $args['type'];

		$this->cache_expire = ( array_key_exists( 'cache_expire', $args ) ) ? $args['cache_expire'] : '3600';
		$this->cache_table  = ( array_key_exists( 'table', $args ) ) ? $args['table'] : 'phpsmug_cache';

        if ( $this->cacheType == 'db' ) {
    		require_once 'MDB2.php';

			$db =& MDB2::connect( $args['dsn'] );
			if ( PEAR::isError( $db ) ) {
				$this->cacheType = FALSE;
				return "CACHING DISABLED: {$db->getMessage()} {$db->getUserInfo()} ({$db->getCode()})";
			}
			$this->cache_db = $db;

			$options = array( 'comment' => 'phpSmug cache', 'charset' => 'utf8', 'collate' => 'utf8_unicode_ci' );
			$fields = array( 'request' => array( 'type' => 'text', 'length' => '35', 'notnull' => TRUE ),
							 'response' => array( 'type' => 'clob', 'notnull' => TRUE ),
							 'expiration' => array( 'type' => 'integer', 'notnull' => TRUE )
						   );
			$db->loadModule('Manager');
			$db->createTable( $this->cache_table, $fields, $options );
			$db->setOption('idxname_format', '%s'); // Make sure index name doesn't have the prefix
			$db->createIndex( $this->cache_table, 'request', array( 'fields' => array( 'request' => array() ) ) );

            if ( $db->queryOne( "SELECT COUNT(*) FROM $this->cache_table") > $this->max_cache_rows ) {
				$diff = time() - $this->cache_expire;
                $db->exec( "DELETE FROM {$this->cache_table} WHERE expiration < {$diff}" );
                $db->query( 'OPTIMIZE TABLE ' . $this->cache_table );
            }
        } elseif ( $this->cacheType ==  'fs' ) {
			if ( file_exists( $args['cache_dir'] ) && ( is_dir( $args['cache_dir'] ) ) ) {
				$this->cache_dir = realpath( $args['cache_dir'] ).'/phpSmug/';
				if ( is_writeable( realpath( $args['cache_dir'] ) ) ) {
					if ( !is_dir( $this->cache_dir ) ) {
						mkdir( $this->cache_dir, 0755 );
					}
					$dir = opendir( $this->cache_dir );
                	while ( $file = readdir( $dir ) ) {
                    	if ( substr( $file, -6 ) == '.cache' && ( ( filemtime( $this->cache_dir . '/' . $file ) + $this->cache_expire ) < time() ) ) {
                        	unlink( $this->cache_dir . '/' . $file );
                    	}
                	}
				} else {
					$this->cacheType = FALSE;
					return 'CACHING DISABLED: Cache Directory "'.$args['cache_dir'].'" is not writeable.';
				}
			} else 	{
				$this->cacheType = FALSE;
				return 'CACHING DISABLED: Cache Directory "'.$args['cache_dir'].'" doesn\'t exist, is a file or is not readable.';
			}
		}
		return (bool) TRUE;
    }

	/**
	 * 	Checks the database or filesystem for a cached result to the request.
	 *
	 * @access	private
	 * @return	mixed		Unparsed serialized PHP, or FALSE
	 * @param	array		$request Request to the SmugMug created by one of the later functions in phpSmug.
	 **/
    private function getCached( $request )
	{
		$request['SessionID']       = ''; // Unset SessionID
		$request['oauth_nonce']     = '';     // --\
		$request['oauth_signature'] = '';  //    |-Unset OAuth info
		$request['oauth_timestamp'] = ''; // --/
       	$reqhash = md5( serialize( $request ).$this->loginType );
		$expire = ( strpos( $request['method'], 'login.with' ) ) ? 21600 : $this->cache_expire;
		$diff = time() - $expire;

		if ( $this->cacheType == 'db' ) {
			$result = $this->cache_db->queryOne( 'SELECT response FROM ' . $this->cache_table . ' WHERE request = ' . $this->cache_db->quote( $reqhash ) . ' AND ' . $this->cache_db->quote( $diff ) . ' < expiration' );
			if ( PEAR::isError( $result ) ) {
				throw new PhpSmugException( $result );
			}
			if ( !empty( $result ) ) {
                return $result;
            }
        } elseif ( $this->cacheType == 'fs' ) {
            $file = $this->cache_dir . '/' . $reqhash . '.cache';
			if ( file_exists( $file ) && ( ( filemtime( $file ) + $expire ) > time() ) ) {
					return file_get_contents( $file );
            }
        }
    	return FALSE;
    }

	/**
	 * Caches the unparsed serialized PHP of a request. 
	 *
	 * @access	private
	 * @param	array		$request Request to the SmugMug created by one of the
	 *						later functions in phpSmug.
	 * @param	string		$response Response from a successful request() method
	 *						call.
	 * @return	null|TRUE
	 **/
    private function cache( $request, $response )
	{
		$request['SessionID']       = ''; // Unset SessionID
		$request['oauth_nonce']     = ''; // --\
		$request['oauth_signature'] = ''; //    |-Unset OAuth info
		$request['oauth_timestamp'] = ''; // --/
		if ( ! strpos( $request['method'], '.auth.' ) ) {
			$reqhash = md5( serialize( $request ).$this->loginType );
			if ( $this->cacheType == 'db' ) {
				if ( $this->cache_db->queryOne( "SELECT COUNT(*) FROM {$this->cache_table} WHERE request = '$reqhash'" ) ) {
					$sql = 'UPDATE ' . $this->cache_table . ' SET response = '. $this->cache_db->quote( $response ) . ', expiration = ' . $this->cache_db->quote( time() ) . ' WHERE request = ' . $this->cache_db->quote( $reqhash ) ;
					$result = $this->cache_db->exec( $sql );
				} else {
					$sql = 'INSERT INTO ' . $this->cache_table . ' (request, response, expiration) VALUES (' . $this->cache_db->quote( $reqhash ) .', ' . $this->cache_db->quote( strtr( $response, "'", "\'" ) ) . ', ' . $this->cache_db->quote( time() ) . ')';
					$result = $this->cache_db->exec( $sql );
				}
				if ( PEAR::isError( $result ) ) {
					// TODO: Create unit test for this
					throw new PhpSmugException( $result );
				}
				return $result;
			} elseif ( $this->cacheType == 'fs' ) {
				$file = $this->cache_dir . '/' . $reqhash . '.cache';
				$fstream = fopen( $file, 'w' );
				$result = fwrite( $fstream,$response );
				fclose( $fstream );
				return $result;
			}
		}
        return TRUE;
    }

	/**
	 * Forcefully clear the cache.
	 *
	 * This is useful if you've made changes to your SmugMug galleries and want
	 * to ensure the changes are reflected by your application immediately.
	 *
	 * @access	public
	 * @param	boolean		$delete Set to TRUE to delete the cache after
	 *						clearing it
	 * @return	boolean
	 * @since 1.1.7
	 **/
    public function clearCache( $delete = FALSE )
	{
		$result = FALSE;
   		if ( $this->cacheType == 'db' ) {
			if ( $delete ) {
				$result = $this->cache_db->exec( 'DROP TABLE ' . $this->cache_table );
			} else {
				$result = $this->cache_db->exec( 'DELETE FROM ' . $this->cache_table );
			}
			if ( ! PEAR::isError( $result ) ) {
				$result = TRUE;
			}
	   	} elseif ( $this->cacheType == 'fs' ) {
            $dir = opendir( $this->cache_dir );
	       	if ( $dir ) {
				foreach ( glob( $this->cache_dir."/*.cache" ) as $filename ) {
					$result = unlink( $filename );
				}
	       	}
			closedir( $dir );
			if ( $delete ) {
				$result = rmdir( $this->cache_dir );
			}
	   	}
		return (bool) $result;
	}

	/**
	 * 	Sends a request to SmugMug's PHP endpoint via POST. If we're calling
	 *  one of the login.with* or auth.get* methods, we'll use the HTTPS end point to ensure
	 *  things are secure by default
	 *
	 * @access	private
	 * @param	string		$command SmugMug API command to call in the request
	 * @param	array		$args optional Array of arguments that form the API call
	 * @return	string		Serialized PHP response from SmugMug, or an error.
	 **/
	private function request( $command, $args = array() )
	{
		if ( ( strpos( $command, 'login.with' ) || strpos( $command, 'Token' ) ) || ( $this->oauth_signature_method == 'PLAINTEXT' ) || $this->secure ) {
			$endpoint = "https://secure.smugmug.com/services/api/php/{$this->APIVer}/";
		} else {
			$endpoint = "http://api.smugmug.com/services/api/php/{$this->APIVer}/";
			if ( ( isset( $this->SessionID ) && is_null( $this->SessionID ) ) && ( !strpos( $command, 'login.anonymously' ) ) && !$this->OAuthSecret ) {
				throw new PhpSmugException( 'Not authenticated. No Session ID or OAuth Token.  Please login or provide an OAuth token.' );
			}
		}
		
		$this->req->setURL( $endpoint );
		
        if ( substr( $command,0,8 ) != 'smugmug.' ) {
            $command = 'smugmug.' . $command;
        }

		$defaultArgs = array( 'method' => $command, );
		if ( is_null( $this->OAuthSecret ) || empty( $this->OAuthSecret ) ) {
			// Use normal login methods
			$defaultArgs = array_merge( $defaultArgs, array( 'APIKey' => $this->APIKey,
													'SessionID' => $this->SessionID,
													'Strict' => 0 )
									   );
		} else {
			$this->loginType = 'oauth';
		}

        // Process arguments, including method and login data.
        $args = array_merge( $defaultArgs, $args );
		$keys = array_map( array( 'phpSmug', 'urlencodeRFC3986' ), array_keys( $args ) );
		$values = array_map( array( 'phpSmug', 'urlencodeRFC3986' ), array_values( $args ) );
		$args = array_combine( $keys, $values );
        ksort( $args );
		//
        if ( !( $this->response = $this->getCached( $args ) ) ) {
  			$this->req->setPostData( $args );
			$this->req->execute();
			$this->response = $this->req->getBody();
			$this->cache( $args, $this->response );
		}
		// TODO: Cater for SmugMug being in read-only mode better.  At the moment we throw and exception and don't allow things to continue.
		$this->parsed_response = unserialize($this->response);
		if ( $this->parsed_response['stat'] == 'fail' ) {
			$this->error_code = $this->parsed_response['code'];
            $this->error_msg = $this->parsed_response['message'];
			$this->parsed_response = FALSE;
			throw new PhpSmugException( "SmugMug API Error for method {$command}: {$this->error_msg}", $this->error_code );
		} else {
			$this->error_code = FALSE;
            $this->error_msg = FALSE;
			// The login calls don't return the mode because you can't login if SmugMug is in read-only mode.
			if ( isset( $this->parsed_response['mode'] ) ) {
				$this->mode = $this->parsed_response['mode'];
			}
		}
		return $this->response;
    }
	
	/**
	 * Set a proxy for all phpSmug calls.
	 *
	 * Params can be passed as an associative array or a set of param=value strings.
	 *
	 * @access	public
	 * @param	string		$server Proxy server
	 * @param	string		$port Proxy server port
	 * @param	string		$username (Optional) Proxy username
	 * @param	string		$password (Optional) Proxy password
	 * @param	string		$auth_scheme (Optional) Proxy authentication scheme.
	 *						Defaults to "basic". Other supported option is "digest".
	 * @return	void
	 **/
    public function setProxy()
	{
		$args = phpSmug::processArgs( func_get_args() );
		$this->proxy['server'] = $args['server'];
		$this->proxy['port'] = $args['port'];
		$this->proxy['username'] = ( isset( $args['username'] ) ) ? $args['username'] : '';
		$this->proxy['password'] = ( isset( $args['password'] ) ) ? $args['password'] : '';
		$this->proxy['auth_scheme'] = ( isset( $args['auth_scheme'] ) ) ? $args['auth_scheme'] : 'basic';
		$this->req->setConfig( array( 'proxy_host' => $this->proxy['server'],
									  'proxy_port' => $this->proxy['port'],
									  'proxy_user' => $this->proxy['username'],
									  'proxy_password' => $this->proxy['password'],
									  'proxy_auth_scheme' => $this->proxy['auth_scheme'] ) );
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
	 * @access	public
	 * @param	string		$id Token ID returned by auth_getAccessToken()
	 * @param	string		$Secret Token secret returned by auth_getAccessToken()
	 * @return	void
	 **/
	public function setToken()
	{
		 $args = phpSmug::processArgs( func_get_args() );
		 $this->oauth_token = $args['id'];
		 $this->oauth_token_secret = $args['Secret'];
	}

	/**
	 * Set the adapter.  
	 * 
	 * @access	public
	 * @param	string		$adapter Allowed options are 'curl' or 'socket'. Default is 'curl'
	 * @return	void
	 */
	public function setAdapter( $adapter )
	{
		$adapter = strtolower( $adapter );
		if ( $adapter == 'curl' || $adapter == 'socket' ) {
			$this->adapter = $adapter;
			$this->req->setAdapter( $adapter );
		}
	}
	
	/**
	 * Get the adapter.  This is primarily for unit testing
	 * 
	 * @access	public
	 * @return	string		Either 'socket' or 'curl'.
	 */
	public function getAdapter()
	{
		return $this->req->getAdapter();
	}
	
	/**
	 * Force the use of the secure/HTTPS API endpoint for ALL API calls, not just 
	 * those entailing authentication.
	 * 
	 * This is only implemented if authenticating using OAuth.
	 * 
	 * @access	public
	 * @return	void
	 */
	
	public function setSecureOnly()
	{
		if ( isset( $this->OAuthSecret ) ) {
			$this->secure = true;
		}
	}
	
	/**
	 * Single login function for all non-OAuth logins.
	 * 
	 * I've created this function to try and get things consistent across the 
	 * entire phpSmug functionality.  
	 * 
	 * This method will determine the login type from the arguments provided. If 
	 * no arguments are provide, anonymous login will be used.
	 *
	 * @access	public
	 * @param	string		$EmailAddress The user's email address
	 * @param	string		$Password The user's password.
	 * @param	string		$UserID The user's ID obtained from a previous login
	 *						using EmailAddress/Password
	 * @param	string		$PasswordHash The user's password hash obtained from
	 *						a previous login using EmailAddress/Password
	 * @uses	request
	 * @return	mixed		Returns the login response or FALSE.
	 **/
	public function login()
	{
		if ( func_get_args() ) {
			$args = phpSmug::processArgs( func_get_args() );
			if ( array_key_exists( 'EmailAddress', $args ) ) {
				// Login with password
				$this->request( 'smugmug.login.withPassword', array( 'EmailAddress' => $args['EmailAddress'], 'Password' => $args['Password'] ) );
			} else if ( array_key_exists( 'UserID', $args ) ) {
				// Login with hash
				$this->request( 'smugmug.login.withHash', array( 'UserID' => $args['UserID'], 'PasswordHash' => $args['PasswordHash'] ) );
			}
			$this->loginType = 'authd';
			
		} else {
			// Anonymous login
			$this->loginType = 'anon';
			$this->request( 'smugmug.login.anonymously' );
		}
		$this->SessionID = $this->parsed_response['Login']['Session']['id'];
		return $this->parsed_response ? $this->parsed_response['Login'] : FALSE;
	}

	/**
	 * Catch login_* methods and direct them to the single login() method.
	 *
	 * This prevents these methods being passed to __call() and the resulting
	 * cryptic and tough troubleshooting that would ensue for users who don't use
	 * the login() method. Now they use it, even if they don't know about it.
	 *
	 * @access	public
	 * @uses	login
	 */
	public function login_anonymously()
	{
		return $this->login();
	}

	public function login_withHash()
	{
		$args = phpSmug::processArgs( func_get_args() );
		return $this->login( $args );
	}

	public function login_withPassword( $args )
	{
		$args = phpSmug::processArgs( func_get_args() );
		return $this->login( $args );
	}
	
	/**
	 * I've chosen to go with the HTTP PUT method as it is quicker, simpler
	 * and more reliable than using the API or POST methods.
	 * 
	 * @access	public
	 * @param	integer		$AlbumID The AlbumID the image is to be uploaded to
	 * @param	string		$File The path to the local file that is being uploaded
	 * @param	string		$FileName (Optional) The filename to give the file
	 *						on upload
	 * @param	mixed		$arguments (Optional) Additional arguments. See
	 *						SmugMug API documentation.
	 * @uses	request
	 * @link	http://wiki.smugmug.net/display/API/Uploading
	 * @return	array|false
	 * @todo Add support for multiple asynchronous uploads
	 **/
	public function images_upload()
	{
		$args = phpSmug::processArgs( func_get_args() );
		if ( !array_key_exists( 'File', $args ) ) {
			throw new PhpSmugException( 'No upload file specified.' );
		}
		
		// Set FileName, if one isn't provided in the method call
		if ( !array_key_exists( 'FileName', $args ) ) {
			$args['FileName'] = basename( $args['File'] );
		}

		// Ensure the FileName is phpSmug::urlencodeRFC3986 encoded - caters for stange chars and spaces
		$args['FileName'] = phpSmug::urlencodeRFC3986( $args['FileName'] );

		// OAuth Stuff
		if ( $this->OAuthSecret ) {
			$sig = $this->generate_signature( 'Upload', array( 'FileName' => $args['FileName'] ) );
		}
		
		if ( is_file( $args['File'] ) ) {
			$fp = fopen( $args['File'], 'r' );
			$data = fread( $fp, filesize( $args['File'] ) );
			fclose( $fp );
		} else {
			throw new PhpSmugException( "File doesn't exist: {$args['File']}" );
		}

		// Create a new object as we still need the other request object
		$upload_req = new httpRequest();
        $upload_req->setMethod( 'PUT' );
		$upload_req->setConfig( array( 'follow_redirects' => TRUE, 'max_redirects' => 3, 'ssl_verify_peer' => FALSE, 'ssl_verify_host' => FALSE, 'connect_timeout' => 60 ) );
		$upload_req->setAdapter( $this->adapter );
		
		// Set the proxy if one has been set earlier
		if ( isset( $this->proxy ) && is_array( $this->proxy ) ) {
			$upload_req->setConfig(array('proxy_host' => $this->proxy['server'],
							             'proxy_port' => $this->proxy['port'],
									     'proxy_user' => $this->proxy['user'],
									     'proxy_password' => $this->proxy['password']));
		}

		$upload_req->setHeader( array( 'User-Agent' => "{$this->AppName} using phpSmug/" . phpSmug::$version,
									   'Content-MD5' => md5_file( $args['File'] ),
									   'Connection' => 'keep-alive') );

		if ( $this->loginType == 'authd' ) {
			$upload_req->setHeader( 'X-Smug-SessionID', $this->SessionID );
		} else {
			$upload_req->setHeader( 'Authorization', 'OAuth realm="http://api.smugmug.com/",
				oauth_consumer_key="'.$this->APIKey.'",
				oauth_token="'.$this->oauth_token.'",
				oauth_signature_method="'.$this->oauth_signature_method.'",
				oauth_signature="'.urlencode( $sig ).'",
				oauth_timestamp="'.$this->oauth_timestamp.'",
				oauth_version="1.0",
				oauth_nonce="'.$this->oauth_nonce.'"' );
		}
			
		$upload_req->setHeader( array( 'X-Smug-Version' => $this->APIVer,
									   'X-Smug-ResponseType' => 'PHP',
									   'X-Smug-AlbumID' => $args['AlbumID'],
									   'X-Smug-Filename'=> basename($args['FileName'] ) ) ); // This is actually optional, but we may as well use what we're given
		
		/* Optional Headers */
		( isset( $args['ImageID'] ) ) ? $upload_req->setHeader( 'X-Smug-ImageID', $args['ImageID'] ) : false;
		( isset( $args['Caption'] ) ) ? $upload_req->setHeader( 'X-Smug-Caption', $args['Caption'] ) : false;
		( isset( $args['Keywords'] ) ) ? $upload_req->setHeader( 'X-Smug-Keywords', $args['Keywords'] ) : false;
		( isset( $args['Latitude'] ) ) ? $upload_req->setHeader( 'X-Smug-Latitude', $args['Latitude'] ) : false;
		( isset( $args['Longitude'] ) ) ? $upload_req->setHeader( 'X-Smug-Longitude', $args['Longitude'] ) : false;
		( isset( $args['Altitude'] ) ) ? $upload_req->setHeader( 'X-Smug-Altitude', $args['Altitude'] ) : false;
		( isset( $args['Hidden'] ) ) ? $upload_req->setHeader( 'X-Smug-Hidden', $args['Hidden'] ) : false;

		//$proto = ( $this->oauth_signature_method == 'PLAINTEXT' || $this->secure ) ? 'https' : 'http';	// No secure uploads at this time.
		//$upload_req->setURL( $proto . '://upload.smugmug.com/'.$args['FileName'] );
		$upload_req->setURL( 'http://upload.smugmug.com/'.$args['FileName'] );
		$upload_req->setBody( $data );

        //Send Requests 
		$upload_req->execute();
		
		$this->response = $upload_req->getBody();
		
		// For some reason the return string is formatted with \n and extra space chars.  Remove these.
		$replace = array( '\n', '\t', '  ' );
		$this->response = str_replace( $replace, '', $this->response );
		$this->parsed_response = unserialize( trim( $this->response ) );
		
		if ( $this->parsed_response['stat'] == 'fail' ) {
			$this->error_code = $this->parsed_response['code'];
            $this->error_msg = $this->parsed_response['message'];
			$this->parsed_response = FALSE;
			throw new PhpSmugException( "SmugMug API Error for method image_upload: {$this->error_msg}", $this->error_code );
		} else {
			$this->error_code = FALSE;
            $this->error_msg = FALSE;
		}
		return $this->parsed_response ? $this->parsed_response['Image'] : FALSE;
	}
	
	/**
	 * Dynamic method handler.  This function handles all SmugMug method calls
	 * not explicitly implemented by phpSmug.
	 * 
 	 * @access	public
	 * @uses	request
	 * @param	string		$method The SmugMug method you want to call, but
	 *						with "." replaced by "_"
	 * @param	mixed		$arguments The params to be passed to the relevant API
	 *						method. See SmugMug API docs for more details.
	 * @return	mixed
	 **/
	public function __call( $method, $arguments )
	{
		$method = strtr( $method, '_', '.' );
		$args = phpSmug::processArgs( $arguments );
	
		if ( $this->OAuthSecret ) {
			$sig = $this->generate_signature( $method, $args );
			$oauth_params = array (
				'oauth_version'             => '1.0',
				'oauth_nonce'               => $this->oauth_nonce,
				'oauth_timestamp'           => $this->oauth_timestamp,
				'oauth_consumer_key'        => $this->APIKey,
				'oauth_signature_method'    => $this->oauth_signature_method,
				'oauth_signature'           => $sig
				);
			
			// Only getRequestToken won't have a token when using OAuth
			if ( $method != 'auth.getRequestToken' ) {
				$oauth_params['oauth_token'] = $this->oauth_token;
			}
			$args = array_merge( $args, $oauth_params );
		}

		$this->request( $method, $args );

		// pop off the "stat", "mode" and "method" parts of the array as we don't need them anymore.
		// BUG: API 1.2.1 and lower: the results are different if the response only has 1 element.  We shouldn't array_shift() lower down.
		//      However, I need to consider what to do to fix this: either go the route of making the response similar to what we do now
		//      and thus don't break anything when people upgrade, or change the response so it's consistent with what the API says the user
		//      will get back, which WILL break people's apps who don't use the 1.2.2 API endpoint.
        if ( is_array( $this->parsed_response ) ) $output = array_pop( $this->parsed_response );
		//if (is_array($this->parsed_response)) $output = $this->parsed_response;
		// I'm really not sure why I shift this array if it only contains one element.
		//$output = (count($output) == '1' && is_array($output)) ? array_shift($output) : $output;

		/* Automatically set token if calling getRequestToken */
		if ( $method == 'auth.getRequestToken' ) {
			$this->setToken( $output['Token'] );
		}

		return ( is_string( $output ) && strstr( $output, 'smugmug.' ) ) ? NULL : $output;
	}
	
	 /**
	  * Return the authorisation URL.
	  *
	  * @access public
	  * @param	string		$Access The required level of access. Defaults to "Public"
	  * @param	string		$Permissions The required permissions.  Defaults to "Read"
	  * @return string
	  **/
	 public function authorize()
	{
		 $args = phpSmug::processArgs( func_get_args() );
		 $perms = ( array_key_exists( 'Permissions', $args ) ) ? $args['Permissions'] : 'Public';
		 $access = ( array_key_exists( 'Access', $args ) ) ? $args['Access'] : 'Read';
 		 return "https://secure.smugmug.com/services/oauth/authorize.mg?Access=$access&Permissions=$perms&oauth_token={$this->oauth_token}";
	 }
	 

	 /**
	  * Static function to encode a string according to RFC3986.
	  *
	  * This is a requirement of implementing OAuth
	  *
	  * @static
	  * @access private
	  * @param	string		$string The string requiring encoding
	  * @return string
	  **/
	 private static function urlencodeRFC3986( $string )
	 {
		return str_replace( '%7E', '~', rawurlencode( $string ) );
	 }

	 /**
	  * Method that generates the OAuth signature
	  *
	  * In order for this method to correctly generate a signature, setToken()
	  * MUST be called to set the token and token secret within the instance of
	  * phpSmug.
	  *
	  * @access	private
	  * @param	string		$apicall The API method.
	  * @param	mixed		$apiargs The arguments passed to the API method.
	  * @return string
	  **/
	 private function generate_signature( $apicall, $apiargs = NULL )
	 {
		$this->oauth_timestamp = time();
		$this->oauth_nonce = md5(time() . mt_rand());

		if ( $apicall != 'Upload' ) {
			if ( substr( $apicall,0,8 ) != 'smugmug.' ) {
				$apicall = 'smugmug.' . $apicall;
			}
		}
		if ( $this->oauth_signature_method == 'PLAINTEXT' ) {
			return phpSmug::urlencodeRFC3986( $this->OAuthSecret ).'&'.phpSmug::urlencodeRFC3986( $this->oauth_token_secret );
		} else {
			$this->oauth_signature_method = 'HMAC-SHA1';
			$encKey = phpSmug::urlencodeRFC3986( $this->OAuthSecret ) . '&' . phpSmug::urlencodeRFC3986( $this->oauth_token_secret );
			
			if ( strpos( $apicall, 'Token' ) || $this->secure && $apicall != 'Upload' ) {
				$endpoint = "https://secure.smugmug.com/services/api/php/{$this->APIVer}/";
			} else if ( $apicall == 'Upload' ) {
				//$proto = ( $this->oauth_signature_method == 'PLAINTEXT' || $this->secure ) ? 'https' : 'http';
				//$endpoint = $proto . '://upload.smugmug.com/'.$apiargs['FileName'];	// No support for secure uploads yet
				$endpoint = 'http://upload.smugmug.com/'.$apiargs['FileName'];
			} else {
				$endpoint = "http://api.smugmug.com/services/api/php/{$this->APIVer}/";
			}
			
			$method = ( $apicall == 'Upload' ) ? 'PUT' : 'POST';
			$params = array (
				'oauth_version'             => '1.0',
				'oauth_nonce'               => $this->oauth_nonce,
				'oauth_timestamp'           => $this->oauth_timestamp,
				'oauth_consumer_key'        => $this->APIKey,
				'oauth_signature_method'    => $this->oauth_signature_method
				);
			if ( $apicall != 'Upload' ) $params = array_merge( $params, array('method' => $apicall ) );
			$params = ( !empty( $this->oauth_token ) ) ? array_merge( $params, array( 'oauth_token' => $this->oauth_token ) ) : $params;
			if ( $apicall != 'Upload' ) $params = ( !empty( $apiargs ) ) ? array_merge( $params, $apiargs ) : $params;
		    $keys = array_map( array( 'phpSmug', 'urlencodeRFC3986' ), array_keys( $params ) );
		    $values = array_map( array( 'phpSmug', 'urlencodeRFC3986' ), array_values( $params ) );
			$params = array_combine( $keys, $values );
		    // Sort by keys (natsort)
		    uksort( $params, 'strnatcmp' );
			// We can't use implode() here as it plays havoc with array keys with empty values.
			$count = count( $params );
			$string = '';
			foreach ( $params as $key => $value ) {
				$count--;
				$string .= $key . '=' . $value;
				if ( $count )	{
					$string .= '&';
				}
			}
			$base_string = $method . '&' . phpSmug::urlencodeRFC3986( $endpoint ) . '&' .  phpSmug::urlencodeRFC3986( $string );
			$sig = base64_encode( hash_hmac( 'sha1', $base_string, $encKey, true ) );
			return $sig;
		}
	 }
	  
	 /**
	  * Process arguments passed to method
	  *
	  * @static
	  * @param	array		Arguments taken from a function by func_get_args()
	  * @access private
	  * @return array
	  **/
	 private static function processArgs( $arguments )
	 {
		$args = array();
		foreach ( $arguments as $arg ) {
			if ( is_array( $arg ) ) {
				$args = array_merge( $args, $arg );
			} else {
				$exp = explode( '=', $arg, 2 );
				$args[$exp[0]] = $exp[1];
			}
		}
		return $args;
	  }
	   
}



/****************** Custom HTTP Request Classes *******************************
 *
 * The classes below could be put into individual files, but to keep things simple
 * I've included them in this file.
 *
 * The code below has been taken from the Habari project - http://habariproject.org
 * and modified to suit the needs of phpSmug.
 *
 * The original source is distributed under the Apache License Version 2.0
 */

class HttpRequestException extends Exception {}

interface PhpSmugRequestProcessor
{
	public function execute( $method, $url, $headers, $body, $config );
	public function getBody();
	public function getHeaders();
}

class httpRequest
{
	private $method = 'POST';
	private $url;
	private $params = array();
	private $headers = array();
	private $postdata = array();
	private $files = array();
	private $body = '';
	private $processor = NULL;
	private $executed = FALSE;

	private $response_body = '';
	private $response_headers = '';

	/**
    * Adapter Configuration parameters
    * @var  array
    * @see  setConfig()
    */
    protected $config = array(
		'adapter'			=> 'curl',
        'connect_timeout'   => 5,
        'timeout'           => 0,
        'buffer_size'       => 16384,

        'proxy_host'        => '',
        'proxy_port'        => '',
        'proxy_user'        => '',
        'proxy_password'    => '',
        'proxy_auth_scheme' => 'basic',

		// TODO: These don't apply to SocketRequestProcessor yet
        'ssl_verify_peer'   => FALSE,
        'ssl_verify_host'   => 2, // 1 = check CN of ssl cert, 2 = check and verify @see http://php.net/curl_setopt
        'ssl_cafile'        => NULL,
        'ssl_capath'        => NULL,
        'ssl_local_cert'    => NULL,
        'ssl_passphrase'    => NULL,

        'follow_redirects'  => FALSE,
        'max_redirects'     => 5
    );

	/**
	 * @param string	$url URL to request
	 * @param string	$method Request method to use (default 'POST')
	 * @param int		$timeout Timeout in seconds (default 30)
	 */
	public function __construct( $url = NULL, $method = 'POST', $timeout = 30 )
	{
		$this->method = strtoupper( $method );
		$this->url = $url;
		$this->setTimeout( $timeout );
		$this->setHeader( array( 'User-Agent' => "Unknown application using phpSmug/" . phpSmug::$version ) );

		// can't use curl's followlocation in safe_mode with open_basedir, so fallback to socket for now
		if ( function_exists( 'curl_init' ) && ( $this->config['adapter'] == 'curl' )
			 && ! ( ini_get( 'safe_mode' ) || ini_get( 'open_basedir' ) ) ) {
			$this->processor = new PhpSmugCurlRequestProcessor;
		}
		else {
			$this->processor = new PhpSmugSocketRequestProcessor;
		}
	}

	/**
	 * Set adapter configuration options
	 *
	 * @param mixed			$config An array of options or a string name with a
	 *						corresponding $value
	 * @param mixed			$value
	 * @return httpRequest
	 */
	public function setConfig( $config, $value = null )
    {
        if ( is_array( $config ) ) {
            foreach ( $config as $name => $value ) {
                $this->setConfig( $name, $value );
            }

        } else {
            if ( !array_key_exists( $config, $this->config ) ) {
				// We only trigger an error here as using an unknow config param isn't fatal
				trigger_error( "Unknown configuration parameter '{$config}'", E_USER_WARNING );
            } else {
				$this->config[$config] = $value;
			}
        }
        return $this;
    }

	/**
     * Set http method
     *
     * @param string HTTP method to use (GET, POST or PUT)
     * @return void
     */
    public function setMethod( $method )
	{
		$method = strtoupper( $method );
        if ( $method == 'GET' || $method == 'POST' || $method == 'PUT' ) {
            $this->method = $method;
		}
    }

	/**
	 * Set the request query parameters (i.e., the URI's query string).
	 * Will be merged with existing query info from the URL.
	 *
	 * @param array $params
	 * @return void
	 */
	public function setParams( $params )
	{
		if ( ! is_array( $params ) ) {
			$params = parse_str( $params );
		}
		$this->params = $params;
	}

	/**
	 * Add a request header.
	 *
	 * @param mixed $header		The header to add, either as an associative array
	 *							'name'=>'value' or as part of a $header $value
	 *							string pair.
	 * @param mixed $value		The value for the header if passing the header as
	 *							two arguments.
	 * @return void
	 */
	public function setHeader( $header, $value = NULL )
	{
		if ( is_array( $header ) ) {
			$this->headers = array_merge( $this->headers, $header );
		}
		else {
			$this->headers[$header] = $value;
		}
	}

	/**
	 * Return the response headers. Raises a warning and returns if the request wasn't executed yet.
	 *
	 * @return mixed
	 */
	public function getHeaders()
	{
		if ( !$this->executed ) {
			return 'Trying to fetch response headers for a pending request.';
		}
		return $this->response_headers;
	}

	/**
	 * Set the timeout. This is independent of the connect_timeout.
	 *
	 * @param int $timeout Timeout in seconds
	 * @return void
	 */
	public function setTimeout( $timeout )
	{
		$this->config['timeout'] = $timeout;
	}

	/**
	 * Set the adapter to use.  Accepted values are "curl" and "socket"
	 *
	 * @param string $adapter
	 * @return void
	 */
	public function setAdapter( $adapter )
	{
		$adapter = strtolower( $adapter );
		if ( $adapter == 'curl' || $adapter == 'socket' ) {
			$this->config['adapter'] = $adapter;
			// We need to reset the processor too.  This is quite crude and messy, but we need to do it.
			if ( function_exists( 'curl_init' ) && ( $adapter == 'curl' )
				 && ! ( ini_get( 'safe_mode' ) || ini_get( 'open_basedir' ) ) ) {
				$this->processor = new PhpSmugCurlRequestProcessor;
			}
			else {
				$this->processor = new PhpSmugSocketRequestProcessor;
			}
		}
	}

	/**
	 * Get the currently selected adapter. This is more for unit testing purposes
	 *
	 * @return string
	 */
	public function getAdapter()
	{
		return $this->config['adapter'];
	}

	/**
	 * Get the params
	 *
	 * @return array
	 */
	public function getParams()
	{
		return $this->params;
	}
	
	/**
	 * Get the current configuration. This is more for unit testing purposes
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Set the destination url
	 *
	 * @param string $url Destination URL
	 * @return void
	 */
	public function setUrl( $url )
	{
		if ( $url ) {
            $this->url = $url;
		}
	}

	/**
	 * Set request body
	 *
	 * @param mixed
	 * @return void
	 */
	public function setBody( $body )
	{
		if ( $this->method === 'POST' || $this->method === 'PUT' ) {
			$this->body = $body;
		}
	}

	/**
	 * set postdata
	 *
	 * @access	public
	 * @param	mixed	$name
	 * @param	string	$value
	 * @return	void
	 */
	public function setPostData( $name, $value = null )
	{
		if ( is_array( $name ) ) {
			//$this->postdata = array_merge( $this->postdata, $name );
			$this->postdata = $name;
		}
		else {
			$this->postdata[$name] = $value;
		}
	}

	/**
	 * Return the response body. Raises a warning and returns if the request wasn't executed yet.
	 *
	 * @return mixed
	 */
	public function getBody()
	{
		if ( !$this->executed ) {
			return 'Trying to fetch response body for a pending request.';
		}
		return $this->response_body;
	}

	/**
	 * Actually execute the request.
	 *
	 * @return mixed	On success, returns TRUE and populates the response_body
	 *					and response_headers fields.
	 *					On failure, throws error.
	 */
	public function execute()
	{
		$this->prepare();
		$result = $this->processor->execute( $this->method, $this->url, $this->headers, $this->body, $this->config );
		$this->body = ''; // We need to do this as we reuse the same object for performance. Once we've executed, the body is useless anyway due to the changing params
		if ( $result ) {
			$this->response_headers = $this->processor->getHeaders();
			$this->response_body = $this->processor->getBody();
			$this->executed = true;
			return true;
		}
		else {
			$this->executed = false;
			return $result;
		}
	}

	/**
	 * Tidy things up in preparation of execution.
	 *
	 * @return void
	 */
	private function prepare()
	{
		// remove anchors (#foo) from the URL
		$this->url = preg_replace( '/(#.*?)?$/', '', $this->url );
		// merge query params from the URL with params given
		$this->url = $this->mergeQueryParams( $this->url, $this->params );

		if ( $this->method === 'POST' ) {
			if ( !isset( $this->headers['Content-Type'] ) ) {
				$this->setHeader( array( 'Content-Type' => 'application/x-www-form-urlencoded' ) );
			}
			if ( $this->headers['Content-Type'] == 'application/x-www-form-urlencoded' || $this->headers['Content-Type'] == 'application/json' ) {
				$count = count( $this->postdata );
				if( $this->body != '' && $count > 0 ) {
					$this->body .= '&';
				}
				//$this->body .= http_build_query( $this->postdata, '', '&' );
				// We don't use http_build_query() as it converts empty array values to 0, which we don't want.
				foreach ( $this->postdata as $key => $value ) {
					$count--;
					$this->body .= $key . '=' . $value;
					if ( $count )	{
						$this->body .= '&';
					}
				}
			}
			$this->setHeader( array( 'Content-Length' => strlen( $this->body ) ) );
		}
	}

	/**
	 * Merge query params from the URL with given params.
	 *
	 * @param string $url The URL
	 * @param string $params An associative array of parameters.
	 * @return string
	 */
	private function mergeQueryParams( $url, $params )
	{
		$urlparts = parse_url( $url );

		if ( ! isset( $urlparts['query'] ) ) {
			$urlparts['query'] = '';
		}

		if ( ! is_array( $params ) ) {
			parse_str( $params, $params );
		}

		if ( $urlparts['query'] != '' ) {
			$parts = array_merge( parse_str( $qparts ) , $params );
		} else {
			$parts = $params;
		}
		$urlparts['query'] = http_build_query( $parts, '', '&' );
		return ( $urlparts['query'] != '' ) ? $url .'?'. $urlparts['query'] : $url;
	}

}

 

class PhpSmugCurlRequestProcessor implements PhpSmugRequestProcessor
{
	private $response_body = '';
	private $response_headers = '';
	private $executed = FALSE;
	private $can_followlocation = TRUE;
	private $_headers = '';

	public function __construct()
	{
		if ( ini_get( 'safe_mode' ) || ini_get( 'open_basedir' ) ) {
			$this->can_followlocation = FALSE;
		}
	}

	public function execute( $method, $url, $headers, $body, $config )
	{
		$merged_headers = array();
		foreach ( $headers as $k => $v ) {
			$merged_headers[] = $k . ': ' . $v;
		}

		$ch = curl_init();

		$options = array(
			CURLOPT_URL				=> $url,
			CURLOPT_HEADERFUNCTION	=> array( &$this, '_headerfunction' ),
			CURLOPT_MAXREDIRS		=> $config['max_redirects'],
			CURLOPT_CONNECTTIMEOUT	=> $config['connect_timeout'],
			CURLOPT_TIMEOUT			=> $config['timeout'],
			CURLOPT_SSL_VERIFYPEER	=> $config['ssl_verify_peer'],
			CURLOPT_SSL_VERIFYHOST	=> $config['ssl_verify_host'],
			CURLOPT_BUFFERSIZE		=> $config['buffer_size'],
			CURLOPT_HTTPHEADER		=> $merged_headers,
			CURLOPT_RETURNTRANSFER	=> TRUE,
		);

		if ( $this->can_followlocation && $config['follow_redirects'] ) {
			$options[CURLOPT_FOLLOWLOCATION] = TRUE; // Follow 302's and the like.
		}

		if ( $method === 'POST' ) {
			$options[CURLOPT_POST] = TRUE; // POST mode.
			$options[CURLOPT_POSTFIELDS] = $body;
		}
		else if ( $method === 'PUT' ) {
			$options[CURLOPT_CUSTOMREQUEST] = 'PUT'; // PUT mode
			$options[CURLOPT_POSTFIELDS] = $body; // The file to put
		}
		else {
			$options[CURLOPT_CRLF] = TRUE; // Convert UNIX newlines to \r\n
		}

		// set proxy, if needed
        if ( $config['proxy_host'] ) {
            if ( ! $config['proxy_port'] ) {
                throw new HttpRequestException( 'Proxy port not provided' );
            }
            $options[CURLOPT_PROXY] = $config['proxy_host'] . ':' . $config['proxy_port'];
            if ( $config['proxy_user'] ) {
                $options[CURLOPT_PROXYUSERPWD] = $config['proxy_user'] . ':' . $config['proxy_password'];
                switch ( strtolower( $config['proxy_auth_scheme'] ) ) {
                    case 'basic':
                        curl_setopt( $ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC );
                        break;
                    case 'digest':
                        curl_setopt( $ch, CURLOPT_PROXYAUTH, CURLAUTH_DIGEST );
                }
            }
        }
		curl_setopt_array($ch, $options);

		$body = curl_exec( $ch );

		if ( curl_errno( $ch ) !== 0 ) {
			throw new HttpRequestException( sprintf( '%s: CURL Error %d: %s', __CLASS__, curl_errno( $ch ), curl_error( $ch ) ), curl_errno( $ch ) );
		}

		if ( substr( curl_getinfo( $ch, CURLINFO_HTTP_CODE ), 0, 1 ) != 2 ) {
			throw new HttpRequestException( sprintf( 'Bad return code (%1$d) for: %2$s', curl_getinfo( $ch, CURLINFO_HTTP_CODE ), $url ), curl_errno( $ch ) );
		}

		curl_close( $ch );

		// this fixes an E_NOTICE in the array_pop
		$tmp_headers = explode( "\r\n\r\n", mb_substr( $this->_headers, 0, -4 ) );

		$this->response_headers = array_pop( $tmp_headers );
		$this->response_body = $body;
		$this->executed = true;

		return true;
	}

	public function _headerfunction( $ch, $str )
	{
		$this->_headers .= $str;
		return strlen( $str );
	}

	public function getBody()
	{
		if ( ! $this->executed ) {
			return 'Request has not executed yet.';
		}
		return $this->response_body;
	}

	public function getHeaders()
	{
		if ( ! $this->executed ) {
			return 'Request has not executed yet.';
		}
		return $this->response_headers;
	}
}

 

class PhpSmugSocketRequestProcessor implements PhpSmugRequestProcessor
{
	private $response_body = '';
	private $response_headers = '';
	private $executed = FALSE;
	private $redir_count = 0;
	private $can_followlocation = true;
	
	public function __construct ( ) 
	{		
		// see if we can follow Location: headers
		if ( ini_get( 'safe_mode' ) || ini_get( 'open_basedir' ) ) {
			$this->can_followlocation = false;
		}	
	}
		
	public function execute ( $method, $url, $headers, $body, $config ) 
	{	
		$merged_headers = array();
		foreach ( $headers as $k => $v ) {
			$merged_headers[] = $k . ': '. $v;
		}

		// parse out the URL so we can refer to individual pieces
		$urlbits = parse_url( $url );

		// set up the options we'll use when creating the request's context
		$options = array(
			'http' => array(
				'method' => $method,
				'header' => implode( "\n", $merged_headers ),
				'timeout' => $config['timeout'],
				'follow_location' => $this->can_followlocation,		// 5.3.4+, should be ignored by others
				'max_redirects' => $config['max_redirects'],

				// and now for our ssl-specific portions, which will be ignored for non-HTTPS requests
				'verify_peer' => $config['ssl_verify_peer'],
				//'verify_host' => $config['ssl_verify_host'],	// there doesn't appear to be an equiv of this for sockets - the host is matched by default and you can't just turn that off, only substitute other hostnames
				'cafile' => $config['ssl_cafile'],
				'capath' => $config['ssl_capath'],
				'local_cert' => $config['ssl_local_cert'],
				'passphrase' => $config['ssl_passphrase'],
			),
		);

		if ( $method == 'POST' || $method == 'PUT' ) {
			$options['http']['content'] = $body;
		}

		if ( $config['proxy_host'] != '' ) {
			$proxy = $config['proxy_host'] . ':' . $config['proxy_port'];
			if ( $config['proxy_user'] != '' ) {
				$proxy = $config['proxy_user'] . ':' . $config['proxy_password'] . '@' . $proxy;
			}
			$options['http']['proxy'] = 'tcp://' . $proxy;
		}

		// create the context
		$context = stream_context_create( $options );

		// perform the actual request - we use fopen so stream_get_meta_data works
		$fh = @fopen( $url, 'r', false, $context );
		if ( $fh === false ) {
			throw new Exception( 'Unable to connect to ' . $urlbits['host'] );
		}

		// read in all the contents -- this is the same as file_get_contents, only for a specific stream handle
		$body = stream_get_contents( $fh );
		// get meta data
		$meta = stream_get_meta_data( $fh );

		// close the connection before we do anything else
		fclose( $fh );

		// did we timeout?
		if ( $meta['timed_out'] == true ) {
			throw new Exception( 'Request timed out' );
		}

		// $meta['wrapper_data'] should be a list of the headers, the same as is loaded into $http_response_header
		$headers = array();
		foreach ( $meta['wrapper_data'] as $header ) {

			// break the header up into field and value
			$pieces = explode( ': ', $header, 2 );

			if ( count( $pieces ) > 1 ) {
				// if the header was a key: value format, store it keyed in the array
				$headers[ $pieces[0] ] = $pieces[1];
			}
			else {
				// some headers (like the HTTP version in use) aren't keyed, so just store it keyed as itself
				$headers[ $pieces[0] ] = $pieces[0];
			}

		}

		$this->response_headers = $headers;
		$this->response_body = $body;
		$this->executed = true;

		return true;
	}

	public function getBody()
	{
		if ( ! $this->executed ) {
			return 'Request has not executed yet.';
		}
		return $this->response_body;
	}

	public function getHeaders()
	{
		if ( ! $this->executed ) {
			return 'Request has not executed yet.';
		}
		return $this->response_headers;
	}

	private function _unchunk( $body )
	{
		/* see <http://www.w3.org/Protocols/rfc2616/rfc2616-sec3.html> */
		$result = '';
		$chunk_size = 0;

		do {
			$chunk = explode( "\r\n", $body, 2 );
			list( $chunk_size_str, )= explode( ';', $chunk[0], 2 );
			$chunk_size = hexdec( $chunk_size_str );

			if ( $chunk_size > 0 ) {
				$result .= mb_substr( $chunk[1], 0, $chunk_size );
				$body = mb_substr( $chunk[1], $chunk_size+1 );
			}
		}
		while ( $chunk_size > 0 );
		// this ignores trailing header fields

		return $result;
	}
}

?>