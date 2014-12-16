<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Array-Object helper
 * 
 * @package  Seezoo-Framework
 * @category Helpers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_AoHelper implements Growable
{
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->helper('Ao');
	}
	
	
	/**
	 * Data to Array
	 * 
	 * @access public
	 * @param mixed $dat
	 * @return array
	 */
	public function toArray($dat)
	{
		if ( is_array($dat) )
		{
			return $dat;
		}
		else if ( is_object($dat) )
		{
			return get_object_vars($dat);
		}
		
		if ( is_string($dat) && class_exists($dat) )
		{
			return get_class_vars($dat);
		}
		return array($dat);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Data to Object(stdClass)
	 * 
	 * @access public
	 * @param  mixed $dat
	 * @return object
	 */
	public function toObject($dat)
	{
		if ( is_object($dat) )
		{
			return $dat;
		}
		
		$obj = new stdClass;
		foreach ( $this->toArray($dat) as $key => $val )
		{
			$obj->{$key} = $val;
		}
		
		return $obj;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create Array/Object shallow copy
	 * 
	 * @access public
	 * @param  mixed $dat
	 * @return mixed
	 */
	public function duplicate($dat)
	{
		if ( is_array($dat) )
		{
			$ret = array();
			foreach ( $dat as $key => $val )
			{
				$ret[$key] = $val;
			}
			return $ret;
		}
		else if ( is_object($dat) )
		{
			$ret = new stdClass;
			foreach ( $dat as $key => $val )
			{
				$ret->{$key} = $val;
			}
			return $ret;
		}
		return $dat;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get keys array from array/hash
	 * 
	 * @access public
	 * @param  mixed $dat
	 * @return array
	 */
	public function keys($dat)
	{
		if ( is_object($dat) )
		{
			$dat = get_object_vars($dat);
		}
		
		$args = func_get_args();
		
		if ( count($args) > 2 )
		{
			return array_keys($dat, $args[1], ( isset($args[2]) ) ? $args[2] : FALSE);
		}
		else
		{
			return array_keys($dat);
		}
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Array/object mixin
	 * 
	 * @access public
	 * @param  mixed $base
	 * @param  mixed $over
	 * @return mixed
	 */
	public function mixin($base, $over)
	{
		if ( is_array($base) )
		{
			$base = array_merge($base, $this->toArray($over));
		}
		else if ( is_object($base) )
		{
			foreach ( $this->toArray($over) as $key => $val )
			{
				$base->{$key} = $val;
			}
		}
		return $base;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check is number-ordered array
	 * 
	 * @access public
	 * @param  array $dat
	 * @return bool
	 */
	public function isNumberArray($dat)
	{
		if ( ! is_array($dat) )
		{
			return FALSE;
		}
		$i = 0;
		foreach ( $dat as $key => $val )
		{
			if ( $key !== $i++ )
			{
				return FALSE;
			}
		}
		return TRUE;
	}
}