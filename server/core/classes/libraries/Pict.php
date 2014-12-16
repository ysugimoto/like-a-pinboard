<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Image manipulation Library
 * 
 * @package  Seezoo-Framework
 * @category libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Pict extends SZ_Driver implements Growable
{
	/**
	 * Driver type
	 * @var string
	 */
	protected $driverType;
	
	
	/**
	 * Text settings className
	 * @var string
	 */
	protected static $_textClass;
	
	
	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();
		
		$env        = Seezoo::getENV();
		$driverType = $env->getConfig('picture_manipulation') . '_pict';
		
		$this->driver = $this->loadDriver(ucfirst($driverType));
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return View ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Pict');
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Create Image handle 
	 * 
	 * @access public
	 * @param  string $path
	 * @return $this
	 */
	public function create($path = '')
	{
		$this->driver->createImageHandle($path);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Resize image
	 * 
	 * @access public
	 * @param  int  $width
	 * @param  int  $height
	 * @param  bool $ratio
	 * @return $this
	 */
	public function resize($width = '50%', $height = '50%', $ratio = TRUE)
	{
		$this->driver->resize($width, $height, $ratio);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Crop image
	 * 
	 * @access public
	 * @param  int $x
	 * @param  int $y
	 * @param  int $width
	 * @param  int $height
	 * @return $this
	 */
	public function crop($x = 0, $y = 0, $width = 0, $height = 0)
	{
		$this->driver->crop($x, $y, $width, $height);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Rotate image
	 * 
	 * @access public
	 * @param  int $angle
	 * @return $this
	 */
	public function rotate($angle = 0)
	{
		$this->driver->rotate($angle);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Flip image vertical
	 * 
	 * @access public
	 * @return $this
	 */
	public function flipVertical()
	{
		$this->driver->flipVertical();
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Flip image horizontal
	 * 
	 * @access public
	 * @return $this
	 */
	public function flipHorizontal()
	{
		$this->driver->flipHorizontal();
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Create Text setting object
	 * 
	 * @access public
	 * @param  string $text
	 * @param  int    $size
	 * @param  string $color
	 * @param  string $fontPath
	 * @return object
	 */
	public function createText($text, $size = 12, $color = '#000000', $fontPath = '')
	{
		if ( ! self::$_textClass )
		{
			self::$_textClass = $this->loadDriver('Text_pict', FALSE);
			$this->driver->textClass = self::$_textClass;
		}

		return new self::$_textClass($text, $size, $color, $fontPath);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Simple text to image
	 * 
	 * @access public
	 * @param  string $text
	 * @param  int    $x
	 * @param  int    $y
	 * @param  int    $fontSize
	 * @param  string $fontPath
	 * @return $this
	 */
	public function text($text = '', $x = 0, $y = 0, $fontSize = 12, $fontPath = '')
	{
		$this->driver->text($text, $x, $y, $fontSize, $fontPath = '');
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Garyscale
	 * 
	 * @access public
	 * @return $this
	 */
	public function grayscale()
	{
		$this->driver->grayscale();
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Sharpness
	 * 
	 * @access public
	 * @return $this
	 */
	public function sharpness()
	{
		$this->driver->sharpness();
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Image set on image
	 * 
	 * @access public
	 * @param  string $path
	 * @param  int    $x
	 * @param  int    $y
	 * @return $this
	 */
	public function image($path, $x = 0, $y = 0)
	{
		$this->driver->image($path, $x, $y);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Store image
	 * 
	 * @access public
	 * @param  string $path
	 * @return $this
	 */
	public function save($path = '')
	{
		$this->driver->save($path);
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Destroy image on memory
	 * 
	 * @access public
	 * @return $this
	 */
	public function destroy()
	{
		$this->driver->destroy();
		return $this;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Output image binary dynamical
	 * 
	 * @access public
	 */
	public function display()
	{
		$this->driver->display();
	}
}