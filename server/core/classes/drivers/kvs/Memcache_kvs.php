<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * KVS Memcache driver
 * 
 * @package  Seezoo-Framework
 * @category Driver
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Memcache_kvs extends SZ_Kvs_driver
{
	/**
	 * Memcache instance
	 * @var Memcache
	 */
	protected $memcache;
	
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->memcache = new Memcache;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Connection start
	 * 
	 * @access public
	 * @param  string $host
	 * @param  int $port
	 * @return bool
	 */
	public function connect($host, $port)
	{
		return $this->memcache->connect($host, $port);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set value if key is not exists
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 */
	public function setex($key, $value)
	{
		return $this->memcache->add($key, $value);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 */
	public function set($key, $value)
	{
		return $this->memcache->set($key, $this->stringify($value));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get value
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key)
	{
		$val = $this->memcache->get($key);
		return ( $val !== FALSE )
		         ? $this->parse($val)
		         : $val;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Replacce value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 */
	public function replace($key, $value)
	{
		return $this->memcache->replace($key, $value);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Delete value
	 * 
	 * @access public
	 * @param  string $key
	 */
	public function delete($key)
	{
		return $this->memcache->delete($key);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Close connection
	 * 
	 * @access public
	 */
	public function close()
	{
		$this->memcache->close();
	}
}
