<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * FTP wrapper class
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Ftp extends SZ_Driver implements Growable
{
	/**
	 * FTP connection handle
	 * @var resource
	 */
	protected $handle;
	
	
	/**
	 * Default config set
	 * @var array
	 */
	protected $_config = array(
		'username' => '',
		'password' => '',
		'hostname' => '',
		'port'     => 21,
		'passive'  => TRUE
	);
	
	
	/**
	 * Stack log messages
	 * @var array
	 */
	protected $logMessages = array();
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Ftp ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Ftp');
	}
	
	
	
	// --------------------------------------------------------------------
	
	
	
	public function __construct()
	{
		parent::__construct();
		
		$env       = Seezoo::getENV();
		$ftpConfig = $env->getConfig('FTP');
		
		$this->configure((array)$ftpConfig);
		$this->driver = ( extension_loaded('ftp') )
		                  ? $this->loadDriver('Php_ftp')
		                  : $this->loadDriver('Socket_ftp');
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Configure connection settings
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		$this->_config = array_merge($this->_config, $conf);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get settings
	 * 
	 * @access public
	 * @param  sring $key
	 * @return mixed
	 */
	protected function _get($key)
	{
		return ( isset($this->_config[$key]) )
		         ? $this->_config[$key]
		         : FALSE;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Try to connect FTP server
	 * 
	 * @access public
	 * @return bool
	 */
	public function connect()
	{
		// Connection initialize.
		$this->driver->close();
		
		$hostname = rtrim(preg_replace('|^[.+]://|', '', $this->_get('hostname')), '/');
		$port     = (int)$this->_get('port');
		
		if ( ! $this->driver->connect($hostname, $port) )
		{
			throw new SeezooException('FTP connection failed.');
		}
		
		$username = $this->_get('username');
		$password = $this->_get('password');
		$passive  = $this->_get('passive');
		
		if ( ! $this->driver->login($username, $password, $passive) )
		{
			throw new SeezooException('FTP login failed.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Change remote directory
	 * 
	 * @access public
	 * @param  string $path
	 * @return bool
	 */
	public function chdir($path = '')
	{
		if ( ! $this->driver->chdir($path) )
		{
			throw new SeezooException('FTP chdir command failed.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Create directory as remote server
	 * 
	 * @access public
	 * @param  string $directory
	 * @param  int $permission
	 * @return bool
	 */
	public function mkdir($directory, $permission = NULL)
	{
		if ( ! $this->driver->mkdir($directory, $permission) )
		{
			throw new SeezooException('MKDIR: Failed to make directory.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Send server from file
	 * 
	 * @access public
	 * @param  string $localFile
	 * @param  string $remoteFile
	 * @param  bool $binary
	 * @param  int $permission
	 */
	public function sendFile($localFile, $remoteFile, $binary = FALSE, $permission = NULL)
	{
		if ( ! $this->driver->sendFile($localFile, $remoteFile, $binary, $permission) )
		{
			throw new SeezooException('SENDFILE: Failed to send file.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Send server from stream
	 * 
	 * @access public
	 * @param  resource $stream
	 * @param  sring $remoteFile
	 * @param  bool $binary
	 * @param  int $permission
	 * @return bool
	 */
	public function sendStream(resource $stream, $remoteFile, $binary = FALSE, $permission = NULL)
	{
		if ( $this->driver->sendStream($stream, $remoteFile, $binary, $permission) )
		{
			throw new SeezooException('SENDFILE: Failed to send stream.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Send server from string buffer
	 * 
	 * @access public
	 * @param  string $string
	 * @param  string $remoteFile
	 * @param  bool $binary
	 * @param  int $permission
	 * @return bool
	 */
	public function sendBuffer($string, $remoteFile, $binary = FALSE, $permission = NULL)
	{
		if ( ! $this->driver->sendBuffer($string, $remoteFile, $binary, $permission) )
		{
			throw new SeezooException('SENDFILE: Failed to send buffer.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get remote file
	 * 
	 * @access public
	 * @param  string $remoteFile
	 * @param  string $saveTo
	 * @param  bool $binary
	 * @return bool
	 */
	public function getFile($remoteFile, $saveTo, $binary = FALSE)
	{
		if ( ! $this->driver->getFile($remoteFile, $saveTo, $binary) )
		{
			throw new SeezooException('GTEFILE: Failed to get remote file.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get remote file to memory string
	 * 
	 * @access public
	 * @param  string $remoteFile
	 * @param  bool $binary
	 * @return bool
	 */
	public function getFileBuffer($remoteFile, $binary = FALSE)
	{
		if ( ! ($buffer = $this->driver->getFileBuffer($remoteFile, $binary)) )
		{
			throw new SeezooException('GTEFILE: Failed to get remote file.');
		}
		
		return $buffer;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Rename remote file
	 * 
	 * @access public
	 * @param  string $oldName
	 * @param  string $newName
	 * @return bool
	 */
	public function rename($oldName, $newName)
	{
		if ( ! $this->driver->rename($oldName, $newName) )
		{
			throw new SeezooException('RENAME: Failed to rename file.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Move file
	 * 
	 * @access public
	 * @param  string $oldName
	 * @param  string $newName
	 * @return bool
	 */
	public function move($oldName, $newName)
	{
		return $this->rename($oldName, $newName);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Delete remote file
	 * 
	 * @access public
	 * @param  string $remoteFile
	 * @return bool
	 */
	public function deleteFile($remoteFile)
	{
		if ( ! $this->driver->deleteFile($remoteFile) )
		{
			throw new SeezooException('DELETE: Failed to delete file.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Delete directory
	 * 
	 * @access public
	 * @param  string $dirPath
	 * @return bool
	 */
	public function deleteDir($dirPath)
	{
		if ( ! $this->driver->deleteDir($dirPath) )
		{
			throw new SeezooException('RMDIR: Failed to remove directory.');
		}
		
		return $this;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Change file permission
	 * 
	 * @access public
	 * @param  string $path
	 * @param  int $permission
	 * @return bool
	 */
	public function chmod($path, $permission)
	{
		if ( ! is_resource($this->handle) )
		{
			return FALSE;
		}
		
		if ( ! @ftp_chmod($this->handle, (int)$permission, $path) )
		{
			return $this->_log('CHMOD: Failed to change permission');
		}
		
		return TRUE;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get simple file list
	 * 
	 * @access pubic
	 * @param  string $path
	 * @return mixed
	 */
	public function fileList($path)
	{
		if ( ! is_resource($this->handle) )
		{
			return FALSE;
		}
		
		return ftp_nlist($this->handle, $path);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get raw file list
	 * 
	 * @access public
	 * @param  string $path
	 * @return mixed
	 */
	public function rawFileList($path = '')
	{
		return $this->driver->rawFileList($path);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Close connection
	 * 
	 * @access public
	 */
	public function close()
	{
		$this->driver->close();
	}
	
	
	// --------------------------------------------------------------------
	
	/**
	 * Guard destructor
	 */
	public function __destruct()
	{
		$this->close();
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get log
	 * 
	 * @access public
	 * @param  bool $all
	 * @return mixed
	 */
	public function getLog($all = FALSE)
	{
		$log = $this->driver->getLog();
		
		return ( $all ) ? $log : end($log);
	}
}
