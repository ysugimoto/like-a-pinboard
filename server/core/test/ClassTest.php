<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * Class test base class
 * 
 * @package  Seezoo-Framework
 * @category Test
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_ClassTest extends PHPUnit_Framework_TestCase
{
	
	public function setMethodAccessible($method, $class = null)
	{
		$ref = new ReflectionMethod(( $class ) ? $class : $this->class, $method);
		$ref->setAccessible(TRUE);
		
		return $ref;
	}
	
	public function getProtectedProperty($prop, $class = null)
	{
		$ref = new ReflectionProperty(( $class ) ? $class : $this->class, $prop);
		$ref->setAccessible(TRUE);
		
		return $ref->getValue(( $class ) ? $class : $this->class);
	}
}
