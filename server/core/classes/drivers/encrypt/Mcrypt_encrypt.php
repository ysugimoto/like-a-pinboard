<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Mcrypt encrypt
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Mcrypt_encrypt
{
	protected $cipher = MCRYPT_RIJNDAEL_256;
	protected $mode   = MCRYPT_MODE_CBC;
	protected $key;
	
	
	public function __construct()
	{
		$this->key = get_config('encrypt_key_string');
	}
	
	
	// ----------------------------------------
	
	
	/**
	 * Encode mcrypt
	 * 
	 * @access public
	 * @param  string $string
	 * @return string
	 */
	public function encode($string)
	{
		$size   = mcrypt_get_iv_size($this->cipher, $this->mode);
		$vect   = mcrypt_create_iv($size, MCRYPT_RAND);
		$crypt  = mcrypt_encrypt($this->cipher, $this->key, $string, $this->mode, $vect);
		$string = $vect . $crypt;
		
		$hashKey   = sha1($this->key);
		$keyLength = strlen($hashKey);
		$length    = strlen($string);
		$output    = '';

		for ( $i = 0, $j = 0; $i < $length; ++$i, ++$j )
		{
			if ( $j >= $keyLength )
			{
				$j = 0;
			}
			$dat = ord($string[$i]) + ord($hashKey[$j]);
			$output .= chr($dat % 256);
		}
		
		return $output;
	}
	
	
	// ----------------------------------------
	
	
	/**
	 * Decode mcrypt
	 * 
	 * @access public
	 * @param  string $string
	 * @return string
	 */
	public function decode($string)
	{
		$hashKey   = sha1($this->key);
		$keyLength = strlen($hashKey);
		$length    = strlen($string);
		$tmp       = '';
		
		for ( $i = 0, $j = 0; $i < $length; ++$i, ++$j )
		{
			if ( $j >= $keyLength )
			{
				$j = 0;
			}
			$dat = ord($string[$i]) - ord($hashKey[$j]);
			
			if ( $dat < 0 )
			{
				$dat = $dat + 256;
			}
			$tmp .= chr($dat);
		}
		
		$size  = mcrypt_get_iv_size($this->cipher, $this->mode);
		
		if ( $size > strlen($tmp) )
		{
			return FALSE;
		}
		
		$vect = substr($tmp, 0, $size);
		$tmp  = substr($tmp, $size);
		$dec  = mcrypt_decrypt($this->cipher, $this->key, $tmp, $this->mode, $vect);
		return rtrim($dec, "\0");
	}
}