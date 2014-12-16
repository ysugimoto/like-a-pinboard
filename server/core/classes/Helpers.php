<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Helper management class (at View)
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Helpers implements Growable, Singleton
{
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Helpers');
	}
	
	
	public function compile()
	{
		static $compiled = array();
		
		foreach ( $this->_helpers as $name => $helper )
		{
			if ( ! isset($compiled[$name]) )
			{
				foreach ( get_class_methods($helper) as $method )
				{
					$fn = strtolower($name . '_' . $method);
					$function = "if ( ! function_exists('$fn')) { "
					            ."  function $fn() {"
					            ."    return call_user_func_array("
					            ."             array(get_helper()->$name, '$method'),"
					            ."             func_get_args()"
					            ."           );"
					            ."  }"
					            ."}";
					//echo $function;
					eval($function);
				}
				$compiled[$name] = 1;
			}
		}
	}
	
	
	
	/**
	 * Stack loaded helers
	 * @var array
	 */
	protected $_helpers = array();
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Overload method
	 */
	public function __get($name)
	{
		$name = ucfirst($name);
		if ( isset($this->_helpers[$name]) )
		{
			return $this->_helpers[$name];
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Overload method
	 */
	public function __set($name, $helperObject)
	{
		$this->{$name} = $helperObject;
		$this->_helpers[$name] = $helperObject;
	}
}
