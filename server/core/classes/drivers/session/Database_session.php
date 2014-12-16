<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Session manages with Database
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Database_session extends SZ_Session_driver
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
	 * database connection instance
	 * @var Database
	 */
	protected $db;
	
	
	/**
	 * database tablename
	 * @var string
	 */
	protected $_dbTableName;
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$this->_dbTableName = $this->env->getConfig('session_db_tablename');
		$this->db = Seezoo::$Importer->database();

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
			'user_agent'    => substr($this->req->server('HTTP_USER_AGENT'), 0, 50),
			'last_activity' => $this->_time,
			'user_data'     => ''
		);
		
		$this->db->insert($this->_dbTableName, $authData);
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
		$this->db->delete($this->_dbTableName, array('session_id' => $this->_sessionID));
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
			$sess = $encrypt->decode($sess);
		}
		
		if ( ! $sess )
		{
			return FALSE;
		}
		$len  = strlen($this->_sessionAuthorizeDatakey);
		$sess = substr($sess, $len);
		
		$sql =
				'SELECT '
				.	'session_id, '
				.	'ip_address, '
				.	'user_agent, '
				.	'last_activity, '
				.	'user_data '
				.'FROM ' . $this->db->prefix() . $this->_dbTableName . ' '
				.'WHERE '
				.	'session_id = ? '
				.'LIMIT 1';
			;
		$query = $this->db->query($sql, array($sess));

		if ( $query->row() )
		{
			$result = $query->row();
			$authData = array(
				'sessionID'    => $sess,
				'ipAddress'    => $result->ip_address,
				'userAgent'    => $result->user_agent,
				'lastActivity' => $result->last_activity
			);
			//$this->_authData = $authData;
			$this->_userData = $result->user_data;
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
		if ( ! $this->_userData )
		{
			$sql =
				'SELECT '
				.	'user_data '
				.'FROM ' . $this->_dbTableName .' '
				.'WHERE '
				.	'session_id = ? '
				.'LIMIT 1';
			$query = $this->db->query($sql, array($this->_sessionID));
			
			if ( $query->row() )
			{
				$result = $query->row();
				$userData = @unserialize($result->user_data);
			}
			else
			{
				$userData = array();
			}
		}
		else
		{
			$userData = @unserialize($this->_userData);
		}
		return $userData;
		
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/session/SZ_Session_driver::_sessionSave()
	 */
	public function _sessionSave()
	{
		$sess = $this->_sessionID;
		// update sessionID when lastActivity is over
		if ( $this->_authData['lastActivity'] + $this->env->getConfig('session_update_time') < $this->_time )
		{
			$this->_sessionID                = $this->_generateSessionID();
			$this->_authData['lastActivity'] = $this->_time;
			$this->_authData['session_id']   = $this->_sessionID;
		}
		
		//$sessID   = $this->_sessionID;
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
		
		$db = DB::grow();
		$db->update($this->_dbTableName, $updateData, array('session_id' => $sess));
		
		$this->_setSessionCookie($authKey);
	}
}