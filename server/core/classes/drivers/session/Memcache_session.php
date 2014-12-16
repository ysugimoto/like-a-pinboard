<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session manages with Memcache
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Memcache_session extends SZ_Session_driver
{
	/**
	 * Environement class instance
	 * @var Environment
	 */
	protected $env;
	
	
	/**
	 * Request class instance
	 * @var Request
	 */
	protected $req;
	
	
	/**
	 * temporary userData
	 * @var array
	 */
	protected $_userData;
	
	
	/**
	 * Memcache connection instance
	 * @var Memcace
	 */
	protected $mc;
	
	
	/**
	 * Memcache connection host
	 * @var string
	 */
	protected $_host;
	
	
	/**
	 * Memcache connection port
	 * @var int
	 */
	protected $_port;
	
	
	/**
	 * Memcache connect poersistance flag
	 * @var bool
	 */
	protected $_pconnect;
	
	/**
	 * memcache version
	 * @var int
	 */
	protected $_version;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->_host     = $this->env->getConfig('session_memcache_host');
		$this->_port     = $this->env->getConfig('session_memcache_port');
		$this->_pconnect = $this->env->getConfig('session_memcache_pconnect');

		$this->_connect();
		
		if ( $this->_initSession() === FALSE )
		{
			// If session cannnot read, create new session.
			$this->_sessionCreate();
		}
		
		// manually destruct
		Event::addListener('session_update', array($this, '_sessionSave'), TRUE);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Memcache connection start
	 * 
	 * @access protected
	 */
	protected function _connect()
	{
		$this->mc = new Memcache;
		if ( $this->_pconnect === TRUE )
		{
			if ( ! @$this->mc->pconnect($this->_host, $this->_port) )
			{
				throw new RuntimeException('Couldn\'t connect Memcache server! check your memcached host/port.');
			}
		}
		else
		{
			if ( ! @$this->mc->connect($this->_host, $this->_port) )
			{
				throw new RuntimeException('Couldn\'t connect Memcache ! check your memcache host/port.');
			}
		}
		
		$this->_version = $this->mc->getVersion();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionCreate()
	 */
	protected function _sessionCreate()
	{
		$sessid = $this->_generateSessionID();
		// create auth info data
		$authData = array(
			'session_id'    => $sessid,
			'ip_address'    => $this->req->ipAddress(),
			'user_agent'    => $this->req->server('HTTP_USER_AGENT'),
			'last_activity' => time(),
			'user_data'     => ''
		);
		$this->mc->set($sessid, $authData);
		$this->_authData  = array(
			'sessionID'    => $authData['session_id'],
			'ipAddress'    => $authData['ip_address'],
			'userAgent'    => $authData['user_agent'],
			'lastActivity' => $authData['last_activity']
		);
		$this->_sessionID = $sessid;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct impelmenets
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionDestroy()
	 */
	protected function _sessionDestroy()
	{
		$this->mc->delete($this->_sessionID);
		$this->_setSessionCookie('', -1000);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_getAuthData()
	 */
	protected function _getAuthData()
	{
		if ( ! $this->req->cookie($this->_sessionName) )
		{
			return FALSE;
		}
		
		$sess = $this->req->cookie($this->_sessionName);
		if ( $this->_encryptSession === TRUE )
		{
			$encrypt = Seezoo::$Importer->library('Encrypt');
			$sess    = $encrypt->decode($sess);
		}
		
		if ( ! $sess )
		{
			return FALSE;
		}
		
		$len  = strlen($this->_sessionAuthorizeDatakey);
		$sess = substr($sess, $len);
		
		if ( FALSE !== ( $result = $this->mc->get($sess)) )
		{
			$authData = array(
				'sessionID'    => $sess,
				'ipAddress'    => $result['ip_address'],
				'userAgent'    => $result['user_agent'],
				'lastActivity' => $result['last_activity']
			);
			$this->_authData = $authData;
			$this->_userData = $result['user_data'];
			return $authData;
		}
		return FALSE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * read session data
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionRead()
	 */
	protected function _sessionRead()
	{
		return @unserialize($this->_userData);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionSave()
	 */
	public function _sessionSave()
	{
		$sessID   = $this->_sessionID;
		// update sessionID when lastActivity is over
		if ( $this->_authData['lastActivity'] + $this->_sessionUpdateTime < $this->_time )
		{
			$this->_sessionID                = $this->_generateSessionID();
			$this->_authData['lastActivity'] = $this->_time;
			$this->_authData['session_id']   = $this->_sessionID;
		}
		$authKey = $this->_sessionAuthorizeDatakey . $this->_sessionID;
		$data    = serialize($this->_sessionCurrentTemporaryData);
		
		if ( $this->_encryptSession === TRUE )
		{
			$encrypt = Seezoo::$Importer->library('Encrypt');
			$authKey = $encrypt->encode($authKey);
		}
		
		$updateData = array(
			'session_id'    => $this->_sessionID,
			'ip_address'    => $this->_authData['ipAddress'],
			'user_agent'    => $this->_authData['userAgent'],
			'last_activity' => $this->_authData['lastActivity'],
			'user_data'     => $data
		);
		
		if ( $sessID !== $this->_sessionID )
		{
			$this->mc->delete($sessID);
			$this->mc->set($this->_sessionID, $updateData);
		}
		else
		{
			if ( FALSE === $this->mc->replace($sessID, $updateData) )
			{
				$this->mc->set($sessID, $updateData);
			}
		}
		
		$this->_setSessionCookie($authKey);
		@$this->mc->close();
	}
}