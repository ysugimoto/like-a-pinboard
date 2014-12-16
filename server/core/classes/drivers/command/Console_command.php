<?php if ( ! defined('SZ_EXEC') OR  PHP_SAPI !== 'cli' ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Comsole line action dispatcher class
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Console_command extends SZ_Command_driver
{
	
	
	/**
	 * option "-t" handler
	 * 
	 * @access public
	 * @param  array $args
	 */
	public function t($args)
	{
		$this->test($args);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Execute test
	 * 
	 * @access public
	 * @param  array $args
	 */
	public function test($args)
	{
		if ( ! isset($args[0]) )
		{
			$args[0] = 'all';
		}
		
		// change current directory
		//chdir(Application::get()->path);
		
		$files = array_slice($args, 1);
		switch ( $args[0] )
		{
			case 'model':
			case 'kennel':
				$this->_runTest('models', $files);
				break;
			case 'helper':
				$this->_runTest('helpers', $files);
				break;
			case 'class':
				$this->_runTest('classes', $files);
				break;
			case 'controller':
			case 'breeder':
				$this->_runTest('controllers', $files);
				break;
			case 'lead':
				$this->_runTest('leads', $files);
				break;
			case 'lib':
			case 'library':
				$this->_runTest('libraries', $files);
				break;
			case 'all':
				$this->_allTest();
				break;
			default:
				echo 'Test target not found!' . PHP_EOL;
				echo 'aborted' . PHP_EOL;
				break;
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Run test
	 * 
	 * @access protected
	 * @param  srtring module
	 * @param  array $files
	 */
	protected function _runTest($moduleName, $files = array())
	{
		echo 'SZFW ' . ucfirst($moduleName) . ' Testing...' . PHP_EOL;
		if ( count($files) > 0 )
		{
			foreach ( $files as $file )
			{
				$module = preg_replace('/\.php$/', '', $file);
				$module = preg_replace('/Test$/', '', $module) . 'Test';
				echo shell_exec('phpunit --colors --bootstrap ' . SZPATH . 'seezoo.php ' . $module . ' ' . SZPATH . 'tests/' . Application::get()->name . '/' .$moduleName . '/' . $module . '.php');
			}
		}
		else
		{
			echo shell_exec('phpunit --colors --bootstrap ' . SZPATH . 'seezoo.php ' . SZPATH . 'tests/' . Application::get()->name . '/' . $moduleName . '/');
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Testing all
	 * 
	 * @access protected
	 */
	protected function _allTest()
	{
		echo 'SZFW All module Testing!' . PHP_EOL;
		if ( file_exists(SZPATH . 'tests/' . Application::get()->name . '/phpunit.xml') )
		{
			echo shell_exec('phpunit --colors --bootstrap ' . SZPATH . 'seezoo.php -c tests/' . Application::get()->name . '/phpunit.xml');
		}
		else
		{
			foreach ( array('controllers', 'classes', 'models', 'libraries', 'helpers') as $module )
			{
				$this->_runTest($module);
			}
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	public function generate($args)
	{
		$this->g($args);
	}
	/**
	 * option "-g" handler
	 * 
	 * @access public
	 * @param  array $args
	 */
	public function g($args)
	{
		// stop output buffering in PHP5.2.x or lower
		if ( version_compare(PHP_VERSION, '5.3', '<') )
		{
			@ob_end_clean();
		}
		echo 'Generate file tool.' . PHP_EOL;
		echo 'Type genetate program:' . PHP_EOL . PHP_EOL;
		echo '[1] Controller' . PHP_EOL;
		echo '[2] Model' . PHP_EOL;
		echo '[3] Both (Controller and Model)' . PHP_EOL;
		echo '[4] ActiveRecords' . PHP_EOL . PHP_EOL;
		echo '[0] Exit' . PHP_EOL . PHP_EOL;
		
		// Create file choose input
		do
		{
			echo ':';
			$type = fgets(STDIN, 10);
			$type = trim($type, "\n");
			if ( ctype_digit($type) )
			{
				if ( $type >= 0 && 6 > $type )
				{
					break;
				}
				else
				{
					echo 'Please Type displayed numbers.' . PHP_EOL;
				}
			}
			else
			{
				echo 'Invalid input. Please Type displayed numbers.' . PHP_EOL;
			}
		}
		while ( 1 );
		
		if ( $type == 0 )
		{
			echo 'Aborted.' . PHP_EOL;
			return;
		}
		
		if ( $type < 4 )
		{
			echo 'Type a Class name:' . PHP_EOL;
			
			// Input class name
			do
			{
				echo ':';
				$name = fgets(STDIN, 20);
				$name = trim($name, "\n");
				if ( preg_match('/^[a-zA-Z][a-zA-Z0-9_]+$/', $name) )
				{
					break;
				}
				else
				{
					echo 'Invalid input. Class name must be alphabet/number/underscore chars only.' . PHP_EOL;
				}
			}
			while ( 1 );
		}
		
		$createFiles = array();
		// switch file types
		switch ( $type )
		{
			// Create Controller only
			case 1:
				$createFiles[] = array(
									APPPATH . 'classes/controllers/' . lcfirst($name) . '.php',
									$this->getControllerTemplate(ucfirst($name)),
									'Controller'
								);
				break;
			// Create Model only
			case 2:
				$createFiles[] = array(
									APPPATH . 'classes/models/' . ucfirst($name) . 'Model.php',
									$this->getModelTemplate(ucfirst($name)),
									'Model'
								);
				break;
			// Create Controller and Model
			case 3:
				$createFiles[] = array(
									APPPATH . 'classes/controllers/' . $name . '.php',
									$this->getControllerTemplate(ucfirst($name)),
									'Controller'
								);
				$createFiles[] = array(
									APPPATH . 'classes/models/' . ucfirst($name) . 'Model.php',
									$this->getModelTemplate(ucfirst($name)),
									'Model'
								);
				break;
			// Create ActiveRecord classes
			case 4:
				$createFiles = $this->_getActiveRecordTargetFiles();
				break;
		}
		
		if ( count($createFiles) > 0 )
		{
			$isAllWrite = FALSE;
			$isAllSkip  = FALSE;
			foreach ( $createFiles as $files )
			{
				if ( $isAllSkip )
				{
					break;
				}
				list($path, $template, $class) = $files;
				$isWrite    = TRUE;
				echo 'Create: ' . $path . PHP_EOL;
				if ( ! $isAllWrite && file_exists($path) )
				{
					// confirm overwrite
					echo $path . ' is already exists.' . PHP_EOL;
					echo 'Are you sure you want to overwrite it? [y/n/ya/na]';
					do
					{
						echo ':';
						$input = fgets(STDIN, 10);
						$input = trim($input, "\n");
						if ( $input === 'y' || $input === 'ya' )
						{
							echo 'Overwriting.' . PHP_EOL;
							$isWrite = TRUE;
							if ( $input === 'ya' )
							{
								$isAllWrite = TRUE;
							}
							break;
						}
						else if ( $input === 'n' || $input === 'na' )
						{
							if ( $input === 'na' )
							{
								$isAllSkip = TRUE;
								break;
							}
							echo 'Skipped.' . PHP_EOL;
							$isWrite = FALSE;
							break;
						}
						else
						{
							echo 'Please type y or n.' . PHP_EOL;
						}
					}
					while ( 1 );
				}
				if ( $isWrite === TRUE )
				{
					$fp = fopen($path, 'wb');
					flock($fp, LOCK_EX);
					fwrite($fp, $template);
					flock($fp, LOCK_UN);
					fclose($fp);
				}
			}
			echo 'Finished!' . PHP_EOL;
			return;
		}
		echo 'Nothing to do.' . PHP_EOL;
	}
	
	public function run($args)
	{
		$this->r($args);
	}
	public function r($args)
	{
		$pathInfo = ( isset($args[0]) ) ? $args[0] : '';
		Application::run(SZ_MODE_CLI, $pathInfo);
		echo PHP_EOL;
	}
}
