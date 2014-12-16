<?php  if ( ! defined('SZ_EXEC')) exit('access denied');


/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * Breeder's lead
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Lead
{
	/**
	 * Controller call info
	 * @var string
	 */
	protected $_callInfo;
	
	
	/**
	 * Database instance (if loaded)
	 * @var object
	 */
	protected $db;
	
	
	/**
	 * Loaded modules stack
	 * @var array
	 */
	protected $_modules    = array();
	
	
	/**
	 * View assign data
	 * @var array
	 */
	protected $_assignData = array();
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set call method on current request
	 * 
	 * @access public
	 * @param  string
	 */
	public function setExecuteMethod($method)
	{
		$this->_callInfo = $method;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Call current request of Controller
	 * 
	 * @access public
	 * @param  array $params
	 * @param  bool  $autoAssign
	 * @return mixed
	 */
	public function call($params = array(), $autoAssign = TRUE)
	{
		if ( method_exists($this, $this->_callInfo) )
		{
			Injector::injectByAnnotation($this, $this->_callInfo);
			$data = call_user_func_array(array($this, $this->_callInfo), $params);
			if ( $autoAssign === TRUE )
			{
				$this->_assignData = $data;
			}
			return $data;
		}
		return NULL;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Controler prepare method
	 * 
	 * @access public
	 */
	public function prepare()
	{
		// Please implement inheritance in the class of destination.
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Controler after method
	 * 
	 * @access public
	 */
	public function teardown()
	{
		// Please implement inheritance in the class of destination.
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Magic method of get loaded modules(library, model)
	 */
	public function __get($name)
	{
		$name = lcfirst($name);
		if ( isset($this->_modules[$name]) )
		{
			return $this->_modules[$name];
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Attach processing loaded module
	 * 
	 * @access public
	 * @param  string $name
	 * @param  object $module
	 */
	public function attachModule($name, $module)
	{
		$this->_modules[lcfirst($name)] = $module;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get auto assign data
	 * 
	 * @access public
	 * @return array
	 */
	public function getAssignData()
	{
		if ( is_object($this->_assignData) )
		{
			return get_object_vars($this->_assignData);
		}
		else if ( is_array($this->_assignData) )
		{
			return $this->_assignData;
		}
		
		return array();
	}
}
