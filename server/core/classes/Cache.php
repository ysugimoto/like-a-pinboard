<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Cache management class
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Cache implements Growable
{
	/**
	 * Cache destination directory
	 * @var string
	 */
	protected $_cacheDir;
	
	
	/**
	 * Serialize marking identifier
	 * @var string
	 */
	protected $_identifier = '<!--@serialized-->';
	
	/**
	 * Cache file prefix constants
	 * @var string
	 */
	const PREFIX_DB     = 'db_';
	const PREFIX_OUTPUT = 'out_';
	const PREFIX_DATA   = 'data_';


	public function __construct()
	{
		$this->_cacheDir = ETCPATH . 'caches/';
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Cache ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Cache');
	}
	
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get the cache
	 * 
	 * @access public
	 * @param string $type
	 * @param string $key
	 * @param mixed $compareTime
	 * @return mixed
	 */
	public function get($type, $key)
	{
		$prefix = $this->_getType($type);
		if ( ! $prefix )
		{
			return FALSE;
		}
		list($file,) = $this->_encode($prefix, $key);
		
		// Does cache file exists?
		if ( ! file_exists($file) )
		{
			return FALSE;
		}
		$cache = file_get_contents($file);
		
		// Does cache data contain serialize indetifier? 
		if ( strpos($cache, $this->_identifier) === 0 )
		{
			// split and unserialize
			$cache = @unserialize(substr($cache, strlen($this->_identifier)));
		}
		return $cache;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create cache
	 * 
	 * @access pubic
	 * @param string $type
	 * @param string $key
	 * @param mixed $data
	 * @return bool
	 */
	public function create($type, $key, $data, $compareTime = FALSE)
	{
		$prefix = $this->_getType($type);
		if ( ! $prefix )
		{
			throw new LogicException('Invalid cache type:' . $type);
		}
		list($file, $buffer) = $this->_encode($prefix, $key, $data);
		if ( ! really_writable($this->_cacheDir) )
		{
			throw new RuntimeException('Cache directory is not writeable:' . $this->_cacheDir);
		}
		if ( $compareTime && file_exists($file) )
		{
			if ( is_string($compareTime) )
			{
				$compareTime = strtotime($compareTime);
			}
			if ( filemtime($file) >= $compareTime )
			{
				return FALSE;
			}
		}
		$fp = fopen($file, 'wb');
		flock($fp, LOCK_EX);
		fwrite($fp, $buffer);
		flock($fp, LOCK_UN);
		fclose($fp);
		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get type prefix
	 * 
	 * @access protected
	 * @param string $type
	 * @return mixed
	 */
	protected function _getType($type)
	{
		$ret = FALSE;
		switch ( $type )
		{
			case 'db':
				$ret = self::PREFIX_DB;
				break;
			case 'output':
				$ret = self::PREFIX_OUTPUT;
				break;
			case 'data':
				$ret = self::PREFIX_DATA;
				break;
			default:
				break;
		}
		return $ret;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode file and data
	 * 
	 * @access protected
	 * @param string $prefix
	 * @param string $key
	 * @param mixed $data
	 * @return array
	 */
	protected function _encode($prefix, $key, $data = FALSE)
	{
		// encrypt cache file name
		$file   = $this->_cacheDir . $prefix . sha1($key);
		$buffer = '';
		if ( $data )
		{
			// reqsource cannot serialize
			if ( is_resource($data) )
			{
				throw new LogicException('Resource canoot write to Cahche! key is ' . $key);
			}
			// object, array to serialized string
			else if ( is_array($data) || is_object($data) )
			{
				$buffer = $this->_identifier . serialize($data);
			}
			else
			{
				$buffer = $data;
			}
		}
		
		return array($file, $buffer);
	}
}