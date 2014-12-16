<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * System debugging utility
 * 
 * @package  Seezoo-Framework
 * @category classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Debugger implements Growable
{
	/**
	 * Environment class instance
	 * @var Environment
	 */
	protected $env;
	
	
	/**
	 * Request class instance
	 * @var Request
	 */
	protected $req;
	
	
	/**
	 * Benchmark class instance
	 * @var Benchmark
	 */
	protected $bm;
	
	
	/**
	 * debug stored stacks
	 * @var array
	 */
	protected $_storedVars = array();
	
	
	public function __construct()
	{
		$this->env = Seezoo::getENV();
		$this->req = Seezoo::getRequest();
		$this->bm  = Seezoo::$Importer->classes('Benchmark');
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Debugger');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * store the debug value
	 * 
	 * @access public
	 * @param  mixed $var1[, $var2...]
	 */
	public function store($var)
	{
		$this->_storedVars[] = $var;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * debugger execute
	 * 
	 * @access public
	 * @return string $buffer
	 */
	public function execute($memory_usage = 0)
	{
		$id    = 'sz_system_debugger_' . time();
		$marks = $this->bm->getAllMarks();
		
		ob_start();
		include(SZPATH . 'core/system/debugger_template.php');
		$buffer = ob_get_contents();
		ob_end_clean();
		return $buffer;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * dump the variable
	 * 
	 * @access protected
	 * @param  mixed $buf
	 */
	protected function _dump_var($var)
	{
		ob_start();
		var_dump($var);
		$buf = ob_get_contents();
		ob_end_clean();
		return $buf;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * format library names
	 * 
	 * @access protected
	 * @param  mixed $lib
	 * @return string $class
	 */
	protected function _formatLibName($lib)
	{
		$class     = ( is_string($lib[0]) ) ? $lib[0] : get_class($lib[0]);
		$subPrefix = get_config('subclass_prefix');
		
		if ( strpos($class, $subPrefix) !== FALSE )
		{
			return $class . ' ( extended )';
		}
		return $class;
	}
}