<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session manages with File
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_File_session extends SZ_Session_driver
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
	 * session file name prefix
	 * @var string
	 */
	protected $_filePrefix;
	
	
	/**
	 * session store dest filepath
	 * @var string
	 */
	protected $_storePath;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->_filePrefix = $this->env->getConfig('session_filename_prefix');
		$this->_storePath  = $this->env->getConfig('session_file_store_path');

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
		$authData = array(
			'sessionID'    => $sessid,
			'ipAddress'    => $this->req->ipAddress(),
			'userAgent'    => $this->req->server('HTTP_USER_AGENT'),
			'lastActivity' => $this->_time
		);
		$this->_authData  = $authData;
		$this->_sessionID = $sessid;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct impelmenets
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionDestroy()
	 */
	protected function _sessionDestroy()
	{
		$file = $this->_storePath . $this->_filePrefix . $this->_sessionID;
		if ( file_exists($file) )
		{
			unlink($file);
		}
		$this->_setSessionCookie('', -100);
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
			$sess = $encrypt->decode($sess);
		}
		
		if ( ! $sess )
		{
			return FALSE;
		}
		$len = strlen($this->_sessionAuthorizeDatakey);
		$this->_sessionID = substr($sess, $len);
		
		$file = $this->_storePath . $this->_filePrefix . $this->_sessionID;
		if ( ! file_exists($file) )
		{
			return FALSE;
		}
		
		$handle = @fopen($file, 'rb');
		$data   = '';
		if ( $handle )
		{
			flock($handle, LOCK_SH);	
			while ( ! feof($handle) )
			{
				$data .= fgets($handle, 4096);
			}
			flock($handle, LOCK_UN);
			fclose($handle);
		}
		list($authData, $userData) = explode("\n", $data, 2);
		
		if ( $this->_encryptSession === TRUE )
		{
			$encrypt  = Seezoo::$Importer->library('Encrypt');
			$authData = $encrypt->decode($authData);
			$userData = $encrypt->decode($userData);
		}
		
		$this->_authData = @unserialize($authData);
		$this->_userData = @unserialize($userData);
		return $this->_authData;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * read session data
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionRead()
	 */
	protected function _sessionRead()
	{
		return ( $this->_userData ) ? $this->_userData : array();
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
			$this->_authData['sessionID']   = $this->_sessionID;
		}
		
		if ( ! really_writable($this->_storePath) )
		{
			throw new Exception('Session file is not writeable!');
		}
		
		$authKey  = $this->_sessionAuthorizeDatakey . $this->_sessionID;//serialize($this->_authData) . $this->_sessionAuthorizeDatakey;
		$authData = serialize($this->_authData);
		$data     = serialize($this->_sessionCurrentTemporaryData);
		
		if ( $this->_encryptSession === TRUE )
		{
			$encrypt  = Seezoo::$Importer->library('Encrypt');
			$data     = $encrypt->encode($data);
			$authKey  = $encrypt->encode($authKey);
			$authData = $encrypt->encode($authData); 
			
		}
		
		if ( FALSE !== ($handle = @fopen($this->_storePath . $this->_filePrefix . $this->_sessionID, 'wb')) )
		{
			flock($handle, LOCK_EX);
			fwrite($handle, $authData . "\n" . $data);
			flock($handle, LOCK_UN);
			fclose($handle);
		}
		
		// Is session_id updated?
		if ( $sessID !== $this->_sessionID )
		{
			// delete old session file if exsits
			if ( file_exists($this->_storePath . $this->_filePrefix . $sessID) )
			{
				unlink($this->_storePath . $this->_filePrefix . $sessID);
			}
		}
		
		$this->_setSessionCookie($authKey);
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
		
		if ( file_exists($this->_storePath . $this->_filePrefix . $sessID) )
		{
			// guard: If session already exists, generate other sessID.
			return $this->_generateSessionID();
		}
		return $sessID;
	}
	
}