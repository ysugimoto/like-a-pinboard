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
 * Application info
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */


class Application
{
	/**
	 * Application absolute path
	 * @var string
	 */
	public $path;
	
	/**
	 * Application name
	 * @var string
	 */
	public $name;
	
	/**
	 * Application prefix namepsace
	 * @var string
	 */
	public $prefix;
	
	
	public $mode;
	public $level;
	public $pathinfo;
	public $router;
	public $env;
	public $request;
	public $config = array();
	
	
	/**
	 * Using applications stack
	 * @var array
	 */
	protected static $apps = array();
	
	
	/**
	 * Application current instance
	 * @var array
	 */
	private static $instances = array();
	
	private static $encodings = array(
	                              'internal' => 'UTF-8',
	                              'post'     => 'UTF-8',
	                              'get'      => 'UTF-8',
	                              'cookie'   => 'UTF-8',
	                              'input'    => 'UTF-8'
	                            );
	
	
	public static function setEncoding($type, $encoding = 'UTF-8')
	{
		self::$encodings[$type] = $encoding;
	}
	
	public static function getEncoding($type)
	{
		return self::$encodings[$type];
	}
	
	public static function getName()
	{
		return self::get()->name;
	}
	
	public static function getPath()
	{
		return self::get()->path;
	}
	
	public static function isForked()
	{
		return count(self::$instances) > 1;
	}
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Fork the subprocess with same environment
	 * 
	 * @access public static
	 * @param  string $mode
	 * @param  string $overridePathInfo
	 * @param  array  $extraArgs
	 * @return mixed
	 */
	public static function fork($mode = FALSE, $overridePathInfo = '', $extraArgs = FALSE)
	{
		if ( count(self::$instances) === 0 )
		{
			throw new RuntimeException('Application has not main process!');
		}
		$instance = self::get();
		$fork = clone $instance;
		self::$instances[] = $fork;
		return $fork->boot($mode, $overridePathInfo, $extraArgs);
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get stackes applications
	 * 
	 * @access public
	 * @return array
	 */
	public function getApps()
	{
		return self::$apps;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get currnt applications
	 * 
	 * @access public static
	 * @return array
	 */
	public static function get()
	{
		return end(self::$instances);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Application constructor
	 * 
	 * @access private
	 * @param  string $appName
	 * @param  string $prefix
	 */
	private function __construct($appName, $prefix)
	{
		if ( ! is_dir(APPPATH . $appName) )
		{
			throw new RuntimeException('Application "' . $appName . '" is undefined.');
		}
		$this->name   = $appName;
		$this->path   = trail_slash(APPPATH . $appName);
		$this->prefix = ( $prefix === '' )
		                     ? trim(ucfirst($appName), '_') . '_'
		                     : trim($prefix, '_') . '_';
		
		foreach ( Autoloader::$loadTargets as $path => $loadType )
		{
			if ( is_dir($this->path . $path) )
			{
				Autoloader::register($this->path . $path, $loadType, $this->prefix);
			}
		}
		
		// Config setup  ---------------------------------------------
		if ( FALSE === ($config = graceful_require($this->path . 'config/config.php', 'config')) )
		{
			$config = array();
		}
		$this->config = $config;
		
		
		// Event startup ---------------------------------------------
		
		if ( file_exists($this->path . 'config/event.php') )
		{
			Event::addListenerFromFile($this->path . 'config/event.php');
		}
		
		Seezoo::addPrefix($this->prefix);
		$this->bootStrap();
		self::$apps[] = $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create application
	 * 
	 * @access public static
	 * @param  string $appName
	 * @param  string $prefix
	 * @return Application $app
	 */
	public static function init(
	                            $appName    = SZ_BASE_APPLICATION_NAME,
	                            $prefix     = SZ_PREFIX_BASE,
	                            $autoExtend = TRUE)
	{
		if ( count(self::$instances) > 0 )
		{
			throw new RuntimeException('Application has already created!');
		}
		// initialize applications
		self::$apps = array();
		$instance   = new Application($appName, $prefix);
		
		// Are you use default application?
		if ( $autoExtend && $appName !== SZ_BASE_APPLICATION_NAME )
		{
			$instance->extend(SZ_BASE_APPLICATION_NAME . ':' . SZ_PREFIX_BASE);
		}
		
		Event::fire('application_init');
		
		return self::$instances[] = $instance;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Extend application
	 * 
	 * @access public
	 * @param  mixed $apps
	 * @return Application $this
	 */
	public function extend($apps = '')
	{
		// If the last be temporarily saved based applications
		if ( end(self::$apps)->name === SZ_BASE_APPLICATION_NAME )
		{
			$baseApp = array_pop(self::$apps);
		}
		
		foreach ( (array)$apps as $app )
		{
			list($appName, $prefix) = ( strpos($app, ':') !== FALSE )
			                            ? explode(':', $app)
			                            : array($app, '');
			
			if ( ! $this->_exists($appName) )
			{
				$instance     = new Application($appName, $prefix);
				$this->config = array_merge($instance->config, $this->config);
			}
		}
		
		// Restore what was saved base application if exists
		if ( isset($baseApp) )
		{
			self::$apps[] = $baseApp;
			$this->config = array_merge($baseApp->config, $this->config);
		}
		
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Extend all applications
	 * 
	 * @access public
	 * @return Application $this
	 */
	public function extendAll()
	{
		$items        = array();
		$applications = new DirectoryIterator(APPPATH);
		
		foreach ( $applications as $application )
		{
			$name = $application->getBasename();
			
			if ( ! $application->isDir()
			     || $application->isDot()
			     || $this->_exists($name) )
			{
				continue;
			}
			
			$items[] = $name;
		}
		
		return $this->extend($items);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check Application already exists
	 * 
	 * @access private
	 * @param  string $appName
	 * @return bool
	 */
	private function _exists($appName)
	{
		foreach ( self::$apps as $app )
		{
			if ( $app->name === $appName )
			{
				return TRUE;
			}
		}
		
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get application conguration
	 * 
	 * @access public static
	 * @param  string $key
	 * @return mixed
	 */
	public static function config($key)
	{
		$instance = self::get();
		
		return ( isset($instance->config[$key]) )
		         ? $instance->config[$key]
		         : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Run the application
	 * 
	 * @access public static
	 * @param  string $mode
	 * @param  string $overridePathInfo
	 */
	public function boot($mode = FALSE, $overridePathInfo = '', $extraArgs = FALSE)
	{
		$mode = ( $mode ) ? $mode : $this->config['default_process'];
		Seezoo::prepare($this, $mode, $overridePathInfo);
		
		// Benchmark start
		$Mark = Seezoo::$Importer->classes('Benchmark');
		
		// create process instance
		$Mark->start('baseProcess:'. $this->level);
		
		// Process started event fire
		Event::fire('process_start', $this);
		
		$exec = $this->router->boot($extraArgs);
		if ( ! is_array($exec) )
		{
			return show_404();
		}
		
		$Mark->end('process:' . $this->level . ':module:Executed', 'baseProcess:' . $this->level);
		
		Event::fire('module_execute');
		
		// extract instance/returnvalue
		list($SZ, $returnValue) = $exec;
		
		// process executed. release process instance.
		$Mark->end('process:' . $this->level . ':end', 'baseProcess:'. $this->level);
		Event::fire('process_end');
		
		if ( Seezoo::$outputBufferMode === FALSE )
		{
			Seezoo::releaseInstance($SZ);
			Seezoo::$outputBufferMode = TRUE;
			return $returnValue;
		}
		
		if ( $returnValue instanceof SZ_View )
		{
			$returnValue->finalRender();
		}
		else if ( $returnValue instanceof SZ_Response )
		{
			$returnValue->send(TRUE);
		}
		else
		{
			// Swtich signal
			switch ( $returnValue )
			{
				// Did Controller return failure signal?
				case Signal::failed:
					throw new SeezooException('Controller returns error signal!');
				
				// Did Controler return redirect signal or response instance?
				case Signal::redirect:
					Seezoo::$Response->send(TRUE);
				
				// Did Controler return finished signal?
				// It means final output is already processed.
				case Signal::finished:
					break;
				
				// Other signal ( variables )
				default:
					switch ( gettype($returnValue) )
					{
						// If string returns, replace output buffer
						case 'string':
							$SZ->view->replaceBuffer($returnValue);
							break;
						
						// If object returns, render final view with array converted.
						case 'object':
							$SZ->view->finalRender(get_object_vars($returnValue));
							break;
						
						// Simple render final view
						case 'array':
							$SZ->view->finalRender($returnValue);
							break;
						
						// Render with no parameter
						default:
							$SZ->view->finalRender();
							break;
					}
			}
		}
		
		// Is this process in a sub process?
		if ( $this->level > 1 )
		{
			// Does output hook method exists?
			if ( method_exists($SZ, '_output') )
			{
				$SZ->view->replaceBuffer($SZ->_output($SZ->view->getDisplayBuffer()));
			}
			Seezoo::releaseInstance($SZ);
			array_pop(self::$instances);
			$this->level--;
			return $SZ->view->getDisplayBuffer();
		}
		else
		{
			$Mark->end('final', 'baseProcess:'. $this->level);
			Event::fire('session_update');
			
			// Does output hook method exists?
			if ( method_exists($SZ, '_output') )
			{
				$SZ->view->replaceBuffer($SZ->_output($SZ->view->getDisplayBuffer()));
			}
			Seezoo::releaseInstance($SZ);
			array_pop(self::$instances);
			$this->level--;
			
			Seezoo::$Response->setBody($SZ->view->getDisplayBuffer())
			                 ->send();
		}
		// -- complete!
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Execute bootstrap if application has bootstrap file
	 * 
	 * @access protected
	 * @return void
	 */
	protected function bootStrap()
	{
		if ( file_exists($this->path . 'bootstrap.php') )
		{
			require($this->path . 'bootstrap.php');
		}
	}
}
