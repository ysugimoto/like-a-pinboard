<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * Breeader test base class
 * 
 * @package  Seezoo-Framework
 * @category Test
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_BreederTest extends PHPUnit_Framework_TestCase
{
	public $breeder;
	public $mode;
	public $isAjaxClass = FALSE;
	
	public function __construct()
	{
		if ( $this->isAjaxClass )
		{
			$_SERVER['X_HTTP_REQUESTED_WITH'] = 'XmlHttpRequest';
		}
		$targetClass   = preg_replace('/(.+)Test$/', '$1', get_class($this));
		$this->breeder = new SZ_FakeBreeder($targetClass, $this->mode);
	}
}


class SZ_FakeBreeder
{
	protected $className;
	protected $mode;
	
	public function __construct($className, $mode)
	{
		$this->className = lcfirst($className);
		$this->mode      = ( $mode ) ? $mode : SZ_MODE_MVC;
	}
	
	public function __call($name, $args = array())
	{
		array_unshift($args, $this->className, $name);
		Seezoo::$outpuBufferMode = FALSE;
		return Application::run($this->mode, implode('/', $args));
	}
}
