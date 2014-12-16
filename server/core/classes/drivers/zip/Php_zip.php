<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * PHP Zip driver ( supports PHP 5.2.0 or newer )
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Php_zip extends SZ_Zip_driver
{
	
	/**
	 * Zip archive handle
	 * @var object ZipArchive
	 */
	protected $handle;
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Archive zip
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function _archive()
	{
		$this->handle = new ZipArchive();
		$mode = ( $this->_isOverWrite === TRUE ) ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE;
		
		foreach ( $this->_addDirectories as $addDir )
		{
			if ( $this->handle->open($this->_archiveName, $mode) !== TRUE )
			{
				throw new Exception('Zip archive ' . $this->_archiveName . ' can\'t create!');
				return FALSE;
			}
			$this->_addDirToZip($addDir, basename($addDir));
			$this->handle->close();
		}
		foreach ( $this->_addFiles as $addFile )
		{
			if ( $this->handle->open($this->_archiveName, $mode) !== TRUE )
			{
				throw new Exception('Zip archive ' . $this->_archiveName . ' can\'t create!');
				return FALSE;
			}
			$this->handle->addFile($addFile[0], $addFile[1]);
			$this->handle->close();
		}
		
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add directory to zip handle
	 * 
	 * @access protected
	 * @param  string $dir
	 * @param  string $dirName
	 */
	protected function _addDirToZip($dir, $dirName)
	{
		$this->handle->addEmptyDir($dirName);
		$dir = rtrim($dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		foreach ( $this->_getFileList($dir) as $key => $file )
		{
			// If key is string and value is array, recursive directory
			if ( is_string($key) )
			{
				$this->_addDirToZip($file, $dirName . '/' . basename($file));
			}
			else
			{
				$this->handle->addFile($file, $dirName . '/' . basename($file));
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Extract zip archive
	 * 
	 * @access protected
	 * @param  string $archiveName
	 * @param  string $dest
	 * @return bool
	 */
	protected function _extract($archiveName = '', $dest = '')
	{
		$this->handle = new ZipArchive();
		if ( $this->handle->open($this->_archiveName, ZIPARCHIVE::OVERWRITE) !== TRUE )
		{
			throw new Exception('Exctract failed!');
			return FALSE;
		}
		
		$this->handle->extractTo($this->_extractDir);
		$this->handle->close();
		
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * clear handle
	 * 
	 * @access potected
	 */
	protected function _cleanup()
	{
		if ( is_resource($this->handle) )
		{
			@$this->handle->close();
		}
		$this->_archiveName    = '';
		$this->_addFiles       = array();
		$this->_addDirectories = array();
	}
}