<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * KVS Redis driver
 * 
 * @package  Seezoo-Framework
 * @category Driver
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Redis_kvs extends SZ_Kvs_driver
{
	/**
	 * Redis instance
	 * @var Redis
	 */
	protected $redis;
	
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->redis = new Redis;
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
		return $this->redis->connect($host, $port);
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
		return (bool)$this->redis->setnx($key, $value);
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
		return 'OK' === $this->redis->set($key, $this->stringify($value));
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
		$val = $this->redis->get($key);
		return ( ! is_null($val) ) ? $this->parse($val) : $val;
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
		return 'OK' === $this->redis->set($key, $this->stringify($value));
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
		return (bool)$this->redis->del($key);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Close connection
	 * 
	 * @access public
	 */
	public function close()
	{
		$this->redis->quit();
	}
}
