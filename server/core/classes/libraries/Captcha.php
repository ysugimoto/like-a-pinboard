<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Form captcha
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Captcha implements Growable
{
	/**
	 * Captcha setting
	 * @var array
	 */
	protected $_setting = array();
	
	
	/**
	 * Random string pools
	 * @var array
	 */
	protected $_pools = array(
		'alpha'         => array('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 26),
		'alpha_numeric' => array('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', 36),
		'number'        => array('0123456789', 10),
		'japanese'      => array('あいうえおかきくけこさしすせそなにぬねのたちつてとはひふへほまみむめもやゆよらりるれろわをん', 46)
	);
	
	
	/**
	 * Default captcha setting
	 * @var array
	 */
	protected $_defaultSettings = array(
		'captcha_name' => 'sz_captcha',
		'save_path'    => '',
		'font_size'    => 20,
		'length'       => 5,
		'width'        => 300,
		'height'       => 40,
		'http_path'    => '',
		'type'         => 'alpha'
	);
	
	public function __construct($conf = array())
	{
		$this->configure($conf);
	}
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Captcha ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Captcha');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Configuration
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		$this->_setting = array_merge($this->_defaultSettings, $conf);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate captcha
	 * 
	 * @access public
	 * @return string
	 */
	public function generate()
	{
		if ( ! isset($this->_pools[$this->_setting['type']]) )
		{
			throw new Exception('Invalid type!');
			return FALSE;
		}
		
		// extract setting
		foreach ( $this->_setting as $key => $val )
		{
			$$key = $val;
		}
		
		// encoding transaction start
		$def = mb_internal_encoding();
		mb_internal_encoding('UTF-8');
		
		list($pool, $poolLength) = $this->_pools[$type];
		$char      = '';
		$img       = imagecreatetruecolor($width, $height);
		$color     = imagecolorallocate($img, 0xff, 0xff, 0xff);
		$textColor = imagecolorallocate($img, 0x33, 0x33, 0x33);
		$font      = SZPATH . 'engines/font/mikachan.ttf';
		$pointX    = round(($width - 50) / $length);
		$pointY    = round(($height / 2) + ($font_size / 2));
		
		// captcha image create
		imagefill($img, 0, 0, $textColor);
		imagefilledrectangle($img, 2, 2, $width - 3, $height - 3, $color);
		for ( $i = 0; $i < $length; ++$i )
		{
			$r     = mt_rand(0, $poolLength - 1);
			$angle = ( $type === 'japanese' ) ?  mt_rand(-90, 90) : mt_rand(-45, 45);
			$c     = ( function_exists('mb_substr') ) ? mb_substr($pool, $r, 1) : substr($pool, $r, 1);
			
			imagettftext(
						$img,
						$font_size,
						$angle,
						$pointX * $i + 30,
						$pointY + (( $angle < 0 ) ? -10 : 0),
						$textColor,
						$font,
						$c
					);
			$char .= $c;
		}
		
		$save_path = trail_slash($save_path);
		$filename  = 'captcha_' . md5(uniqid(mt_rand(), TRUE)) . '.gif';
		
		// encoding transaction start
		mb_internal_encoding($def);
		
		// generate captcha image
		imagegif($img, $save_path . $filename);
		
		$obj = new stdClass;
		$obj->string     = $char;
		$obj->image_path = prep_str($http_path . $filename);
		$obj->image_html = '<img src="' . prep_str($http_path . $filename) . '" width="' . $width . '" height="' . $height . '" alt="captcha" />';
		
		return $obj;
	}
}