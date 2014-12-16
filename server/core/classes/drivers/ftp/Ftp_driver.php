<?php


abstract class SZ_Ftp_driver
{
	/**
	 * Stack log messages
	 * @var array
	 */
	protected $logMessages = array();
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Set log
	 * 
	 * @access protected
	 * @param  string $msg
	 */
	protected function _log($msg)
	{
		$this->logMessages[] = $msg;
	}
	
	
	// --------------------------------------------------------------------
	
	
	/**
	 * Get log
	 * 
	 * @access public
	 * @param  bool $all
	 * @return mixed
	 */
	public function getLog()
	{
		return $this->logMessages;
	}
	
	
	// abstract methods -------------------------------------------------------------
	
	
	abstract public function connect($host, $port);
	
	abstract public function login($username, $password, $passive = FALSE);
	
	abstract public function chdir($path = '');
	
	abstract public function mkdir($directory, $permission = NULL);
	
	abstract public function sendFile($localFile, $remoteFile, $binary = FALSE, $permission = NULL);
	
	abstract public function sendStream($stream, $remoteFile, $binary = FALSE, $permission = NULL);
	
	abstract public function sendBuffer($string, $remoteFile, $binary = FALSE, $permission = NULL);
	
	abstract public function sendDir($localDir, $remoteDir, $binary = FALSE, $permission = NULL);
	
	abstract public function getFile($remoteFile, $saveTo, $binary = FALSE);
	
	abstract public function getFileBuffer($remoteFile, $binary = FALSE);
	
	abstract public function rename($oldName, $newName);
	
	abstract public function move($oldName, $newName);
	
	abstract public function deleteFile($remoteFile);
	
	abstract public function deleteDir($dirPath);
	
	abstract public function chmod($path, $permission);
	
	abstract public function fileList($path);
	
	abstract public function rawFileList($path);
	
	abstract public function close();
}
