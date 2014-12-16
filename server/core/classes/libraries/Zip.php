<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * create Zip archive or extract supports
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Zip extends SZ_Driver implements Growable
{
	
	public function __construct()
	{
		parent::__construct();
		
		// feature detection
		$driverClass = $this->_featureDetection();
		// and load Dirver
		$this->driver = $this->loadDriver($driverClass . '_zip');
	}
	
	
	// --------------------------------------------------
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Zip');
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add directory queue
	 * 
	 * @access public
	 * @param  string $dirName
	 */
	public function addDir($dirName)
	{
		$this->driver->addDir($dirName);
		return $this;
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
		$this->driver->addFile($file, $localName);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set extract target archive
	 * 
	 * @access public
	 * @param  string $archiveName
	 */
	public function setArchive($archiveName)
	{
		$this->driver->setArchive($archiveName);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set extract to target directory
	 * 
	 * @access public
	 * @param  string $dir
	 */
	public function setExtractDir($dir)
	{
		$this->driver->setExtractDir($dir);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Detect enable backend driver
	 * 
	 * @access protected
	 */
	protected function _featureDetection()
	{
		$feature = ucfirst(get_config('zip_mode'));
		if ( $feature === 'auto' )
		{
			// auto detection
			if ( class_exists('ZipArchive') )
			{
				// PHP5.2.0+ supports built-in class
				$feature = 'Php';
			}
			// elseif, Does zlib extension enabled?
			else if ( extension_loaded('zlib') )
			{
				// use Manual zip archive/extract class
				$feature = 'Manual';
			}
			// else, can't works on this env.
			else
			{
				throw new Exception('Sorry, your environment can\'t use Zip Library. need to zlib extension loaded at least.');
			}
		}
		
		return $feature;
	}
}