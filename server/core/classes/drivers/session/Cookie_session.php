<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session manages with Cookie
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Cookie_session extends SZ_Session_driver
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
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		if ( $this->_initSession() === FALSE )
		{
			// If session cannnot read, create new session.
			$this->_sessionCreate();
		}
		
		Event::addListener('session_update', array($this, '_sessionSave'), TRUE);
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
		$this->_authData = array(
			'sessionID'    => $sessid,
			'ipAddress'    => $this->req->ipAddress(),
			'userAgent'    => $this->req->server('HTTP_USER_AGENT'),
			'lastActivity' => time()
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
		
		$len         = strlen($this->_sessionAuthorizeDatakey);
		$sess        = substr($sess, $len);
		$sessionData = @unserialize($sess);
		
		if ( $sessionData )
		{
			$this->_authData = $sessionData['authData'];
			$this->_userData = $sessionData['userData'];
			return $this->_authData;
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
		return $this->_userData;
		
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstract implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionSave()
	 */
	public function _sessionSave()
	{
		$sessID = $this->_sessionID;
		// update sessionID when lastActivity is over
		if ( $this->_authData['lastActivity'] + $this->_sessionUpdateTime < $this->_time )
		{
			$this->_sessionID                = $this->_generateSessionID();
			$this->_authData['lastActivity'] = $this->_time;
			$this->_authData['sessionID']    = $this->_sessionID;
		}
		$data = array(
			'authData' => $this->_authData,
			'userData' => $this->_sessionCurrentTemporaryData
		);
		
		$data = $this->_sessionAuthorizeDatakey . serialize($data);
	
		if ( $this->_encryptSession === TRUE )
		{
			$encrypt  = Seezoo::$Importer->library('Encrypt');
			$data     = $encrypt->encode($data);
		}
		
		$this->_setSessionCookie($data);
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