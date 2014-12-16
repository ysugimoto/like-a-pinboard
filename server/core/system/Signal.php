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
 * Application signals
 * 
 * Controller should return these signals
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class Signal
{
	/**
	 * Success signal
	 * @var int
	 */
	const success  = 0x0001;
	
	
	/**
	 * Failure signal
	 * @var int
	 */
	const failed   = 0x0010;
	
	
	/**
	 * Redirect signal
	 * @var int
	 */
	const redirect = 0x0100;
	
	
	/**
	 * Finished signal
	 * @var int
	 */
	const finished = 0x1000;
	
	// Do you need more signals?
}
