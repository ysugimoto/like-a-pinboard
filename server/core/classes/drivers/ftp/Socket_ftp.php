<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * FTP wrapper on socket connection class
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Socket_ftp extends SZ_Ftp_driver
{
	/**
	 * FTP connection handle
	 * @var resource
	 */
	protected $handle;
	
	protected $dataHandle;
	
	
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
		$this->handle = @fsockopen($host, $port, $errno, $errstr);
		if ( ! is_resource($this->handle) )
		{
			$this->_log('CONNECT: FTP server connection failed.');
			return FALSE;
		}
		
		socket_set_timeout($this->handle, 3);
		
		return TRUE;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Send command and get response
	 * 
	 * @access protected
	 * @param  sring $command
	 * @param  bool $singleLine
	 * @return mixed array/object
	 */
	protected function cmd($command, $singleLine = TRUE)
	{
		fputs($this->handle, $command . "\r\n");
		$lines = $this->getResponse($this->handle);
		
		return ( $singleLine ) ? end($lines) : $lines;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get socket response
	 * 
	 * @access protected
	 * @param  resource $socket
	 * @param  int $size
	 * @return array
	 */
	protected function getResponse($socket, $size = 512)
	{
		$lines = explode("\r\n", trim(stream_socket_recvfrom($socket, $size), "\r\n"));
		
		while ( FALSE !== ($reponse = $this->checkIsTimeout($socket)) )
		{
			if ( ! ($response = fgets($socket, $size)) )
			{
				break;
			}
			
			$lines[] = $response;
		}
		
		return array_map(array($this, '_parseResponse'), $lines);
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Check socket response is timeouted
	 * 
	 * @access protected
	 * @param  resource $socket
	 * @return bool
	 */
	protected function checkIsTimeout($socket)
	{
		$meta = stream_get_meta_data($socket);
		return !$meta['timed_out'];
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Parse string reponse to code/response stdClass
	 * 
	 * @access protected
	 * @param  string $response
	 * @return stdClass
	 */
	protected function _parseResponse($response)
	{
		$obj = new stdClass;
		list($obj->code, $obj->response) = ( preg_match('/^[0-9]{3}\s/', $response) )
		                                    ? explode(' ', $response, 2)
		                                    : array(NULL, $response);
		
		return $obj;
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
	public function login($username, $password, $passive = TRUE)
	{
		// Try to login
		$user = $this->cmd('USER ' . $username);
		if ( $user->code != 331 )
		{
			$this->_log('LOGIN: FTP login failed.');
			return FALSE;
		}
		$pass = $this->cmd('PASS ' . $password);
		if ( $pass->code != 230 )
		{
			$this->_log('LOGIN: FTP login failed.');
			return FALSE;
		}
		
		// Set PASV mode if needs
		if ( $passive === TRUE )
		{
			if ( ! $this->pasv() )
			{
				$this->_log('PASV: Failed to entering pasv mode.');
				return FALSE;
			}
		}
		else
		{
			$this->dataHandle = $this->handle;
		}
		return TRUE;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Ser passive mode
	 * 
	 * @access public
	 * @return bool
	 */
	public function pasv()
	{
		$passive = $this->cmd('PASV');
		if ( $passive->code != 227
		     || ! preg_match('/.+\(([0-9]+),([0-9]+),([0-9]+),([0-9]+),([0-9]+),([0-9]+)/', $passive->response, $match) )
		{
			return FALSE;
		}
		
		// Connect data streaming socket
		$pasv = array_map('intval', array_slice($match, 1));
		
		$this->dataHandle = @fsockopen(
			sprintf('%d.%d.%d.%d', $pasv[0], $pasv[1], $pasv[2], $pasv[3]),
			($pasv[4] << 8) + $pasv[5],
			$errno,
			$errstr,
			10 
		);
		
		if ( ! $this->dataHandle )
		{
			return FALSE;
		}
		
		socket_set_timeout($this->dataHandle, 3);
		
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
		if ( $path === '' )
		{
			$this->_log('CHDIR: Failed to change directory.');
			return FALSE;
		}
		
		$cd = $this->cmd('CWD ' . $path);
		if ( $cd->code != 250 )
		{
			$this->_log($cd->code . ': ' . $cd->response);
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
		if ( $directory === '' )
		{
			$this->_log('MKDIR: Failed to make directory.');
			return FALSE;
		}
		
		$mkdir = $this->cmd('MKD ' . $directory);
		if ( $mkdir->code != 257 )
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
		
		// clone filename if remote file is directory
		if ( substr($remoteFile, -1, 1) === '/' )
		{
			$remoteFile .= basename($localFile);
		}
		
		return $this->sendStream(fopen($localFile, 'rb'), $remoteFile, $binary, $permission);
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
		     || ! is_resource($this->dataHandle)
		     || ! is_resource($stream) )
		{
			return FALSE;
		}
		
		$this->cmd(( $binary ) ? 'TYPE I' : 'TYPE A');
		$put = $this->cmd('STOR ' . $remoteFile);
		if ( $put->code != 150 )
		{
			$this->_log('SENDFILE: Failed to send file.');
			return FALSE;
		}
		
		rewind($stream);
		
		$string = '';
		while ( ! feof($stream) )
		{
			$string = fgets($stream, 512);
			fwrite($this->dataHandle, $string);
		}
		
		fclose($stream);
		
		if ( $permission )
		{
			$this->chmod($remoteFile, $permission);
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
		if ( ! is_resource($this->handle)
		     || ! is_resource($this->dataHandle) )
		{
			return FALSE;
		}
		
		$this->cmd(( $binary ) ? 'TYPE I' : 'TYPE A');
		
		$put = $this->cmd('STOR ' . $remoteFile);
		if ( $put->code != 150 )
		{
			$this->_log('SENDFILE: Failed to send file.');
			return FALSE;
		}

		$length = 0;
		$dest   = strlen($string);
		
		// Write to temp stream
		do
		{
			$length += fwrite($this->dataHandle, $string);
		}
		while ( $length <= $dest );
		
		if ( $permission )
		{
			$this->chmod($remoteFile, $permission);
		}
		
		return TRUE;
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
		
		$buffer = $this->getFileBuffer($remoteFile, $binary);
		
		return (bool)file_put_contents($saveTo, $buffer);
		
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
		$this->cmd(( $binary ) ? 'TYPE I' : 'TYPE A');
		$retr = $this->cmd('RETR ' . $remoteFile);
		if ( $retr->code != 150 )
		{
			$this->_log($retr->code . ': ' . $retr->response);
			return FALSE;
		}
		
		// Get data stream
		$lines = $this->getResponse($this->dataHandle);
		
		// And seek pointer to end
		$this->getResponse($this->handle);
		
		$buffer = array();
		foreach ( $lines as $line )
		{
			$buffer[] = $line->response;
		}
		
		return implode("\r\n", $buffer);
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
		$renameFrom = $this->cmd('RNFR ' . $oldName);
		if ( $renameFrom->code != 350 )
		{
			$this->_log('RENAME-FROM: Failed to rename file.');
			return FALSE;
		}
		
		$renameTo = $this->cmd('RNTO ' . $newName);
		if ( $renameTo->code != 250 )
		{
			$this->_log('RENAME-TO: Failed to rename file.');
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
		$del = $this->cmd('DELE ' . $remoteFile);
		if ( $del->code != 250 )
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
		
		$dirPath = rtrim($dirPath, '/');
		
		// Remove dest directory
		$rmdir = $this->cmd('RMD ' . $dirPath);
		if ( $rmdir->code != 250 )
		{
			$this->_log($rmdir->code . ':' . $rmdir->response);
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
		$chmod = $this->cmd('SITE CHMOD ' . (string)$permission . ' ' . $path);
		if ( $chmod->code != 200 )
		{
			$this->_log($chmod->code . ': ' . $chmod->response);
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
		$list = array();
		foreach ( $this->rawFileList($path) as $file )
		{
			$list[] = $file->name;
		}
		
		return $list;
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
		$ls     = $this->cmd(rtrim('LIST ' . $path));
		if ( $ls->code != 150 )
		{
			$this->_log($ls->code . ': ' . $ls->response);
			return FALSE;
		}
		
		// Get response inline
		$lines = $this->getResponse($this->dataHandle);
		
		// And seek main socket pointer to end
		$this->getResponse($this->handle);
		
		if ( ! empty($path) )
		{
			$path = trail_slash($path);
		}
		
		foreach ( $lines as $raw )
		{
			if ( ctype_digit($raw->code) )
			{
				continue;
			}
			$stat = new stdClass;
			if ( ! preg_match('/^([\-rwdlx]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+([0-9]+)\s+(.+)\s(.+)$/', $raw->response, $matches) )
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
			fclose($this->handle);
		}
		
		if ( is_resource($this->dataHandle) )
		{
			fclose($this->dataHandle);
		}
	}
}
