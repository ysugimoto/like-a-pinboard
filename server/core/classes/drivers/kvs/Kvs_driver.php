<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * KVS base driver
 * 
 * @package  Seezoo-Framework
 * @category Driver
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

abstract class SZ_Kvs_driver
{
	/**
	 * abstact methods
	 */
	abstract public function setex($key, $value);
	abstract public function set($key, $value);
	abstract public function get($key);
	abstract public function replace($key, $value);
	abstract public function delete($key);
	abstract public function close();
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Value stringidy
	 * 
	 * @access protected
	 * @param  mixed $data
	 * @return string
	 */
	protected function stringify($data)
	{
		switch ( gettype($data) )
		{
			case 'string':
			case 'integer':
			case 'float':
				return (string)$data;
			
			case 'array':
			case 'object':
				return serialize($data);
				
			default:
				throw new RuntimeException(
					'Cannot convert to string data on KVS driver!'
				);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse string value
	 * 
	 * @access protected
	 * @param  string $data
	 * @return mixed
	 */
	protected function parse($data)
	{
		$unser = @unserialize($data);
		return ( $unser !== FALSE ) ? $unser : $data;
	}
}
