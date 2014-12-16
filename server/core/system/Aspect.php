<?php if ( ! defined('SZ_EXEC') ) exit('access denied.');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * ------------------------------------------------------------------
 * 
 * Simple aspect wrapping class
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class Aspect
{
	/**
	 * Joinpoint signature constants
	 */
	const JOINPOINT_BEFORE = 0x01;
	const JOINPOINT_AFTER  = 0x10;
	
	/**
	 * Module instance
	 * @var object
	 */
	public $instance;
	
	
	/**
	 * Joinpoint functions
	 * @var array
	 */
	protected static $joinPoints = array(0x01 => array(), 0x10 => array());
	
	
	
	public function __construct($instance)
	{
		$this->instance = $instance;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Overload method call
	 * 
	 * @access public
	 * @param  sring $method
	 * @param  array $args
	 */
	public function __call($method, $args = array())
	{
		// Does calls method exists on instance?
		if ( method_exists($this->instance, $method) )
		{
			// Calls joinpoint before
			foreach ( self::$joinPoints[self::JOINPOINT_BEFORE] as $point )
			{
				call_user_func($point, $this->instance, $args);
			}
			
			$rv = $args['return'] = call_user_func_array(array($this->instance, $method), $args);
			
			// Calls joinpoint after
			foreach ( self::$joinPoints[self::JOINPOINT_AFTER] as $point )
			{
				call_user_func($point, $this->instance, $args);
			}
			
			return $rv;
			
		}
		throw new BadMethodCallException('Called undefined method ' . get_class($this->instance) . '::' . $method . '.');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create object wrapper
	 * 
	 * @access public static
	 * @param  object $instance
	 * @return Aspect
	 */
	public static function make($instance)
	{
		return new self($instance);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add joinpoint function
	 * 
	 * @access public static
	 * @param  function/array $callable
	 * @param  int $point
	 */
	public static function joinPoint($callable, $point = 0x01)
	{
		if ( ! is_callable($callable) )
		{
			throw new LogicException('JoinPoint function is not callable!');
		}
		else if ( $point !== self::JOINPOINT_AFTER
		          && $point !== self::JOINPOINT_BEFORE )
		{
			throw new LogicException('Invalid joinpoint timing passed!');
		}
		
		// Add stack
		self::$joinPoints[$point][] = $callable;
	}
}
