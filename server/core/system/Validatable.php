<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Validatable interface
 * Implemented interface class can use Validation rules 
 * 
 * @package  Seezoo-Framework
 * @category system
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
interface Validatable
{
	/**
	 * Validate value
	 * 
	 * @access public
	 * @param  SZ_Validation_field $field
	 * @return bool
	 */
	public function validate($field);
}