<?php

/**
 * ====================================================================
 * 
 * Seezoo-Framework bootstrap file
 * 
 * Define Path constants and load Core files.
 * 
 * @author Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * 
 * ====================================================================
 */

// ---- user defined on your environment ---- //
define('APPLICATION_DIR', 'applications/');

// end ---- --------------------------------- //

// Initial, force clean output buffer
@ob_end_clean();
// System always handles the UTF-8 encoding.
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');

// Framework version
define('SZ_VERSION', '0.6');

// System path constants difinition
define('SZ_EXEC',    TRUE);
define('DISPATCHER', basename($_SERVER['SCRIPT_FILENAME']));
define('ROOTPATH',   realpath(dirname($_SERVER['SCRIPT_FILENAME'])) . '/');
define('SZPATH',     dirname(__FILE__) . '/');

// Prepare System files
require_once(SZPATH . 'core/system/exceptions.php');
require_once(SZPATH . 'core/system/common.php');
require_once(SZPATH . 'core/system/constants.php');
require_once(SZPATH . 'core/system/Autoloader.php');

// Autoloader register
Autoloader::init();

Seezoo::startup();

// Did you request from CLI?
if ( PHP_SAPI === 'cli' )
{
	// Unit test handling
	if ( strpos($_SERVER['argv'][0], 'phpunit') !== FALSE )
	{
		define('SZ_COMMANDLINE_WORKER', 1);
		$application = Application::init()->extendAll();
		Seezoo::addApplication($application);
		Seezoo::prepare($application, SZ_MODE_CLI, '');
		spl_autoload_register(array('Autoloader', 'loadTestModule'));
	}
	else
	{
		// detect application
		$app      = SZ_BASE_APPLICATION_NAME;
		$prefix   = SZ_PREFIX_BASE;
		$argInput = array();
		
		foreach ( $_SERVER['argv'] as $key => $argv )
		{
			if ( preg_match('/^\-\-app=(.+)$/u', trim($argv), $match) )
			{
				$app = $match[1];
				continue;
			}
			else if ( preg_match('/^\-\-prefix=(.+)$/u', trim($argv), $match) )
			{
				$prefix = $match[1];
				continue;
			}
			$argInput[] = $argv;
		}
		
		$application = Application::init($app, $prefix);
		Seezoo::prepare($application, SZ_MODE_CLI, '/');
		
		// Command line tools ignittion
		$dog = Seezoo::$Importer->classes('Console');
		$dog->executeCommandLine($argInput);
		exit;
	}
}
