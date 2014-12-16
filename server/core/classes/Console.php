<?php if ( ! defined('SZ_EXEC') OR  PHP_SAPI !== 'cli' ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Command line action dispatcher class
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
 
class SZ_Console extends SZ_Driver implements Growable
{
	
	public function __construct()
	{
		$this->driver = $this->_loadDriver('command', 'Console_command', TRUE);
	}
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Dog ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Console');
	}
	
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Execute command line action
	 * 
	 * @access public
	 */
	public function executeCommandLine($argv)
	{
		if ( ! isset($argv[1]) )
		{
			$this->_showUsage();
			exit;
		}
		
		$exec = ltrim($argv[1], '-');
		if ( $exec === 'bite' )
		{
			echo $this->driver->easterEgg();
		}
		else if ( method_exists($this->driver, strtolower($exec)) )
		{
			$args = array_slice($argv, 2);
			$this->driver->{strtolower($exec)}($args);
		}
		else
		{
			$this->_showUsage();
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Show tool's usage
	 * 
	 * @access protected
	 */
	protected function _showUsage()
	{
		echo '============================================' . PHP_EOL;
		echo '  Seezoo-Framework Commnad Line Tool ver ' . SZ_VERSION . PHP_EOL;
		echo '============================================' . PHP_EOL;
		echo 'usage: ' . PHP_EOL;
		echo '  Module Testing      : ./dog [-t|test] [model|library|class|helper|controller] [--app=appname]' . PHP_EOL;
		echo '  Execute Script      : ./dog [-r|run]  [pathinfo] [--app=appname]' . PHP_EOL;
		//echo '  Create Application  : ./dog [-a|app]  ApplicationName' . PHP_EOL;
		echo '  Generate Module     : ./dog [-g|gen] [--app=appname]' . PHP_EOL . PHP_EOL;
		exit;
	}
}
