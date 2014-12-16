<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Access Router
 * 
 * @package  Seezoo-Framework
 * @category classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Router implements Growable
{
	protected $_level;
	
	protected $_mode;
	
	/**
	 * Environment
	 * @var Environment
	 */
	protected $env;
	
	
	/**
	 * Request method
	 * @var string
	 */
	protected $requestMethod;
	
	
	/**
	 * Requested pathinfo
	 * @var string
	 */
	protected $_pathinfo = '';
	
	
	/**
	 * Default module
	 * @var string
	 */
	protected $defaultModule;
	
	
	/**
	 * Inlucde module filename
	 * @var string
	 */
	protected $moduleFileName;
	
	
	/**
	 * Really executed method
	 * @var string
	 */
	protected $_execMethod = '';
	
	
	/**
	 * Routed informations
	 * @var string /array
	 */
	protected $_package    = '';
	protected $_directory  = '';
	protected $_class      = '';
	protected $_method     = '';
	protected $_arguments  = array();
	protected $_loadedFile = '';
	
	
	public function __construct()
	{
		$this->env = Seezoo::getENV();
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Router ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Router');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set boot pathinfo
	 * 
	 * @access public
	 * @param  string $pathinfo
	 */
	public function setPathInfo($pathinfo)
	{
		$this->_pathinfo = $pathinfo;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set current process mode
	 * 
	 * @access public
	 * @param  string $mode
	 */
	public function setMode($mode)
	{
		$this->_mode = $mode;
		switch ( $mode )
		{
			case SZ_MODE_CLI:
				$this->moduleFileName = 'cli';
				break;
				
			case SZ_MODE_ACTION:
				$this->moduleFileName = 'action';
				break;
				
			default:
				$this->moduleFileName = ( is_ajax_request() ) ? 'ajax' : 'controller';
				break;
		}
	}
	
	
	public function boot($args)
	{
		if ( $this->routing() === FALSE )
		{
			return FALSE;
		}
		
		$this->_loadedFile = $this->_package . '/' . $this->moduleFileName . '.php';
		
		if ( $args !== FALSE )
		{
			$this->_arguments = array_merge($this->_arguments, $args);
		}
		
		if ( $this->_mode === SZ_MODE_ACTION )
		{
			// Action mode process single filed output.
			// method contains on arguments
			array_unshift($this->_arguments, $this->_method);
			$args = $this->_arguments;
			$SZ   = new Seezoo::$Classes['Breeder']();
			
			// Include process file
			$SZ->view->bufferStart();
			$rv = require($this->_loadedFile);
			$SZ->view->getBufferEnd();
			
			return array($SZ, $rv);
		}
		
		$class  = $this->_class . ucfirst($this->moduleFileName);
		$return = require_once($this->_loadedFile);
		
		Event::fire('module_loaded');
		
		if ( ! class_exists($class, FALSE) )
		{
			throw new UndefinedClassException($class . ' is not defined.');
		}
		
		$Controller = new $class();
		$Controller->view->bufferStart();
		$Controller->lead->prepare();
		$Controller->view->set(strtolower($this->_directory . '/' . $this->_method));
		
		Event::fire('module_prepared');
		
		// Does mapping method exists?
		if ( method_exists($Controller, '_mapping') )
		{
			// execute mapping method
			$rv = $Controller->_mapping($this->_method);
		}
		else if ( '' === ($this->_execMethod = $this->_findMethod($Controller)) )
		{
			return FALSE;
		}
		else
		{
			$Controller->lead->setExecuteMethod($this->_execMethod);
			$rv = call_user_func_array(array($Controller, $this->_execMethod), $this->_arguments);;
		}
		
		$Controller->lead->teardown();
		$Controller->view->getBufferEnd();
		
		return array($Controller, $rv);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Find executable method
	 * 
	 * @access protected
	 * @param  SZ_Breeder $Controller
	 * @return string
	 */
	protected function _findMethod($Controller)
	{
		// request method suffix
		$methodSuffix = ( $this->requestMethod === 'POST' ) ? '_post' : '';
		$execMethod   = '';
		
		// First, call prefix-method-suffix ( ex.act_index_post ) if exists
		if ( method_exists($Controller, SZ_EXEC_METHOD_PREFIX . $this->_method . $methodSuffix) )
		{
			$execMethod = SZ_EXEC_METHOD_PREFIX . $this->_method . $methodSuffix;
		}
		// Second, call method-suffix ( *_post method ) if exists
		else if ( method_exists($Controller, $this->_method . $methodSuffix) )
		{
			$execMethod = $this->_method . $methodSuffix;
		}
		// Third, call prefix-method if exists
		else if ( method_exists($Controller, SZ_EXEC_METHOD_PREFIX . $this->_method) )
		{
			$execMethod = $this->methodPrefix . $this->_method;
		}
		// Fourth, call method simply if exists
		else if ( method_exists($Controller, $this->_method) )
		{
			$execMethod = $this->_method;
		}
		
		return $execMethod;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set process level
	 * 
	 * @access public
	 * @param  int $level
	 */
	public function setLevel($level)
	{
		$this->_level = $level;
	}
	
	
	// ---------------------------------------------------------------o
	
	
	/**
	 * Boot Lead layer class
	 *
	 * @access public
	 * @return object SZ_Lead
	 */
	public function bootLead()
	{
		if ( ! file_exists($this->_package . '/lead.php') )
		{
			return Seezoo::$Importer->classes('Lead');
		}
		
		require_once($this->_package . '/lead.php');
		$Class = $this->_class . 'Lead';
		if ( ! class_exists($Class, FALSE) )
		{
			throw new UndefinedClassException($Class . ' class is not declared on ' . $this->_package . '/lead.php.');
		}
		
		$lead = new $Class();
		Injector::injectDIContainer($lead, $this->_package);
		Injector::injectByReflection($lead);
		
		return $lead;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Routing execute
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function routing()
	{
		$this->requestMethod = Seezoo::getRequest()->getRequestMethod();
		$this->defaultModule = get_config('default_module');
		
		// If URI segments is empty array ( default page ),
		// set default module and default method array.
		$_segments = ( empty($this->_pathinfo) )
		              ? array($this->defaultModule, 'index')
		              : explode('/', $this->_pathinfo);
		// Mark routing succeed
		$isRouted = FALSE;
		
		foreach ( Seezoo::getApplication() as $app )
		{
			$segments = $_segments;
			array_unshift($segments, $app->path . 'modules');
			$detected = $this->_detectModule($segments);
			
			if ( is_array($detected) )
			{
				list($package, $this->_method, $this->_arguments) = $detected;
				$this->_package   = implode('/', $package);
				array_shift($package);
				$this->_directory = implode('/', $package);
				foreach ( $package as $pkg )
				{
					$this->_class .= ucfirst($pkg);
				}
				$isRouted = TRUE;
				Autoloader::register($this->_package);
				break;
			}
		}
		
		if ( $isRouted === TRUE )
		{
			// Routing succeed!
			Event::fire('routed', $this);
		}
		return $isRouted;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get routing info
	 * 
	 * @access public
	 * @param string $prop
	 * @return string
	 */
	public function getInfo($prop)
	{
		return ( isset($this->{'_' . $prop}) ) ? $this->{'_' . $prop} : '';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Module detection
	 * 
	 * @access protected
	 * @param  array $segments
	 * @param  string $baseDir
	 * @return mixed
	 */
	protected function _detectModule($segments, $arguments = array())
	{
		$searchDir = implode('/', $segments) . '/';
		if ( file_exists($searchDir . $this->moduleFileName . '.php') )
		{
			$arguments = array_reverse($arguments);
			$method    = array_shift($arguments);
			return array(
			              $segments,
			              ( $method === NULL || $method === '' ) ? 'index' : $method,
			              $arguments
			            );
		}
		
		if ( ! is_dir($searchDir) )
		{
			$arguments[] = array_pop($segments);
			if ( count($segments) === 1 )
			{
				return FALSE;
			}
			return $this->_detectModule($segments, $arguments);
		}
		else
		{
			return FALSE;
		}
	}
}
