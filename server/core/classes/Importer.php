<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Core/Library/Model/Helper/Tools multi importer class
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Importer implements Growable
{	
	/**
	 * database class name
	 * @var string
	 */
	protected $_databaseClass;
	
	
	/**
	 * Loaded vendors stack
	 * @var array
	 */
	protected $loadedVendors = array();
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Importer ( extended )
	 */
	public static function grow()
	{
		$base = new SZ_Importer();
		return $base->classes('Importer');
	}
	
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a database instance
	 * 
	 * @access public
	 * @param  string $group
	 * @return Database $db
	 */
	public function database($group = '')
	{
		if ( $group === ''
		     && ! ($group = get_config('default_database_connection_handle')) )
		{
			$group = 'default'; 
		}
		
		if ( FALSE === ($db = Seezoo::getDB($group)) )
		{
			$dbClass = $this->loadModule('Database', '', FALSE)->data;
			$db      = new $dbClass($group);
			Seezoo::pushDB($group, $db);
		}
		$this->_attachModule('db', $db);
		
		return $db;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a database forge library
	 * 
	 * @access public
	 * @return Databaseforge instance
	 */
	public function dbforge()
	{
		return $this->loadModule('databaseforge', '', TRUE, array(), 'dbforge');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a KVS instance
	 * 
	 * @access public
	 * @return Kvs $kvs
	 */
	public function kvs($driver = NULL)
	{
		if ( ! $driver )
		{
			$setting = Seezoo::getENV()->getKvsSettings();
			$driver  = $setting['driver'];
		}
		
		if ( FALSE === ($kvs = Seezoo::getKVS($driver)) )
		{
			$kvsClass = $this->loadModule('Kvs', '', FALSE)->data;
			$kvs      = new $kvsClass($driver);
			Seezoo::pushKVS($kvs);
		}
		$this->_attachModule('kvs', $kvs);
		
		return $kvs;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load an activerecord
	 * 
	 * @access public
	 * @return ActiveRecord instance
	 */
	public function activeRecord($arName)
	{
		$module = $this->loadModule($arName, 'activerecords', TRUE);
		return $module->data;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a core class
	 * 
	 * @access public
	 * @param  string $className
	 * @param  bool $instanciate
	 * @return object
	 */
	public function classes($className, $instanciate = TRUE)
	{
		$module = $this->loadModule($className, '', $instanciate);
		return $module->data;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a library
	 * 
	 * @access public
	 * @param  mixed $libname
	 * @param  array  $param
	 * @param  string $alias
	 * @return object
	 */
	public function library($libname, $param = array(), $alias = FALSE, $instantiate = TRUE)
	{
		foreach ( (array)$libname as $lib )
		{
			$module = $this->loadModule($lib, 'libraries', $instantiate, $param, $alias);
			$this->_attachModule(( $alias ) ? $alias : lcfirst($module->name), $module->data);
		}
		return $module->data;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a tool
	 * 
	 * @access public
	 * @param  mixed $tools
	 */
	 /*
	public function tool($tools)
	{
		foreach ( (array)$tools as $tool )
		{
			$name = str_replace('_tool', '', $tool);
			$this->loadModule($name . '_tool', 'tools', FALSE);
		}
	}
	*/
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a Helper
	 * 
	 * @access public
	 * @param  mixed $helpers
	 * @param  string $alias
	 * @return object
	 */
	public function helper($helpers, $alias = FALSE)
	{
		if ( is_array($helpers) )
		{
			$alias = FALSE;
		}
		$H = $this->classes('Helpers');
		$suffixes = Seezoo::getSuffix('helper');
		
		foreach ( (array)$helpers as $helper )
		{
			$name     = str_replace(Seezoo::getSuffix('helper'), '', $helper);
			$alias    = ( $alias ) ? $alias : ucfirst($name);
			$isLoaded = FALSE;
			foreach ( $suffixes as $suffix )
			{
				try
				{
					$module   = $this->loadModule($name . $suffix, 'helpers', TRUE, array(), $alias);
					$isLoaded = TRUE;
					break;
				}
				catch ( Exception $e )
				{
					continue;
				}
			}
			if ( ! $isLoaded )
			{
				throw new UndefinedClassException('Helper ' . $helper . ' is not exists.');
			}
			$H->{strtolower($name)} = $module->data;
		}
		
		return $module->data;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a Model
	 * 
	 * @access public
	 * @param  mixed $models
	 * @param  array  $param
	 * @param  string $alias
	 * @return object
	 */
	public function model($models, $param = array(), $alias = FALSE)
	{
		$this->classes('Kennel', FALSE);
		
		if ( is_array($models) )
		{
			$alias = FALSE;
		}
		$suffixes = Seezoo::getSuffix('model');
		
		foreach ( (array)$models as $model )
		{
			$name     = str_replace($suffixes, '', $model);
			$isLoaded = FALSE;
			foreach ( $suffixes as $suffix )
			{
				try
				{
					$module   = $this->loadModule($name . $suffix, 'models', TRUE, $param);
					$isLoaded = TRUE;
					break;
				}
				catch ( Exception $e )
				{
					continue;
				}
			}
			if ( ! $isLoaded )
			{
				throw new UndefinedClassException('Model ' . $model . ' is not exists.');
			}
			
			$this->_attachModule(( $alias ) ? $alias : $module->name, $module->data);
		}
		return $module->data;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a kennel
	 * 
	 * @access public
	 * @param  mixed  $kennel
	 * @param  array  $param
	 * @param  string $alias
	 * @return object
	 */
	public function kennel($kennel, $param = array(), $alias = FALSE)
	{
		return $this->model($kennel, $param, $alias);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load a vendor library
	 * 
	 * @access public
	 * @param  mixed  $vendors
	 * @param  array  $param
	 * @return object
	 */
	public function vendor($vendors, $param = array())
	{
		$dirBase = 'vendors/';
		$apps    = Seezoo::getApplication();
		
		foreach ( (array)$vendors as $vendor )
		{
			// Does request class in a sub-directory?
			if ( FALSE !== ($point = strrpos($vendor, '/')) )
			{
				$dir    = $dirBase . substr($vendor, 0, ++$point);
				$vendor = substr($vendor, $point);
				$Class  = ucfirst($vendor);
			}
			else
			{
				$Class = ucfirst($vendor);
				$dir   = $dirBase;
			}
			
			$isLoaded = FALSE;
			$filePath = $dir . $Class . '.php';
		
			// Is class already loaded?
			if ( isset($this->loadedVendors[$vendor]) )
			{
				$stacked = $this->loadedVendors[$vendor];
				if ( is_object($stacked) )
				{
					$this->_attachModule($vendor, $stacked);
				}
				continue;
			}
			
			foreach ( $apps as $app )
			{
				if ( file_exists($app->path . $filePath) )
				{
					require_once($app->path . $filePath);
					$isLoaded = TRUE;
					break;
				}
			}
			
			if ( ! $isLoaded )
			{
				throw new Exception($Class . ' is not specified.');
			}
			
			if ( class_exists($Class, FALSE) )
			{
				$module = new $Class($param);
				$this->_attachModule(strtolower($vendor), $module);
			}
			else
			{
				$module = $Class;
			}
			$this->loadedVendors[$vendor] = $module;
		}
		return $module;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load file by string
	 * 
	 * @access public
	 * @param  string $filePath
	 * @return mixed
	 */
	public function file($filePath)
	{
		if ( preg_match('/\Ahttp/', $filePath) )
		{
			$http = $this->library('Http');
			$resp = $http->request('GET', $filePath);
			if ( $resp->status !== 200 )
			{
				return FALSE;
			}
			return $resp->body;
		}
		else if ( ! is_file($filePath) )
		{
			throw new InvalidArgumentException('import file is not found: '
			                                   . get_class($this) . '::file');
		}
		return file_get_contents($filePath);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * attach the module
	 * 
	 * @access protected
	 * @param  string $name
	 * @param  object $module
	 */
	protected function _attachModule($name, $module)
	{
		if ( FALSE === ($SZ = Seezoo::getInstance()) )
		{
			return;
		}
		
		if ( ! isset($SZ->{$name}) )
		{
			$SZ->{$name} = $module;
		}
		if ( isset($SZ->lead) )
		{
			$SZ->lead->attachModule($name, $module);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Load the class
	 * 
	 * @access public static
	 * @param  string $class
	 * @param  string $destDir
	 * @param  bool   $instanciate
	 * @param  array  $params
	 * @throws Exception
	 * @return mixed
	 */
	protected function loadModule(
	                               $class,                      // module name
	                               $destDir     = 'libraries',  // load target directory
	                               $instanciate = TRUE,         // If true, create instance
	                               $params      = NULL,         // pass parameter to class constructor
	                               $alias       = FALSE         // property alias name
	                              )
	{
		if ( FALSE !== ($point = strrpos($class, '/')) )
		{
			$dir   = substr($class, 0, ++$point) . '/';
			$class = ucfirst(substr($class, $point));
		}
		else
		{
			$dir   = '';
			$class = ucfirst($class);
		}
		
		$classSuffix = '';
		if ( $destDir === '' )
		{
			$destDir = 'classes';
			$dir = $destDir . '/' . $dir;
		}
		else if ( $destDir === 'activerecords' )
		{
			$dir = 'activerecords/';
			$classSuffix = 'ActiveRecord';
		}
		else
		{
			$dir = 'classes/' . $destDir . '/' . $dir;
		}
		
		$module = new stdClass;
		$module->name = lcfirst($class);
		
		if ( $singleton = Seezoo::getSingleton($class) )
		{
			$module->data = $singleton;
			return $module;
		}
		
		foreach ( Seezoo::getApplication() as $app )
		{
			if ( file_exists($app->path . $dir . $app->prefix . $class . '.php') )
			{
				require_once($app->path . $dir . $app->prefix . $class . '.php');
				$loadClass = ( class_exists($app->prefix . $class . $classSuffix, FALSE) )
				                ? $app->prefix . $class . $classSuffix
				                : $class . $classSuffix;
				break;
			}
			else if ( file_exists($app->path . $dir . $class . '.php') )
			{
				require_once($app->path . $dir . $class . '.php');
				$loadClass = ( class_exists($app->prefix . $class . $classSuffix, FALSE) )
				                ? $app->prefix . $class . $classSuffix
				                : $class . $classSuffix;
				break;
			}
		}
		
		if ( ! isset($loadClass) )
		{
			$class = Seezoo::removePrefix($class);
			if ( file_exists(SZPATH . 'core/' . $dir . $class. $classSuffix . '.php') )
			{
				require_once(SZPATH . 'core/' . $dir . $class. $classSuffix . '.php');
				$loadClass = SZ_PREFIX_CORE . $class . $classSuffix;
			}
			else
			{
				throw new Exception('Undefined class' . ':' . $class . $classSuffix);
			}
		}
		
		if ( $instanciate === TRUE )
		{
			$instance = new $loadClass($params);
			$module->data = ( $instance instanceof Aspectable )
			                  ? Aspect::make($instance)
			                  : $instance;
			
			// Add stack if singleton class
			if ( $instance instanceof Singleton
			     || Application::config('class_treats_singleton') )
			{
				Seezoo::addSingleton($class . $classSuffix, $instance);
			}
		}
		else
		{
			$module->data = $loadClass;
		}
		
		return $module;
	}
}
