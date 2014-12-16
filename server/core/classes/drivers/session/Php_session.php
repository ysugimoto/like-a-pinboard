<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session manages with Native PHP Session
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Php_session extends SZ_Session_driver
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
	 * Constructor
	 */
	public function __construct()
	{
		$this->req = Seezoo::getRequest();
		$this->env = Seezoo::getENV();
				
		// Session start!
		$this->_startSession();
		
		if ( $this->_initSession() === FALSE )
		{
			// If session cannnot read, create new session.
			$this->_sessionCreate();
		}
		
		Event::addListener('session_update', array($this, '_sessionSave'), TRUE);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Start Session
	 * 
	 * @access protected
	 * @return void
	 */
	protected function _startSession()
	{
		if ( ! session_id() )
		{
			session_name($this->env->getConfig('session_name'));
			session_start();
		}
		
		// Notice:
		// TRUE arguments accept PHP 5.1.0 or newer!!
		session_regenerate_id(TRUE);
		
		$this->_sessionID = session_id();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionCreate()
	 */
	protected function _sessionCreate()
	{
		// create auth info data
		$authData = array(
			'ipAddress'    => $this->req->ipAddress(),
			'userAgent'    => $this->req->server('HTTP_USER_AGENT'),
			'lastActivity' => time(),
			'sessionID'    => $this->_generateSessionID()
		);
		$authData = serialize($authData);
		if ( $this->_encryptSession === TRUE )
		{
			$encrypt  = Seezoo::$Importer->library('Encrypt');
			$authData = $encrypt->encode($authData);
		}
		
		$_SESSION[$this->_sessionAuthorizeDatakey] = $authData;
		$this->_sessionData = $_SESSION;
	}
	
	
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
	
	
	// --------------------------------------------------
	
	
	/**
	 * read session data
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionRead()
	 */
	protected function _sessionRead()
	{
		return @$_SESSION['userData'];
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct impelmenets
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionDestroy()
	 */
	protected function _sessionDestroy()
	{
		$_SESSION = array();
		$this->_setSessionCookie('', -1000);
		session_destroy();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_getAuthData()
	 */
	protected function _getAuthData()
	{
		if ( ! isset($_SESSION[$this->_sessionAuthorizeDatakey]) )
		{
			return FALSE;
		}
		
		$sess = $_SESSION[$this->_sessionAuthorizeDatakey];
		if ( $this->_encryptSession === TRUE )
		{
			$encrypt = Seezoo::$Importer->library('Encrypt');
			$sess    = $encrypt->decode($sess);
		}
		return $sess = @unserialize($sess);
	}
	
	
	// --------------------------------------------------
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionSave()
	 */
	public function _sessionSave()
	{
		$_SESSION['userData'] = $this->_sessionCurrentTemporaryData;
		session_write_close();
	}
	
}