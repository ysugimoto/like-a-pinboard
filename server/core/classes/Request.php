<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * User Request parameters as $_POST, $_GET, $_SERVER, $_COOKIE management
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Request implements Growable
{
	/**
	 * request method
	 * @var string ( always uppercase )
	 */
	public $requestMethod;
	
	
	/**
	 * request pathinfo
	 * @var string
	 */
	protected $_pathinfo;
	
	
	/**
	 * $_COOKIE stack
	 * @var array
	 */
	protected $_cookie;
	
	
	/**
	 * $_SERVER stack
	 * @var array
	 */
	protected $_server;
	
	
	/**
	 * $_POST stack
	 * @var array
	 */
	protected $_post;
	
	
	/**
	 * $_GET stack
	 * @var array
	 */
	protected $_get;


	/**
	 * INPUT stack
	 * @var array
	 */
	protected $_input;
	
	
	/**
	 * URI info
	 * @var string
	 */
	protected $_uri;
	
	
	/**
	 * segment info
	 * @var array
	 */
	protected $_uriArray = array();
	
	
	/**
	 * Accessed passinfo ( not overrided )
	 * @var string
	 */
	protected $_accessPathInfo;
	
	
	/**
	 * Stack accessed IP address
	 * @var string
	 */
	protected $_ip;
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Constructor
	 * 
	 * @access public
	 * @param  array $requestParam
	 * @param  string $encodings
	 */
	public function __construct($requestParam = null)
	{
		if ( empty($requestParam) )
		{
			$requestParam = $GLOBALS;
		}
		$requestParam  = array_change_key_case($requestParam, CASE_LOWER);
		
		$this->_post   = $this->_cleanFilter($this->_getKey($requestParam, 'post')  , Application::getEncoding('post'));
		$this->_cookie = $this->_cleanFilter($this->_getKey($requestParam, 'cookie'), Application::getEncoding('cookie'));
		$this->_get    = $this->_cleanFilter($this->_getKey($requestParam, 'get')   , Application::getEncoding('get'));
		$this->_server = $this->_getKey($requestParam, 'server');
		
		$this->requestMethod   = $this->server('request_method');
		$this->_uri            = ltrim((string)$this->server('request_uri'), '/');
		$this->_accessPathInfo = (string)$this->server('path_info');
		
		$this->_input  = $this->_parseInput();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get globals array from key ( not or underscored )
	 * 
	 * @access protected
	 * @param  array $requests
	 * @param  string $index
	 * @return array
	 */
	protected function _getKey($requests, $index)
	{
		if ( isset($requests[$index]) )
		{
			return $requests[$index];
		}
		else if ( isset($requests['_' . $index]) )
		{
			return $requests['_' . $index];
		}
		
		return array();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Request ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Request');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the server parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function server($key)
	{
		$key = strtoupper($key);
		return ( isset($this->_server[$key]) ) ? $this->_server[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the POST parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function post($key)
	{
		return ( isset($this->_post[$key]) ) ? $this->_post[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the GET parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return ( isset($this->_get[$key]) ) ? $this->_get[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------


	/**
	 * Get the PHP input
	 *
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function input($key)
	{
		return ( isset($this->_input[$key]) ) ? $this->_input[$key] : FALSE;
	}


	// ---------------------------------------------------------------
	
	
	/**
	 * Get the COOKIE parameter
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function cookie($key)
	{
		return ( isset($this->_cookie[$key]) ) ? $this->_cookie[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Build current access URI
	 * 
	 * @access public
	 * @return string
	 */
	public function getCurrentURL()
	{
		$uri = get_config('base_url')
		       . implode('/', reset($this->_uriArray));
		if ( ! get_config('enable_mod_rewrite') )
		{
			$uri .= DISPATCHER . '/';
		}
		if ( count($this->_get) > 0 )
		{
			$uri .= '?' . http_build_query($this->_get);
		}
		
		return $uri;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set a process request
	 * 
	 * @access public
	 * @param  string $pathinfo
	 * @param  string $mode
	 * @param  int    $level
	 */
	public function setRequest($pathinfo, $mode, $level)
	{
		// If pathinfo is empty, use server-pathinfo.
		$pathinfo = ( empty($pathinfo) ) ? $this->_accessPathInfo : $pathinfo;
		$pathinfo = kill_traversal(trim($pathinfo, '/'));
		$segments = ( $pathinfo !== '' ) ? explode('/', $pathinfo) : array();
		
		// Push stack uri-array
		$this->_uriArray[$level] = $segments;
		
		// method mapping ( returns expect array )
		$routeMapping = array();
		foreach ( array_reverse(Seezoo::getApplication()) as $app )
		{
			// Load the application settings if exists
			if ( file_exists($app->path . 'config/mapping.php') )
			{
				include($app->path . 'config/mapping.php');
				if ( isset($mapping) )
				{
					$routeMapping = array_merge($routeMapping, $mapping);
				}
			}
		}
		
		if ( isset($routeMapping[$mode]) && is_array($routeMapping[$mode]) )
		{
			foreach ( $routeMapping[$mode] as $regex => $map )
			{
				if ( $regex === $pathinfo )
				{
					$pathinfo = $map;
					break;
				}
				else if ( preg_match('|^' . $regex . '$|u', $pathinfo, $matches) )
				{
					$pathinfo = ( isset($matches[1]) )
					              ? preg_replace('|^' . $regex . '$|u', $map, $pathinfo)
					              : $map;
					break;
					
				}
			}
		}
		
		// returns mapped pathinfo
		return $pathinfo;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get access URI-segment
	 * 
	 * @access public
	 * @param  int $index
	 * @param  mixed $default
	 * @return mixed
	 */
	public function segment($index, $default = FALSE)
	{
		$level = Seezoo::getLevel();
		return ( isset($this->_uriArray[$level])
		         && isset($this->_uriArray[$level][$index - 1]) )
		           ? $this->_uriArray[$level][$index - 1]
		           : $default;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get all access URI-segment array
	 * 
	 * @access public
	 * @return array
	 */
	public function uriSegments($level = FALSE)
	{
		if ( ! $level )
		{
			$level = Seezoo::getLevel();
		}
		return ( isset($this->_uriArray[$level]) )
		         ? $this->_uriArray[$level]
		         : array();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get STDIN data
	 * 
	 * @access public
	 * @return string
	 */
	public function stdin()
	{
		if ( PHP_SAPI !== 'cli' )
		{
			throw new RuntimeException('STD_INPUT can get CLI request only!');
		}
		
		$stdin = '';
		while ( FALSE !== ($line = fgets(STDIN, 8192)) )
		{
			$stdin .= $line;
		}
		
		return $stdin;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get HTTP requested PATH_INFO
	 * 
	 * @access public
	 * @return string
	 */
	public function getAccessPathInfo()
	{
		return $this->_accessPathInfo;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get requested method
	 * 
	 * @access public
	 * @return string
	 */
	public function getRequestMethod()
	{
		return $this->requestMethod;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get client IP address
	 * 
	 * @access public
	 * @return string
	 */
	public function ipAddress()
	{
		if ( ! $this->_ip )
		{
		
			$remote        = $this->server('REMOTE_ADDR');
			$trusted       = Application::config('trusted_proxys');
			$ip = $default = '0.0.0.0';
			
			if ( FALSE !== ( $XFF = $this->server('X_FORWARDED_FOR'))
			     && $remote
			     && in_array($remote, $trusted) )
			{
				$exp = explode(',', $XFF);
				$ip  = reset($exp);
			}
			else if ( FALSE !== ( $HCI = $this->server('HTTP_CLIENT_IP'))
			          && $remote
				      && in_array($remote, $trusted) )
			{
				$exp = explode(',', $HCI);
				$ip  = reset($exp);
			}
			else if ( $remote )
			{
				$ip = $remote;
			}
			
			// validate
			if ( function_exists('filter_var') )
			{
				if ( ! filter_var($ip, FILTER_VALIDATE_IP) )
				{
					$ip = $default;
				}
			}
			else if ( function_exists('inet_pton') )
			{
				if ( FALSE === inet_pton($ip) )
				{
					$ip = $default;
				}
			}
			$this->_ip = $ip;
		}
		return $this->_ip;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * clean up parameters
	 * 
	 * @access protected
	 * @param  array  $data
	 * @param  string $encoding
	 * @return array
	 */
	protected function _cleanFilter(array $data, $encoding = 'UTF-8')
	{
		foreach ( $data as $key => $value )
		{
			$key = $this->_filterString($key, $encoding);
			if ( is_array($value) )
			{
				foreach ( $value as $k => $v )
				{
					$k = $this->_filterString($k, $encoding);
					$value[$k] = $this->_filterString($v, $encoding);
				}
				$data[$key] = $value;
			}
			else
			{
				$data[$key] =  $this->_filterString($value, $encoding);
			}
		}
		
		return $data;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Filter string
	 * 
	 * @access protected static
	 * @param  string $str
	 * @param  string $encoding
	 * @return string
	 */
	protected function _filterString($str, $encoding = 'UTF-8')
	{
		if ( get_magic_quotes_gpc() )
		{
			$str = stripslashes($str);
		}
		
		if ( $encoding !== 'UTF-8' )
		{
			$str = self::_convertUTF8($str, $encoding);
		}
		
		// kill invisible character
		do
		{
			$str = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $str, -1, $count);
		}
		while( $count );
		
		// to strict linefeed
		if ( strpos($str, "\r") !== FALSE )
		{
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}
		
		// trim nullbyte
		$str = kill_nullbyte($str);
		
		// TODO: some security process
		
		return $str;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse PHP Input ( when requested with PUT/DELETE method )
	 * @access protected
	 * @return array
	 */
	protected function _parseInput($encoding = 'UTF-8')
	{
		if ( $this->requestMethod === 'GET'
		     || $this->requestMethod === 'POST'
		     || ($input = trim(file_get_contents('php://input')) === ''))
		{
			return array();
		}
		
		$data  = array();
		foreach ( array_filter(explode('=', $input)) as $keyValue )
		{
			list($key, $value) = explode('=', $keyValue);
			// Raw input should have been encoded
			$data[$key] = rawurldecode($value);
		}
		
		return self::_cleanFilter($data, $encoding);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * String convert to UTF-8 encoding
	 * @param string $str
	 * @param string $encoding
	 */
	protected function _convertUTF8($str, $encoding = 'UTF-8')
	{
		if ( function_exists('iconv') && ! preg_match('/[^\x00-\x7F]/S', $str) )
		{
			return @iconv($encoding, 'UTF-8//IGNORE', $str);
		}
		else if ( mb_check_encoding($str, $encoding) )
		{
			return mb_convert_encoding($str, 'UTF-8', $encoding);
		}
		return $str;
	}
}
