<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Text renderer on Image
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */


class SZ_Text_pict
{
	public $R        = "0x00";
	public $G        = "0x00";
	public $B        = "0x00";
	public $x        = 0;
	public $y        = 0;
	public $size     = 12;
	public $fontPath = '';
	public $angle    = 0;
	public $text     = '';
	
	public function __construct($text, $size, $color, $fontPath)
	{
		$this->text     = $text;
		$this->size     = $size;
		$this->fontPath = ( empty($fontPath) ) ? COREPATH . 'engines/font/mikachan.ttf' : $fontPath;
		$this->_parseHexRGB($color);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Set color
	 * 
	 * @access public
	 * @param  string $color
	 */
	public function setColor($color)
	{
		$this->_parseHexRGB($color);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Parse Hex color to RGB
	 * 
	 * @access protected
	 * @param  string $color
	 */
	protected function _parseHexRGB($color)
	{
		if ( preg_match('|\A#([0-9a-fA-F]{2})([0-9a-fA-F]{2})([0-9a-fA-F]{2})\Z|u', strtolower($color), $matches) )
		{
			$this->R = "0x" . $matches[1];
			$this->G = "0x" . $matches[2];
			$this->B = "0x" . $matches[3];
			return;
		}
		
		throw new Exception('Invalid colo hex format!');
	}
}