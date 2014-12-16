<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Filesystem helper
 * 
 * @package  Seezoo-Framework
 * @category Helpers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_FilesystemHelper implements Growable
{
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->helper('Filesystem');
	}
	
	
	/**
	 * Read file
	 * 
	 * @access public
	 * @param  string $filePath
	 * @param  bool $sendBrowser
	 * @return void or string
	 */
	public function read($filePath, $sendBrowser = FALSE)
	{
		if ( ! file_exists($filePath) )
		{
			return FALSE;
		}
		
		return ( $sendBrowser === TRUE )
		         ? readfile($filePath)
		         : file_get_contents($filePath);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Write file
	 * 
	 * @access public
	 * @param  string $filePath
	 * @param  string $data
	 * @param  bool $append
	 * @return bool
	 */
	public function write($filePath, $data = '', $append = FALSE)
	{
		if ( ! really_writable($filePath) )
		{
			return FALSE;
		}
		
		$mode = ( $append === TRUE ) ? 'ab' : 'wb';
		$file = new SplFileObject($filePath, $mode);
		$file->flock(LOCK_EX);
		$byte = $file->fwrite($data);
		$file->flock(LOCK_UN);
		
		return ( $byte > 0 ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Delete file/directory
	 * 
	 * @access public
	 * @param  string $filePath
	 */
	public function delete($filePath)
	{
		$file = new XSplFileInfo($filePath);
		if ( $file->isDir() )
		{
			return @rmdir($file->__toString());
		}
		else
		{
			return @unlink($file->__toString());
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get file info
	 * 
	 * @access public
	 * @param  string $filePath
	 * @param  bool $index
	 * @return mixed
	 */
	public function info($filePath, $index = FALSE)
	{
		$file = new XSplFileInfo($filePath);
		if ( ! $file->exists() )
		{
			return FALSE;
		}
		
		$info = new stdClass;
		$info->size       = $file->getSize();
		$info->mtime      = $file->getMTime();
		$info->name       = $file->getBasename();
		$info->dir        = $file->getPath();
		$info->trail_dir  = $file->getPath() . '/';
		$info->writeble   = $file->isWritable();
		$info->readable   = $file->isReadable();
		$infp->executable = $file->isExecutable();
		
		if ( $index && isset($info->{$index}) )
		{
			return $info->{$index};
		}
		return $info;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get file list ( first level )
	 * @param string $dirPath
	 * @param bool   $getFullPath
	 * @param array  $filterExtension
	 * @return mixed
	 */
	public function fileList($dirPath, $fileOnly = FALSE, $getFullPath = FALSE, $filterExtension = array(''))
	{
		$ret = array();
		try
		{
			$dirPath = trail_slash($dirPath);
			$dir     = new DirectoryIterator($dirPath);
			foreach ( $dir as $file )
			{
				$append = FALSE;
				if ( $file->isDot() )
				{
					continue;
				}
				if ( $file->isDir() && $fileOnly === FALSE )
				{
					$append = TRUE;
				}
				else if ( $file->isFile() )
				{
					$exp = explode('.', $file);
					if ( ! in_array(end($exp), $filterExtension) )
					{
						$append = TRUE;
					}
				}
				
				if ( $append !== FALSE )
				{
					$ret[] = ( $getFullPath ) ? $dirPath . $file : (string)$file;
				}
			}
		}
		catch ( UnexpectedValueException $e )
		{
			throw $e;
		}
		
		return $ret;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get file/directory list
	 * 
	 * @access public
	 * @param  string $dirPath
	 * @param  bool $getFullPath
	 * @return mixed
	 */
	public function directoryMap($dirPath, $getFullPath = FALSE)
	{
		if ( ! is_dir($dirPath) )
		{
			return FALSE;
		}
		$ret     = array();
		$dp      = dir($dirPath);
		$dirPath = trail_slash($dirPath);
		
		while ( FALSE !== ( $file = $dp->read()) )
		{
			// "." or ".." or ".+" file is ignore
			if ( preg_match('#\A\.#u', $file) )
			{
				continue;
			}
			
			$path = $dirPath . $file;
			// File is dirertory?
			if ( is_dir($path) )
			{
				$ret[basename($file)] = $this->directoryMap($path, $getFullPath);
			}
			else
			{
				$ret[] = ( $getFullPath ) ? $path : $file;
			}
		}
		$dp->close();
		return $ret;
	}
}