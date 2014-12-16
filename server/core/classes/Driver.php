<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Selectable backend as driver ( base class )
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Driver
{
	protected $driver;
	protected $driverPath;
	protected $driverBase;
	protected $driverSuffix;
	
	public function __construct()
	{
		$className = Seezoo::removePrefix(get_class($this));
		$class     = strtolower($className);
		$this->driverPath = 'classes/drivers/' . $class . '/';
		
		// Pre-include abstract base class
		if ( file_exists(SZPATH . 'core/' . $this->driverPath . $className . '_driver.php') )
		{
			require_once(SZPATH . 'core/' . $this->driverPath . $className . '_driver.php');
			//$this->driverBase = SZ_PREFIX_CORE . $className . '_driver';
		}
	}
	
	/**
	 * load a driver
	 * 
	 * @access protected
	 * @param  string $driverClass
	 * @param  bool   $instanticate
	 * @throws Exception
	 */
	protected function loadDriver($driverClass = '', $instantiate = TRUE)
	{
		if ( empty($driverClass) )
		{
			if ( $this->driverBase )
			{
				return new $this->driverBase;
			}
			return;
		}
		
		// Mark the load class
		$Class = '';
		
		if ( file_exists(SZPATH . 'core/' . $this->driverPath . $driverClass . '.php') )
		{
			require_once(SZPATH . 'core/' . $this->driverPath . $driverClass . '.php');
			$Class = SZ_PREFIX_CORE . $driverClass;
		}
		
		foreach ( Seezoo::getApplication() as $app )
		{
			if ( file_exists($app->path . $this->driverPath . $app->prefix . $driverClass . '.php') )
			{
				require_once($app->path . $this->driverPath . $app->prefix . $driverClass . '.php');
				$Class = ( class_exists($app->prefix . $driverClass, FALSE) )
				           ? $app->prefix . $driverClass
				           : $driverClass;
				break;
			}
			if ( file_exists($app->path . $this->driverPath . $driverClass . '.php') )
			{
				require_once($app->path . $this->driverPath . $driverClass . '.php');
				$Class = ( class_exists($app->prefix . $driverClass, FALSE) )
				           ? $app->prefix . $driverClass
				           : $driverClass;
				break;
			}
		}
		
		if ( $Class === '' || ! class_exists($Class, FALSE) )
		{
			throw new Exception('DriverClass:' . $Class . ' is not declared!');
		}
		
		return ( $instantiate === TRUE ) ? new $Class() : $Class;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Overload method
	 * calls like subclass member function
	 */
	public function __call($name, $arguments)
	{
		if ( method_exists($this, $name) )
		{
			return call_user_func_array(array($this, $name), $arguments);
		}
		else if ( is_object($this->driver)
		          && is_callable(array($this->driver, $name)) )
		{
			return call_user_func_array(array($this->driver, $name), $arguments);
		}
		throw new BadMethodCallException('Undefined method called ' . get_class($this) . '::' . $name . '.');
	}
}