<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * XOR encrypt
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Xor_encrypt
{
	protected $key;
	
	
	public function __construct()
	{
		$this->key = get_config('encrypt_key_string');
	}
	
	
	// ----------------------------------------
	
	
	/**
	 * Encode xor
	 * 
	 * @access public
	 * @param  string $string
	 * @return string
	 */
	public function encode($string)
	{
		$rand   = '';
		$enc    = '';
		$length = strlen($string);

		for ( $i = 0; $i < 32; ++$i )
		{
			$rand .= mt_rand(0, mt_getrandmax());
		}
		
		$rand       = sha1($rand);
		$randLength = strlen($rand);
		
		for ( $i = 0; $i < $length; $i++ )
		{
			$tip  = substr($rand, ($i % $randLength), 1);
			$enc .= $tip;
			$enc .= ($tip ^ substr($string, $i, 1));
		}
		
		return $this->_merge($enc);
	}
	
	
	// ----------------------------------------
	
	
	/**
	 * Decode xpr
	 * 
	 * @access public
	 * @param  string $string
	 * @return string
	 */
	public function decode($string)
	{
		$dat    = $this->_merge($string);
		$dec    = '';
		$length = strlen($string);
		
		for ( $i = 0; $i < $length; $i += 2 )
		{
			$dec .= (substr($dat, $i, 1) ^ substr($dat, $i + 1, 1));
		}
		
		return $dec;
	}
	
	
	// ----------------------------------------
	
	
	/**
	 * XOR merge
	 * 
	 * @access protected
	 * @param  string $string
	 * @return string
	 */
	protected function _merge($string)
	{
		$ret        = '';
		$hash       = sha1($this->key);
		$hashLength = strlen($hash);
		$length     = strlen($string);
		
		for ( $i = 0; $i < $length; $i++ )
		{
			$tip = substr($string, $i, 1);
			$ret .= ($tip ^ substr($hash, ($i % $hashLength), 1)); 
		}
		
		return $ret;
	}
}