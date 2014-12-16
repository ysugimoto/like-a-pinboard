<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * View rendering with PHPTAL
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Twig_view extends SZ_View_driver
{
	/**
	 * PHPTAL library path
	 * @var string
	 */
	protected $_libpath;
	protected $options = array();
	
	
	public function __construct()
	{
		parent::__construct();
		
		$this->_libpath  = $this->env->getConfig('Twig_lib_path');
		$this->options   = (array)$this->env->getConfig('Twig');
		$this->_loadTwigAutoloader();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * abstruct implements
	 * @see seezoo/core/drivers/view/SZ_View_driver::render()
	 */
	public function renderView($viewFile, $vars = NULL, $return = FALSE)
	{
		$this->_stackVars =& $vars;
		
		$this->bufferStart();
		
		// Twig
		$loader  = new Twig_Loader_Filesystem(dirname($viewFile) . '/');
		$twigEnv = new Twig_Environment($loader, $this->options);
		$twig    = $twigEnv->loadTemplate(basename($viewFile));

		// TODO : implement Twig extension enables.
		
		// Twig execute!
		try
		{
			echo $twig->render($vars);
		}
		catch ( Exception $e )
		{
			throw $e;
		}
		
		// destroy GC
		unset($loader);
		unset($twigEnv);
		unset($twig);
		
		if ( $return === TRUE )
		{
			return $this->getBufferEnd();
		}
		
		if ( ob_get_level() > $this->_initBufLevel + 1 )
		{
			@ob_end_flush();
		}
		else
		{
			$this->getBufferEnd(TRUE);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * load Twig class
	 * 
	 * @access protected
	 */
	protected function _loadTwigAutoloader()
	{
		if ( class_exists('Twig_Autoloader') )
		{
			return;
		}
		
		if ( ! file_exists($this->_libpath . 'Autoloader.php') )
		{
			throw new Exception('Twig Autoloader Class not exists!');
			return;
		}
		require_once($this->_libpath . 'Autoloader.php');
		Twig_Autoloader::register();
	}
}