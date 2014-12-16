<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

abstract class SZ_Session_driver
{
	/**
	 * sessionID
	 * @var string
	 */
	protected $_sessionID;
	
	
	/**
	 * processed time
	 * @var int
	 */
	protected $_time;
	
	
	/**
	 * session name
	 * @var string
	 */
	protected $_sessionName;
	
	
	/**
	 * encrypt session flag
	 * @var bool
	 */
	protected $_encryptSession;
	
	
	/**
	 * authorize parameters
	 * @var array
	 */
	protected $_authData;
	
	
	/**
	 * current session data
	 * @var array
	 */
	protected $_sessionData = array();
	
	
	/**
	 * current "Flash" session data
	 * @var array
	 */
	protected $_sessionFlashData = array();
	
	
	/**
	 * current "all" session data ( to save destruct )
	 * @var array
	 */
	protected $_sessionCurrentTemporaryData = array();
	
	
	/**
	 * session authorize key
	 * @var string
	 */
	protected $_sessionAuthorizeDatakey;
	
	
	/**
	 * session lifetime
	 * @var int
	 */
	protected $_sessionLifetime;
	
	
	/**
	 * session update time
	 * @var int
	 */
	protected $_sessionUpdateTime;
	
	
	/**
	 * save cookie path if save to cookie
	 * @var string
	 */
	protected $_cookiePath;
	
	
	/**
	 * save cookie domain if save to cookie
	 * @var string
	 */
	protected $_cookieDomain;
	
	
	// =============================================================
	// abstruct methods
	// =============================================================
	
	/**
	 * create session
	 */
	abstract protected function _sessionCreate();
	
	/**
	 * read session
	 */
	abstract protected function _sessionRead();
	
	/**
	 * save session
	 */
	abstract protected function _sessionSave();
	
	/**
	 * destroy session
	 */
	abstract protected function _sessionDestroy();
	
	/**
	 * get authorize data
	 */
	abstract protected function _getAuthData();
	
	
	
	public function __construct()
	{
		$this->req = Seezoo::getRequest();
		$this->env = Seezoo::getENV();
		
		// setting from config
		$this->_time                    = time();
		$this->_sessionName             = $this->env->getConfig('session_name');
		$this->_encryptSession          = $this->env->getConfig('session_encryption');
		$this->_sessionAuthorizeDatakey = md5($this->env->getConfig('session_auth_key'));
		$this->_cookiePath              = $this->env->getConfig('cookie_path');
		$this->_cookieDomain            = $this->env->getConfig('cookie_domain');
		$this->_sessionLifetime         = $this->env->getConfig('session_lifetime');
		$this->_sessionUpdateTime       = $this->env->getConfig('session_update_time');
		
	}
	
	public function save()
	{
		$this->_sessionSave();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get UserData
	 * 
	 * @access public
	 * @param string $key
	 * @return mixed $sessData
	 */
	public function get($key)
	{
		return ( isset($this->_sessionData[$key]) ) ? $this->_sessionData[$key] : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set userData
	 * 
	 * @access public
	 * @param mixed $key ( string or array )
	 * @param string $value
	 * @return void
	 */
	public function set($key, $value = '')
	{
		if ( is_array($key) )
		{
			foreach ($key as $key2 => $val)
			{
				$this->set($key2, $val);
			}
		}
		else
		{
			$this->_sessionData[$key] =  $this->_sessionCurrentTemporaryData[$key] = $value;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Remove userData
	 * 
	 * @access public
	 * @param  string $key
	 * @return void
	 */
	public function remove($key)
	{
		if ( isset($this->_sessionData[$key]) )
		{
			unset($this->_sessionData[$key]);
		}
		
		if ( isset($this->_sessionCurrentTemporaryData[$key]) )
		{
			unset($this->_sessionCurrentTemporaryData[$key]);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get all userdata
	 * 
	 * @access public
	 * @return array
	 */
	public function getAll()
	{
		return $this->_sessionData;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get flashData
	 * 
	 * @access public
	 * @param string $flashKey
	 * @return mixed
	 */
	public function getFlash($flashKey)
	{
		return ( isset($this->_sessionFlashData[$flashKey]) ) ? $this->_sessionFlashData[$flashKey] : FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set flashdata
	 * 
	 * @access public
	 * @param mixed $key
	 * @param string $value
	 * @return void
	 */
	public function setFlash($key, $value)
	{
		if ( is_array($key) )
		{
			foreach ($key as $key2 => $val)
			{
				$this->setFlash($key2, $val);
			}
		}
		else
		{
			$this->_sessionFlashData[$key]  = $this->_sessionCurrentTemporaryData['Flash:keep:' . $key] = $value;
			
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Keep flash session data untill next access
	 * 
	 * @access public
	 * @param  mixed $keyName
	 */
	public function keepFlash($keyName = null)
	{
		$regex      =  '#\AFlash:sweep:([0-9a-zA-Z\-\._]+)#u';
		$newSession = array();
		
		foreach ( $this->_sessionCurrentTemporaryData as $key => $val )
		{
			if ( preg_match($regex, $key, $match) )
			{
				// If keyname is empty, keep all flashdata
				if ( ! $keyName || $key === $keyName )
				{
					$newSession['Flash:keep:' . $match[1]] = $val;
				}
			}
			else
			{
				$newSession[$key] = $val;
			}
		}
		$this->_sessionCurrentTemporaryData = $newSession;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Read session data
	 * 
	 * @access protected
	 * @return bool $created
	 */
	protected function _initSession()
	{
		if ( FALSE == ($authData = $this->_getAuthData()) )
		{
			return FALSE;
		}
		
		// Does authorize data exits and match user?
		if ( ! isset($authData)
		     || ! is_array($authData) )
		{
			$this->_sessionDestroy();
			return FALSE;
		}
		
		$this->_sessionID = $authData['sessionID'];
		
		// Session expiration check.
		if ( $authData['lastActivity'] + $this->_sessionLifetime < $this->_time )
		{
			$this->_sessionDestroy();
			return FALSE;
		}
		
		// Session matching IP check.
		if ( $this->env->getConfig('session_match_ip') === TRUE
		     && $this->req->ipAddress() !== $authData['ipAddress'] )
		{
			$this->_sessionDestroy();
			return FALSE;
		}
		
		// Session matching User-Agent check.
		if ( $this->env->getConfig('session_match_useragent') === TRUE
		    && strpos($this->req->server('HTTP_USER_AGENT'), $authData['userAgent']) !== 0 )
		{
			$this->_sessionDestroy();
			return FALSE;
		}	
		
		$newSession = array();
		// Mark and sweep Flash data
		foreach ( $this->_sessionRead() as $key => $value )
		{
			if ( preg_match('#\AFlash:(keep|sweep):([0-9a-zA-Z\-\._]+)#u', $key, $match) )
			{
				if ( $match[1] === 'keep' )
				{
					$newSession['Flash:sweep:' . $match[2]] =
					$this->_sessionFlashData[$match[2]]     = $value;
				}
			}
			else
			{
				$this->_sessionData[$key] =
				        $newSession[$key] = $value;
			}
		}
		
		//$authData['lastActivity'] = $this->_time;
		
		// save the new session
		$this->_authData = $authData;
		$this->_sessionCurrentTemporaryData = $newSession;
		unset($authData);
		unset($newSession);
		
		return TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * set session cookie
	 * 
	 * @access protected
	 * @param  string $cookieData
	 */
	protected function _setSessionCookie($cookieData, $lifetime = FALSE)
	{
		$expire = ( $lifetime ) ? $lifetime : $this->env->getConfig('session_lifetime');
		
		if ( version_compare(PHP_VERSION, '5.2.0', '>') )
		{
			setcookie(
				$this->_sessionName,
				$cookieData,
				$expire + time(),
				$this->_cookiePath,
				$this->_cookieDomain,
				( $this->req->server('HTTPS') === 'on' ) ? TRUE : FALSE,
				TRUE // PHP5.2.0+ enables httponly paramter
			);
		}
		else
		{
			setcookie(
				$this->_sessionName,
				$cookieData,
				$expire + time(),
				$this->_cookiePath,
				$this->_cookieDomain,
				( $this->req->server('HTTPS') === 'on' ) ? TRUE : FALSE
			);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * generate random session ID
	 * 
	 * @access protected
	 * @return string $sessID
	 */
	protected function _generateSessionID()
	{
		$sessID = '';
		while (strlen($sessID) < 32)
		{
			$sessID .= mt_rand(0, mt_getrandmax());
		}
		
		return $sessID;
	}
}