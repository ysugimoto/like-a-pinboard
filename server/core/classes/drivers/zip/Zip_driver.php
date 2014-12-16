<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Zip driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

abstract class SZ_Zip_driver
{
	/**
	 * Archive target name
	 * or extract target archive name
	 * @var string
	 */
	protected $_archiveName;
	
	
	/**
	 * Extract target directory path
	 * @var string
	 */
	protected $_extractDir;
	
	
	/**
	 * Archive create with overwrite?
	 * @var bool
	 */
	protected $_isOverWrite    = FALSE;
	
	
	/**
	 * Queue add archive files
	 * @var array
	 */
	protected $_addFiles       = array();
	
	
	/**
	 * Queue add archive directory
	 * @var array
	 */
	protected $_addDirectories = array();
	
	
	/**
	 * @abstract execute create archive mthod
	 */
	abstract protected function _archive();
	
	
	/**
	 * @abstract execute extract archive method
	 */
	abstract protected function _extract(); 
	
	
	/**
	 * @abstract cleanup method..
	 */
	abstract protected function _cleanup();
	
	
	// --------------------------------------------------
	
	
	public function __construct()
	{
		// nothing to do...
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * syntax driver archive
	 * 
	 * @access public
	 * @param  string $archivePath
	 * @param  bool   $overwrite
	 * @throws Exception
	 */
	public function archive($archivePath = '', $overwrite = TRUE)
	{
		if ( ! empty($archivePath) )
		{
			$this->_archiveName = $archivePath;
		}
		
		if ( empty($this->_archiveName) )
		{
			throw new Exception('Zip archive name must not be empty!');
			return FALSE;
		}
		// create target file is writeable?
		else if ( ! really_writable($this->_archiveName) )
		{
			throw new Exception('Zip archive save target ' . $this->_archiveName . ' can\'t writable!');
			return FALSE;
		}
		// Does process allowed overwrite and archive exists?
		else if ( file_exists($this->_archiveName) && $overwrite === FALSE )
		{
			throw new Exception('Create target Zip archive ' . $this->_archiveName . ' already exsits. change other name!');
			return FALSE;
		}
		
		$this->_isOverWrite = $overwrite;
		
		// execute!
		return $this->_archive();
	}
	
	
	
	// --------------------------------------------------
	
	
	/**
	 * syntax driver extract
	 * 
	 * @access public
	 * @param  string $archiveName
	 * @param  string $dest
	 */
	public function extract($archiveName = '', $dest = '')
	{
		if ( ! empty($archiveName) )
		{
			$this->_archiveName = $archiveName;
		}
		
		if ( empty($this->_archiveName) )
		{
			throw new Exception('Extract archive name must not be empty!');
			return FALSE;
		}
		else if ( ! file_exists($this->_archiveName) )
		{
			throw new Exception('Archive file not found!');
			return FALSE;
		}
		
		if ( ! empty($dest) )
		{
			$this->_extractDir = rtrim($dest, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		}
		
		if ( empty($this->_extractDir) )
		{
			throw new Exception('Extract destination directory must not be empty!');
			return FALSE;
		}
		// extract target is writeable?
		else if ( ! really_writable($this->_extract()) )
		{
			throw new Exception('Extract destination directory can\'t writable!');
			return FALSE;
		}
		
		// execute!
		return $this->_extract();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set target archive
	 * 
	 * @access public
	 * @param  string $file
	 */
	public function setArchive($file)
	{
		$this->_archiveName = $file;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set exract target directory
	 * 
	 * @access public
	 * @param  string $dir
	 */
	public function setExtractDir($dir)
	{
		$this->_extractDir = $dir;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add File to Zip queue
	 * 
	 * @access public
	 * @param  string  $file
	 * @param  string $localName
	 */
	public function addFile($file, $localName = '')
	{
		$finfo = array(
			$file,
			( ! empty($localName) ) ? $localName : basename($file)
		);
		$this->_addFiles[] = $finfo;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add Directory to Zip queue
	 * 
	 * @access public
	 * @param  string $dirName
	 */
	public function addDir($dirName)
	{
		$this->_addDirectories[] = $dirName;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * syntax driver cleanup
	 */
	public function cleanup()
	{
		$this->_cleanup();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get File or directory list From target Directory
	 * ( uses Driver )
	 * 
	 * @access protected
	 * @param  string $dirPath
	 */
	protected function _getFileList($dirPath)
	{
		if ( ! is_dir($dirPath) )
		{
			return array();
		}
		
		$ret     = array();
		$dp      = dir($dirPath);
		$dirPath = rtrim($dirPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
		
		while ( FALSE !== ( $file = $dp->read()) )
		{
			// "." or ".." or ".+" file is ignore
			if ( preg_match('#\A\.#u', $file) )
			{
				continue;
			}
			
			$path = $dirPath . $file;
			// if file is dirertory?
			if ( is_dir($path) )
			{
				$ret[basename($path)] = $path;
			}
			else
			{
				$ret[] = $path;
			}
		}
		$dp->close();
		
		return $ret;
	}
}