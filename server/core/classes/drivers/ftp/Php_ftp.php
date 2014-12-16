<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * PHP-builtin FTP wrapper class
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Php_ftp extends SZ_Ftp_driver
{
	/**
	 * FTP connection handle
	 * @var resource
	 */
	protected $handle;
	
	
	/**
	 * Try to connect FTP server
	 * 
	 * @access public
	 * @param  string $host
	 * @param  int $port
	 * @return bool
	 */
	public function connect($host, $port)
	{
		// Connection initialize.
		$this->close();
		
		// Try to connect FTP server
		$this->handle = @ftp_connect($host, $port);
		if ( ! is_resource($this->handle) )
		{
			$this->_log('CONNECT: FTP server connection failed.');
			return FALSE;
		}
		
		return TRUE;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Try to login
	 * 
	 * @access public
	 * @param  string $username
	 * @param  string $password
	 * @param  bool   $passive
	 * @return bool
	 */
	public function login($username, $password, $passive = FALSE)
	{
		// Try to login
		$login = @ftp_login($this->handle, $username, $password);
		if ( ! $login )
		{
			$this->_log('LOGIN: FTP login failed.');
			return FALSE;
		}
		
		// Set PASV mode if needs
		if ( $passive === TRUE )
		{
			if ( ! ftp_pasv($this->handle, TRUE) )
			{
				$this->_log('PASSIV: FTP server rejected PASV mode.');
				return FALSE;
			}
		}
		return TRUE;
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
		if ( $path === ''
		     || ! is_resource($this->handle)
		     || ! @ftp_chdir($this->handle, $path)
		)
		{
			$this->_log('CHDIR: Failed to change directory.');
			return FALSE;
		}
		
		return TRUE;
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
		if ( $directory === ''
		     || ! is_resource($this->handle)
		     || ! @ftp_mkdir($this->handle, $directory)
		)
		{
			$this->_log('MKDIR: Failed to make directory.');
			return FALSE;
		}
		
		return TRUE;
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
		if ( ! is_resource($this->handle) )
		{
			return FALSE;
		}
		
		if ( ! file_exists($localFile) )
		{
			$this->_log('UPLOAD: Local file is not exists.');
			return FALSE;
		}
		
		if ( is_dir($localFile) )
		{
			return $this->sendDir($localFile, $remotePath, $binary, $permission);
		}
		
		$mode = ( $binary ) ? FTP_BINARY : FTP_ASCII;
		
		// clone filename if remote file is directory
		if ( substr($remoteFile, -1, 1) === '/' )
		{
			$remoteFile .= basename($localFile);
		}
		
		if ( ! @ftp_put($this->handle, $remoteFile, $localFile, $mode) )
		{
			$this->_log('SENDFILE: Failed to send file.');
			return FALSE;
		}
		
		if ( ! is_null($permission) )
		{
			$this->chmod($remoteFile, (int)$permission);
		}
		
		return TRUE;
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
	public function sendStream($stream, $remoteFile, $binary = FALSE, $permission = NULL)
	{
		if ( ! is_resource($this->handle)
		     || ! is_resource($stream) )
		{
			return FALSE;
		}
		
		$mode = ( $binary ) ? FTP_BINARY : FTP_ASCII;
		rewind($stream);
		
		if ( ! @ftp_fput($this->handle, $remoteFile, $stream, $mode) )
		{
			fclose($stream);
			$this->_log('SENDFILE: Failed to send stream.');
			return FALSE;
		}
		
		fclose($stream);
		
		if ( ! is_null($permission) )
		{
			$this->chmod($remoteFile, (int)$permission);
		}
		
		return TRUE;
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
		if ( ! is_resource($this->handle) )
		{
			return FALSE;
		}
		
		// create file stream
		$stream = fopen('php://temp', 'wb');
		$length = 0;
		$dest   = strlen($string);
		
		// Write to temp stream
		do
		{
			$length += fwrite($stream, $string);
		}
		while ( $length <= $dest );
		// And rewind
		rewind($stream);
		
		return $this->sendStream($stream, $remoteFile, $binary, $permission);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Send server from directory recursive
	 * 
	 * @access public
	 * @param  string $localDir
	 * @param  string $remoteDir
	 * @param  bool $binary
	 * @param  int $pemission
	 * @return bool
	 */
	public function sendDir($localDir, $remoteDir, $binary = FALSE, $permission = NULL)
	{
		if ( ! is_resource($this->handle) || ! is_dir($localDir) )
		{
			return FALSE;
		}
		
		if ( ! $this->chdir($remoteDir) )
		{
			if ( ! $this->mkdir($remoteDir, $permission) )
			{
				return FALSE;
			}
			$this->chdir($remoteDir);
		}
		
		$localDir  = trail_slash($localDir);
		$remoteDir = trail_slash($remoteDir);
		$isSuccess = FALSE;
		try
		{
			$dir = new DirectoryIterator($localDir);
			
			foreach ( $dir as $file )
			{
				if ( $file->isDot() )
				{
					continue;
				}
				
				if ( $file->isDir() )
				{
					$this->sendDir($localDir . (string)$file, $remoteDir . (string)$file, $binary, $permission);
				}
				else if ( $file->isFile() )
				{
					$this->sendFile($localDir . (string)$file, $remoteDir . (string)$file, $binary, $permission);
				}
			}
			$isSuccess = TRUE;
		}
		catch ( Exception $e )
		{
			$this->_log('SENDDIR: Failed to send directory recursive.');
		}
		
		return $isSuccess;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get remote file and save to localfile
	 * 
	 * @access public
	 * @param  string $removeFile
	 * @param  string $saveTo
	 * @param  book $binary
	 * @return bool
	 */
	public function getFile($remoteFile, $saveTo = '', $binary = FALSE)
	{
		if ( $saveTo === '' )
		{
			$saveTo = basename($remoteFile);
		}
		
		if ( ! really_writable($saveTo) )
		{
			return FALSE;
		}
		
		if ( ! is_resource($this->handle)
		     || ! ftp_get($this->handle, $saveTo, $remoteFile, ( $binary ) ? FTP_BINARY : FTP_ASCII) )
		{
			return FALSE;
		}
		
		return TRUE;
		
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get remove file on memory buffer
	 * 
	 * @access public
	 * @param  string $remoteFile
	 * @param  bool $binary
	 * @return string
	 */
	public function getFileBuffer($remoteFile, $binary = FALSE)
	{
		$fp = fopen('php://temp', "wb");
		
		if ( ! is_resource($this->handle)
		     || ! ftp_fget($this->handle, $fp, $remoteFile, ( $binary ) ? FTP_BINARY : FTP_ASCII) )
		{
			return FALSE;
		}
		
		rewind($fp);
		$ret = "";
		while ( ! feof($fp) )
		{
			$ret .= fgets($fp);
		}
		
		fclose($fp);
		
		return $ret;
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
		if ( ! is_resource($this->handle)
		     || ! @ftp_rename($this->handle, $oldName, $newName) )
		{
			$this->_log('RENAME: Failed to rename file.');
			return FALSE;
		}
		
		return TRUE;
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
		if ( ! is_resource($this->handle)
		     || ! @ftp_delete($this->handle, $remoteFile) )
		{
			$this->_log('DELETE: Failed to delete file.');
			return FALSE;
		}
		
		return TRUE;
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
		if ( ! is_resource($this->handle) )
		{
			return FALSE;
		}
		
		$dirPath = trail_slash($dirPath);
		$list    = $this->rawFileList($dirPath);
		
		// Remove child files recursive
		if ( is_array($list) )
		{
			foreach ( $list as $file )
			{
				if ( $file->isDirectory )
				{
					$this->deleteDir($dirPath . $file->name);
				}
				else
				{
					$this->deleteFile($dirPath . $file->name);
				}
			}
		}
		
		// Remove dest directory
		if ( ! @ftp_rmdir($this->handle, $dirPath) )
		{
			$this->_log('RMDIR: Failed to remove directory.');
			return FALSE;
		}
		
		return TRUE;
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
		if ( ! is_resource($this->handle)
		     || ! @ftp_chmod($this->handle, (int)$permission, $path) )
		{
			$this->_log('CHMOD: Failed to change permission');
			return FALSE;
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
	public function rawFileList($path)
	{
		$return = array();
		$list   = ftp_rawlist($this->handle, $path);
		
		if ( is_array($list) )
		{
			if ( ! empty($path) )
			{
				$path = trail_slash($path);
			}
			
			foreach ( $list as $raw )
			{
				$stat = new stdClass;
				if ( ! preg_match('/^([\-rwdlx]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.+)\s(.+)$/', $raw, $matches) )
				{
					$this->_log('RAWLIST: invalid raw list returns.');
					return FALSE;
				}
				if ( $matches[7] === '.' || $matches[7] === '..' )
				{
					continue;
				}
				$symbol = substr(strtolower($matches[1]), 0, 1);
				$stat->isDirectory = ( $symbol === 'd') ? TRUE : FALSE;
				$stat->isLink      = ( $symbol === 'l') ? TRUE : FALSE;
				$stat->isFile      = ! $stat->isDirectory;
				$stat->name        = $matches[7];
				$stat->size        = (int)$matches[5];
				$stat->fullPath    = $path . $matches[7];
				$return[]          = $stat;
				}
		}
		
		return $return;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Close connection
	 * 
	 * @access public
	 */
	public function close()
	{
		if ( is_resource($this->handle) )
		{
			@ftp_close($this->handle);
		}
	}
}
