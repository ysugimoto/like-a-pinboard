<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Image manipulation Driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/**
 * Abstract class
 * @abstract
 */
abstract class SZ_Pict_driver
{
	/**
	 * Text_pict className
	 * @var string
	 */
	public $textClass = '';
	
	
	/**
	 * Driver type
	 * @var string
	 */
	protected $type;
	
	/**
	 * Process image data object
	 * @var stdClass
	 */
	protected $dat;
	
	
	/**
	 * process settings
	 * @var array
	 */
	protected $setting;
	
	
	/**
	 * Image mimetype/info/function/type about extension map
	 * @var array
	 */
	protected $mimeMethods = array(
		'gif'  => array('image/gif',  'imagecreatefromgif',  'imagegif',  IMG_GIF),
		'jpg'  => array('image/jpeg', 'imagecreatefromjpeg', 'imagejpeg', IMG_JPG),
		'jpeg' => array('image/jpeg', 'imagecreatefromjpeg', 'imagejpeg', IMG_JPG),
		'jpe'  => array('image/jpeg', 'imagecreatefromjpeg', 'imagejpeg', IMG_JPG),
		'png'  => array('image/png',  'imagecreatefrompng',  'imagepng',  IMG_PNG),
	);
	
	
	/**
	 * Default settings
	 * @var array
	 */
	protected $_commonSetting = array(
		'src_image'    => '',
		'display'      => FALSE,
		'dest_image'   => '',
		'overwrite'    => FALSE,
		'jpeg_quelity' => 80
	);
	
	
	/**
	 * Resize/Crop default settings
	 * @var array
	 */
	protected $_settings = array(
		'resize' => array(
							'width'  => '50%',
							'height' => '50%',
							'x'      => 0,
							'y'      => 0,
							'to_x'   => 0,
							'to_y'   => 0,
							'ratio'  => TRUE
						),
		'crop'   => array(
							'width'  => 0,
							'height' => 0,
							'x'      => 0,
							'y'      => 0,
							'to_x'   => 0,
							'to_y'   => 0,
							'ratio'  => FALSE
						)
	);
	
	// =====================================================
	// abstract methods
	// =====================================================
	
	/**
	 * Resize image
	 * 
	 * @abstract
	 * @param int  $width
	 * @param int  $height
	 * @param bool $ratio
	 */
	abstract function resize($width, $height, $ratio);
	
	/**
	 * Crop image 
	 * 
	 * @abstract
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 */
	abstract function crop($x, $y, $width, $height);
	
	/**
	 * Rotate image
	 * 
	 * @abstract
	 * @param int $angle
	 */
	abstract function rotate($angle);
	
	/**
	 * Flip vertical
	 * 
	 * @abstract
	 */
	abstract function flipVertical();
	
	/**
	 * Flip horizontal
	 * 
	 * @abstract
	 */
	abstract function flipHorizontal();
	
	/**
	 * Grayscale
	 * 
	 * @abstract
	 */
	abstract function grayscale();
	
	/**
	 * Sharpness
	 * 
	 * @abstract
	 */
	abstract function sharpness();
	
	/**
	 * Convert image to other extension
	 * @param string $ext
	 */
	abstract function convert($ext);
	
	/**
	 * Display the image dynamic
	 */
	abstract function display();
	
	/**
	 * Save image
	 * @param string $path
	 */
	abstract function save($path);
	
	/**
	 * Destroy image resource ( use GD )
	 */
	abstract function destroy();

	
	
	public function __construct($type)
	{
		$this->type    = $type;
		$this->dat     = new stdClass;
		$this->setting = $this->_commonSetting;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Configure setting
	 * @param array $conf
	 */
	public function configure($conf = array())
	{
		$this->setting = array_merge($this->_commonSetting, $conf);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Detect Process image data and create handle( If GD )
	 * @param  string $path
	 * @param  bool   $return
	 */
	public function createImageHandle($path, $return = FALSE)
	{
		// data stack object
		$dat = new stdClass();
		
		if ( empty($path) )
		{
			$path = $this->setting['src_image'];
		}
		if ( ! file_exists($path) )
		{
			throw new Exception('Source image is not exists!');
			return FALSE;
		}
		
		// detect extension
		$pathinfo = pathinfo($path);
		$ext      = $pathinfo['extension'];
		
		// Is sysytem allowed (image ) extension?
		if ( ! isset($this->mimeMethods[$ext]) )
		{
			throw new Exception('Unsupported file extension!');
			return FALSE;
		}
		
		// extract system definitions info
		list($dat->mime,
		     $createFunction,
		     $dat->outFunction,
		     $dat->type         ) = $this->mimeMethods[$ext];
		
		// get image info
		$img = @getimagesize($path);
		if ( ! $img || $img['mime'] !== $dat->mime )
		{
			throw new Exception('Source image is not image!');
			return FALSE;
		}
		
		// set image info
		$dat->filename  = $pathinfo['basename'];
		$dat->width     = (int)$img[0];
		$dat->height    = (int)$img[1];
		$dat->extension = $ext;
		$dat->filebody  = str_replace('.' . $dat->extension, '', $dat->filename);
		
		if ( $this->type === 'gd' )
		{
			// If GD, create image resource
			if ( ! function_exists($createFunction) )
			{
				throw new Exception($createFunction . ' function is not defined.');
				return FALSE;
			}
			
			$handle = $createFunction($path);
			if ( $return === FALSE )
			{
				$this->dat           = $dat;
				$this->source_handle =& $handle;
			}
			else
			{
				return array('dat' => $dat, 'handle' => $handle);
			}
		}
		else if ( $this->type === 'imagemagick' )
		{
			$this->dat            = $dat;
			$this->source_handle  = $path;
			$this->dest_handle    = new stdClass;
			$this->once_processed = FALSE;
		}
		
	}
	

}