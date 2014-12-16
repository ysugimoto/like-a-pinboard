<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * AutoLoader class
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class Autoloader
{
	/**
	 * PSR Load constant
	 */
	const LOAD_PSR = 'PSR';
	
	/**
	 * Load destination directories
	 * @var array
	 */
	private static $loadDir    = array(0x00 => array());
	
	private static $aliasClass = array(
	                                    'ActiveRecord' => SZ_PREFIX_CORE,
	                                    'DB'           => SZ_PREFIX_CORE
	                                  );
	
	
	public static $loadTargets = array(
	                                    'classes'           => 'classes',
	                                    'classes/helpers'   => 'helper',
	                                    'classes/libraries' => 'library',
	                                    'classes/models'    => 'model',
	                                    'vendors'           => self::LOAD_PSR
	                                  );
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Autolorder init
	 * 
	 * @access public static
	 */
	public static function init()
	{
		spl_autoload_register(array('Autoloader', 'loadSystem'));
		spl_autoload_register(array('Autoloader', 'load'));
		
		foreach ( self::$loadTargets as $dir => $loadType )
		{
			self::register(SZPATH . 'core/' . $dir, $loadType, SZ_PREFIX_CORE);
		}
		
		spl_autoload_register(array('Event', 'loadEventDispatcher'));
	}
		

	// ---------------------------------------------------------------
	
	
	/**
	 * Register load destination directory
	 * 
	 * @param public static
	 * @param string $path
	 * @param string $loadType
	 * @param string $prefix
	 */
	public static function register($path, $loadType = '', $prefix = 0x00)
	{
		$path = trail_slash($path);
		if ( ! isset(self::$loadDir[$prefix]) )
		{
			self::$loadDir[$prefix] = array();
		}
		if ( isset(self::$loadDir[$prefix][$path]) )
		{
			return;
		}
		self::$loadDir[$prefix][$path] = $loadType;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Unregister load destination directory
	 * 
	 * @access public static
	 * @param  string $path
	 */
	public static function unregister($path)
	{
		$path = trail_slash($path);
		foreach ( self::$loadDir as $key => $dirs )
		{
			if ( array_key_exists($path, $dir) )
			{
				unset($dir[$path]);
				self::$loadDir[$key] = $dir;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load Class from registerd paths
	 * 
	 * @access public static ( handler )
	 * @param string $className
	 */
	public static function load($className)
	{
		if ( Seezoo::hasPrefix($className) )
		{
			list($prefix, $class) = Seezoo::removePrefix($className, TRUE);
		}
		else
		{
			$prefix = ( isset(self::$aliasClass[$className]) )
			            ? self::$aliasClass[$className]
			            : '';
			$class  = $className;
		}
		
		$dirs = ( isset(self::$loadDir[$prefix]) )
		          ? self::$loadDir[$prefix]
		          : self::$loadDir[0x00];
		
		foreach ( $dirs as $path => $type )
		{
			switch ( $type )
			{
				// PSR load type
				case self::LOAD_PSR:
					$className = str_replace(array('\\', '_'), '/', $class);
					if ( file_exists($path . $className . '.php') )
					{
						require_once($path . $className . '.php');
					}
					break;
				
				// Etc, Prefix-Suffixed load type
				default:
					if ( file_exists($path . $prefix . $class . '.php') )
					{
						require_once($path . $class . '.php');
					}
					else if ( file_exists($path . $class . '.php') )
					{
						require_once($path . $class . '.php');
					}
					break;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Autoload core load handler
	 *
	 * @access public static
	 * @param  string
	 */
	public static function loadSystem($className)
	{
		if ( file_exists(SZPATH . 'core/system/' . $className . '.php') )
		{
			require_once(SZPATH . 'core/system/' . $className . '.php');
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Autoload register handler for testcase
	 *
	 * @access public static
	 * @param string
	 */
	public static function loadTestModule($name)
	{
		$module = preg_replace('/^SZ_/', '', $name);
		if ( file_exists(SZPATH . 'core/test/' . $module . '.php') )
		{
			require_once(SZPATH . 'core/test/' . $module . '.php');
		}
	}
}
