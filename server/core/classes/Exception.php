<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * PHP Exception/Error catch class
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Exception
{
	/**
	 * Error methods
	 * @var array
	 */
	protected $_errorHandles = array(
		'message'    => 'getMessage',
		'code'       => 'getCode',
		'file'       => 'getFile',
		'line'       => 'getLine',
		'stackTrace' => 'getTrace'
	);
	
	protected $_errorLevels = array(
		E_ERROR      => 'ERROR',
		E_WARNING    => 'Warning',
		E_PARSE      => 'ParseError',
		E_NOTICE     => 'Notice',
		E_STRICT     => 'StrictError',
		E_DEPRECATED => 'Deprecated'
	);
	
	/**
	 * Error template list
	 * @var array
	 */
	protected $_errorTemplates = array(
		501 => 'database'
	);
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * show 404 message and exit...
	 * 
	 * @access public
	 * @param  string $message
	 * @param  string $backLink
	 */
	public function error404($message = '', $backLink = '')
	{
		if ( defined('SZ_COMMANDLINE_WORKER') ) 
		{
			echo '404:' . (( ! empty($message) ) ? $message : 'Request not found.') . PHP_EOL;
			return;
		}
		header('HTTP/1.1 404 Not Found');
		$env = Seezoo::getENV();
		
		foreach ( Seezoo::getApplication() as $app )
		{
			if ( file_exists($app->path . 'errors/404.php') )
			{
				require($app->path . 'errors/404.php');
				break;
			}
		}
		
		//require(APPPATH . 'errors/404.php');
		exit;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * All system exception handler
	 * 
	 * @access public
	 * @param  Exception $e
	 */
	public function catchException( $e )
	{
		if ( defined('SZ_COMMANDLINE_WORKER') ) 
		{
			echo $e->getMessage() . PHP_EOL;
			return;
		}
		
		$env = Seezoo::getENV();
		Event::fire('session_update');
		
		if ( ! $env )
		{
			echo $e->getMessage();
			return;
		}
		if ( $env->getConfig('error_reporting') === 0 )
		{
			return;
		}
		
		header('HTTP/1.1 500 Internal Server Error');
		// extract errorInfo
		foreach ( $this->_errorHandles as $var => $error )
		{
			$$var = $e->{$error}();
		}
		
		$stackTrace = $this->_formatStackTrace(array_slice($stackTrace, 1));
		// switch template
		$template = ( isset($this->_errorTemplates[$code]) )
		                ? $this->_errorTemplates[$code]
		                : 'general';
		foreach ( Seezoo::getApplication() as $app )
		{
			if ( file_exists($app->path . 'errors/' . $template . '.php') )
			{
				require($app->path . 'errors/' . $template . '.php');
				break;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * PHP Error handler method
	 * 
	 * @access public
	 * @param int    $errorNum
	 * @param string $message
	 * @param string $file
	 * @param int    $line
	 */
	public function catchError($errorNum = 0 ,$message = '', $file = '', $line = 0)
	{
		// Do not show an error that occurred in the operator "@"
		if ( error_reporting() === 0 )
		{
			return FALSE;
		}
		
		if ( defined('SZ_COMMANDLINE_WORKER') ) 
		{
			echo sprintf('Error:%s in %s line %d', $message, $file, $line) . PHP_EOL;
			return;
		}
		Event::fire('session_update');
		$env      = Seezoo::getENV();
		$template = 'phperror';
		$code     = ( isset($this->_errorLevels[$errorNum]) )
		               ? $this->_errorLevels[$errorNum]
		               : 'Error';
		
		header('HTTP/1.1 500 Internal Server Error');
		foreach ( Seezoo::getApplication() as $app )
		{
			if ( file_exists($app->path . 'errors/' . $template . '.php') )
			{
				require($app->path . 'errors/' . $template . '.php');
				break;
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * format stacktrace
	 * 
	 * @access public
	 * @param  array $trace
	 * @return array
	 */
	protected function _formatStackTrace($trace)
	{
		$ret = array();
		$ind = -1;
		while ( isset($trace[++$ind]) )
		{
			if ( ! isset($trace[$ind]['file']) )
			{
				$ret[count($ret) - 1][] = $trace[$ind];
			}
			else
			{
				$ret[] = array($trace[$ind]);
			}
		}
		return $ret;
	}
}