<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * KVS(Key-Value Store) library
 * 
 * @package  Seezoo-Framework
 * @category Core
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Kvs extends SZ_Driver
{
	
	/**
	 * Constructor
	 */
	public function __construct($driver = NULL)
	{
		parent::__construct();
		
		$env     = Seezoo::getENV();
		$setting = $env->getKvsSettings();
		
		// Use default driver if argument not exists
		if ( ! $driver )
		{
			$driver = $setting['driver'];
		}
		$info         = $setting[$driver];		
		$this->driver = $this->loadDriver(ucfirst($driver) . '_kvs');
		
		if ( ! $this->driver->connect($info['host'], $info['port']) )
		{
			throw new RuntimeException($driver . ' service connection failed!');
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set value if key not exists
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 */
	public function setex($key, $value)
	{
		$this->driver->setex($key, $value);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 */
	public function set($key, $value = '')
	{
		if ( is_array($key) )
		{
			foreach ( $key as $k => $v )
			{
				$this->driver->set($k, $v);
			}
		}
		else
		{
			$this->driver->set($key, $value);
		}
		
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get value
	 * 
	 * @access public
	 * @param  mixed ( string ar array )
	 * @return mixed
	 */
	public function get($key)
	{
		if ( is_array($key) )
		{
			$ret = array();
			foreach ( $key as $k )
			{
				$ret[] = $this->driver->get($k);
			}
			return $ret;
		}
		
		return $this->driver->get($key);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Replace value
	 * 
	 * @access public
	 * @param  string $key
	 * @param  mixed $value
	 */
	public function replace($key, $value)
	{
		$this->driver->replace($key, $value);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Delete key
	 * 
	 * @access public
	 * @param  string $key
	 */
	public function delete($key)
	{
		foreach ( (array)$key as $k )
		{
			$this->driver->delete($k);
		}
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Destructor
	 */
	public function __destruct()
	{
		$this->driver->close();
	}
}
