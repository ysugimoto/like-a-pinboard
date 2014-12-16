<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session manages with Redis
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Redis_session extends SZ_Session_driver
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
	 * Redis connection instance
	 * @var Redis
	 */
	protected $redis;
	
	
	/**
	 * Redis connection host
	 * @var string
	 */
	protected $_host;
	
	
	/**
	 * Redis connection port
	 * @var int
	 */
	protected $_port;
	
	
	/**
	 * Redis connect poersistance flag
	 * @var bool
	 */
	protected $_pconnect;
	
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->_host     = $this->env->getConfig('session_redis_host');
		$this->_port     = $this->env->getConfig('session_redis_port');
		$this->_pconnect = $this->env->getConfig('session_redis_pconnect');

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
	 * Redis connection start
	 * 
	 * @access protected
	 */
	protected function _connect()
	{
		$this->redis = new Redis;
		if ( $this->_pconnect === TRUE )
		{
			if ( ! @$this->redis->pconnect($this->_host, $this->_port) )
			{
				throw new RuntimeException('Couldn\'t connect Redis server! check your redis host/port.');
			}
		}
		else
		{
			if ( ! @$this->redis->connect($this->_host, $this->_port) )
			{
				throw new RuntimeException('Couldn\'t connect Redis server! check your redis host/port.');
			}
		}
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
		$this->redis->set($sessid, $authData);
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
		$this->redis->delete($this->_sessionID);
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
		
		if ( FALSE !== ( $result = $this->redis->get($sess)) )
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
			$this->redis->delete($sessID);
			$this->redis->set($this->_sessionID, $updateData);
		}
		else
		{
			if ( FALSE === $this->redis->replace($sessID, $updateData) )
			{
				$this->redis->set($sessID, $updateData);
			}
		}
		
		$this->_setSessionCookie($authKey);
		@$this->redis->quit();
	}
}