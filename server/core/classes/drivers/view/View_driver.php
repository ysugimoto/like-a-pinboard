<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * View Driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

abstract class SZ_View_driver
{
	/**
	 * application packages
	 * @var array
	 */
	protected $_packages;
	
	
	/**
	 * initialize buffer level
	 * @var int
	 */
	protected $_initBufLevel;
	protected $_currentBufLevel;
	
	
	/**
	 * rendered buffer
	 * @var string
	 */
	protected $_buffer;
	
	
	/**
	 * Temporary stacked view parameters
	 * @var array
	 */
	protected $_stackVars = array();
	
	
	/**
	 * Current loading file directory
	 */
	protected $_directoryBase;
	
	
	/**
	 * ===========================================-
	 * abstruct method rendering
	 * 
	 * @abstract render
	 * @param string $path
	 * @param mixed  $vars
	 * @param bool   $return
	 * ===========================================-
	 */
	abstract function renderView($viewFile, $vars = NULL, $return = FALSE);
	
	
	public function __construct()
	{
		$this->env = Seezoo::getENV();
		
		$this->_initBufLevel = ob_get_level();
		$this->_currentBufLevel = $this->_initBufLevel;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Load a piece of view file
	 * @access public
	 * @param  string $path
	 */
	public function partial($path)
	{
		$SZ          = Seezoo::getInstance();
		$ext         = pathinfo($path, PATHINFO_EXTENSION);
		$viewExt     = $SZ->view->getExtension();
		$path        = $this->_directoryBase . $path;
		$includeFile = ( empty($ext) ) ? $path . $viewExt : $path;
		
		if ( ! file_exists($includeFile) )
		{
			throw new Exception('Unable to load requested file: ' . $path);
		}
		
		$partial = ( pathinfo($includeFile, PATHINFO_EXTENSION) === ltrim($viewExt, '.') )
		             ? $this->renderView($includeFile, $this->_stackVars, TRUE)
		             : file_get_contents($includeFile);
		
		echo $partial;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Render view in view
	 * 
	 * @access public
	 * @param  string $view
	 * @param  array $vars
	 * @param  bool $return
	 * @return mixed
	 */
	public function render($view, $vars = array(), $return = FALSE)
	{
		$SZ = Seezoo::getInstance();
		return $SZ->view->render($view, $vars, $return);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Rendering for loop
	 * 
	 * @access public
	 * @param  string $view
	 * @param  array $vars
	 */
	public function renderLoop($view, $vars = array())
	{
		$SZ = Seezoo::getInstance();
		foreach ( $vars as $var )
		{
			$SZ->view->render($view, $var);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Clean up the stacks 
	 * 
	 * @access public
	 */
	public function cleanUp()
	{
		if ( $this->_initBufLevel == ob_get_level() )
		{
			$this->_stackVars = array();
			$this->_directoryBase = NULL;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * add buffer
	 * 
	 * @access public
	 * @param  string $buffer
	 */
	public function addBuffer($buffer)
	{
		$this->_buffer .= $buffer;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * get buffer
	 * 
	 * @access public
	 */
	public function getBuffer()
	{
		return $this->_buffer;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * output buffer start
	 * 
	 * @access public
	 */
	public function bufferStart()
	{
		ob_start();
		$this->_currentBufLevel++;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * buffering end and get buffer
	 * 
	 * @access public
	 * @param  bool $returnValue
	 */
	public function getBufferEnd($returnValue = FALSE)
	{
		$buffer = ob_get_contents();
		@ob_end_clean();
		
		if ( $returnValue === TRUE )
		{
			return $buffer;
		}
		else if ( ob_get_level() > $this->_initBufLevel )
		{
			echo $buffer;
			return;
		}
		
		return $this->addBuffer($buffer);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * replace output buffer
	 * 
	 * @access public
	 * @param  string $buf
	 */
	public function replaceBuffer($buf)
	{
		$this->_buffer = $buf;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set current loading directory
	 * 
	 * @access public
	 * @param  string $dir
	 */
	public function setDirectoryBase($dir)
	{
		$this->_directoryBase = $dir;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get current loading directory
	 * 
	 * @access public
	 */
	public function getDirectoryBase()
	{
		return $this->_directoryBase;
	}
}

