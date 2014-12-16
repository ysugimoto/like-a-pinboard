<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * String Filter
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Filter implements Growable
{
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Filter');
	}
	
	
	/**
	 * Filter string
	 * 
	 * @access public
	 * @param  string $str
	 * @return string
	 */
	public function str($str)
	{
		return prep_str($str);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Filter uri format
	 * 
	 * @access public
	 * @param  string $str
	 * @return string
	 */
	public function url($str)
	{
		if ( preg_match('#\Ahttps?:/|\A/#u', $str) )
		{
			return prep_str($s);
		}
		return '';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Filter number
	 * 
	 * @access public
	 * @param  string $str
	 * @return string
	 */
	public function num($str)
	{
		if ( ctype_digit((string)$str) )
		{
			return $str;
		}
		return '';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Filter number-dash format
	 * 
	 * @access public
	 * @param  string $str
	 * @return string
	 */
	public function num_ad($str)
	{
		if ( preg_match('#\A[0-9\-_]\Z#u', $str) )
		{
			return $str;
		}
		return '';
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Filter javascript string
	 * 
	 * @access public
	 * @param  string $str
	 * @return string
	 */
	public function javascript($str)
	{
		return preg_replace_callback('\[^-.0-9a-zA-Z]+/um', array($this, '_escape_uni'), $str);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Escape unicaode
	 * 
	 * @access public
	 * @param  array $matches
	 * @return string
	 */
	protected function _escape_uni($matches)
	{
		return preg_replace(
							'/[0-9a-f]{4}/',
							'\u$0',
							bin2hex(mb_convert_encoding($matches[0], 'UTF-16'))
						);
	}
	

}