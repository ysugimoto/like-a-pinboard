<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/** ===================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Encrypt-Decrypt string
 * 
 * @package Seezoo-Framework
 * @category Libraries
 * @author Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * 
 * ====================================================================
 */


class SZ_Encrypt extends SZ_Driver implements Growable
{
	
	/**
	 * Driver handles
	 */
	protected $_mcrypt;
	protected $_blowfish;
	protected $_xor;
	
	/**
	 * Initial mode
	 */
	protected $mode = 'xor';
	
	
	public function __construct()
	{
		parent::__construct();
		
		if ( extension_loaded('mcrypt') )
		{
			$this->mode = 'mcrypt';
		}
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Encrypt');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set encrypt mode
	 * 
	 * @access public
	 * @param  string $mode
	 */
	public function setMode($mode)
	{
		$this->mode = $mode;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode string
	 * 
	 * @access public
	 * @param  string $string
	 * @return string (encoded)
	 */
	public function encode($string)
	{
		switch ( $this->mode )
		{
			case 'mcrypt':
				return $this->encodeMcrypt($string);
			case 'blowfish':
				return $this->encodeBlowfish($string);
			default:
				return $this->encodeXOR($string);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode string
	 * 
	 * @access public
	 * @param  string
	 * @return string (decoded)
	 */
	public function decode($string)
	{
		switch ( $this->mode )
		{
			case 'mcrypt':
				return $this->decodeMcrypt($string);
			case 'blowfish':
				return $this->decodeBlowfish($string);
			default:
				return $this->decodeXOR($string);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode string by mcrypt algorithm
	 * 
	 * @access public
	 * @param  string $string
	 * @return string (encoded)
	 */
	public function encodeMcrypt($string)
	{
		if ( ! $this->_mcrypt )
		{
			$this->_mcrypt = $this->loadDriver('Mcrypt_encrypt');
		}
		
		$enc = $this->_mcrypt->encode($string);
		return base64_encode($enc);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode string by mcrypt algorithm
	 * 
	 * @access public
	 * @param  string
	 * @return string (decoded)
	 */
	public function decodeMcrypt($string)
	{
		if ( ! $this->_mcrypt )
		{
			$this->_mcrypt = $this->loadDriver('Mcrypt_encrypt');
		}
		
		$string = base64_decode($string);
		return $this->_mcrypt->decode($string);
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode string by Blowfish algorithm
	 * 
	 * @access public
	 * @param  string $string
	 * @return string (encoded)
	 */
	public function encodeBlowfish($string)
	{
		if ( ! $this->_blowfish )
		{
			$this->_blowfish = $this->loadDriver('Blowfish_encrypt');
		}
		
		$enc = $this->_blowfish->encode($string);
		return base64_encode($enc);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode string by Blowfish algorithm
	 * 
	 * @access public
	 * @param  string
	 * @return string (decoded)
	 */
	public function decodeBlowfish($string)
	{
		if ( ! $this->_blowfish )
		{
			$this->_blowfish = $this->loadDriver('Blowfish_encrypt');
		}
		
		$string = base64_decode($string);
		return $this->_blowfish->decode($string);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode string by XOR bit merge
	 * 
	 * @access public
	 * @param  string $string
	 * @return string (encoded)
	 */
	public function encodeXOR($string)
	{
		if ( ! $this->_xor )
		{
			$this->_xor = $this->loadDriver('Xor_encrypt');
		}
		
		$enc = $this->_xor->encode($string);
		return base64_encode($enc);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Decode string by XOR bit merge
	 * 
	 * @access public
	 * @param  string
	 * @return string (decoded)
	 */
	public function decodeXOR($string)
	{
		if ( ! $this->_xor )
		{
			$this->_xor = $this->loadDriver('Xor_encrypt');
		}
		
		$string = base64_decode($string);
		return $this->_xor->decode($string);
	}
}