<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Image manipulation use PHP GD
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Gd_pict extends SZ_Pict_driver
{
	/**
	 * Current processing image resource
	 * @var resource
	 */
	protected $source_handle;
	
	
	/**
	 * Process destination image resource
	 * @var resource
	 */
	protected $dest_handle;
	
	
	public function __construct()
	{
		parent::__construct('gd');
	}
	
	
	public function __destruct()
	{
		$this->destroy();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Resize image
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::resize()
	 * 
	 * @access public
	 * @param int $width
	 * @param int $height
	 * @param bool $ratio
	 */
	public function resize($width, $height, $ratio)
	{
		$this->preprocess();
		
		$params = ( is_array($width) )
		            ? $width
		            : array('width' => $width, 'height' => $height, 'ratio' => $ratio);
		$setting = array_merge($this->_settings['resize'], $params);
		$setting = $this->_formatNumber($setting);
		$setting['src_width']  = $this->dat->width;
		$setting['src_height'] = $this->dat->height;
		
		$this->_process($setting);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Crop image
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::trim()
	 * 
	 * @access public
	 * @param int $x
	 * @param int $y
	 * @param int $width
	 * @param int $height
	 */
	public function crop($x, $y, $width, $height)
	{
		$this->preprocess();
		
		$params = ( is_array($x) )
		            ? $x
		            : array('width' => $width, 'height' => $height, 'x' => $x, 'y' => $y);
		$setting = array_merge($this->_settings['crop'], $params);
		$setting = $this->_formatNumber($setting);
		$setting['src_width']  = $setting['width'];
		$setting['src_height'] = $setting['height'];
		
		$this->_process($setting);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Rotate image
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::rotate()
	 * 
	 * @access public
	 * @param  int $angle
	 */
	public function rotate($angle = 0)
	{
		$this->preprocess();
		
		if ( $angle % 360 === 0 )
		{
			$this->dest_handle = $this->source_handle;
			imagedestroy($this->source_handle);
			return;
		}
		else if ( ! function_exists('imagerotate') )
		{
			imagedestroy($this->source_handle);
			throw new Exception('imagerotate function is not supported on this environment!');
			return FALSE;
		}
		
		// @notice
		// GD imagerotate process +angle to left rotattion.
		// so that, we FIX reserve rotation on this angle.
		//$angle      = -$angle;
		$background = imagecolorallocate($this->source_handle, 0xFF, 0xFF, 0xFF);
		$this->dest_handle = imagerotate($this->source_handle, -$angle, $background);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Flip image vetical
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::flipVertical()
	 * @see https://gist.github.com/990417 thanks!
	 * 
	 * @access public
	 */
	public function flipVertical()
	{
		$this->preprocess();
		$this->dest_handle = imagecreatetruecolor($this->dat->width, $this->dat->height);
		
		if ( ! imagecopyresampled(
									$this->dest_handle,
									$this->source_handle,
									0,
									0,
									0,
									$this->dat->height - 1,
									$this->dat->width,
									$this->dat->height,
									$this->dat->width,
									0 - $this->dat->height
								) )
		{
			throw new Exception('imagecopyresampled process failed!');
			return FALSE;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Flip image horizontal
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::flipHorizontal()
	 * @see https://gist.github.com/990417 thanks!
	 * 
	 * @access public
	 */
	public function flipHorizontal()
	{
		$this->preprocess();
		$this->dest_handle = imagecreatetruecolor($this->dat->width, $this->dat->height);
		
		if ( ! imagecopyresampled(
									$this->dest_handle,
									$this->source_handle,
									0,
									0,
									$this->dat->width - 1,
									0,
									$this->dat->width,
									$this->dat->height,
									0 - $this->dat->width,
									$this->dat->height
								) )
		{
			throw new Exception('imagecopyresampled process failed!');
			return FALSE;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Filter image to grayscale
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::grayscale()
	 * 
	 * @access public
	 */
	public function grayscale()
	{
		if ( ! function_exists('imagefilter') )
		{
			throw new Exception('Undefined or cannot use "imagefilter" function!');
			return FALSE;
		}
		
		$this->preprocess();
		
		if ( ! imagefilter($this->source_handle, IMG_FILER_GRAYSCALE) )
		{
			throw new Exception('imagefileter process failed!');
			return FALSE;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Image sharpness
	 * 
	 * @access public
	 * 
	 * --------------------------------------------------------
	 * Convolution Matrix for sharpness:
	 * 
	 * |  0, -1,  0 |
	 * | -1,  5, -1 |
	 * | 0,  -1,  0 |
	 * 
	 */
	public function sharpness()
	{
		if ( ! function_exists('imageconvolution') )
		{
			throw new Exception('Undefined or cannot use "imageconvolution" function!');
			return FALSE;
		}
		
		$this->preprocess();
		$matrix = array(
						array( 0, -1,  0),
						array(-1,  5, -1),
						array( 0, -1,  0)
					);
		if ( ! imageconvolution($this->source_handle, $matrix, 1, 1) )
		{
			throw new Exception('imageconvolution process failed!');
			return FALSE;
		}
		
		$this->dest_handle = $this->source_handle;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Image on image
	 * 
	 * @access public
	 * @param  string $path
	 * @param  int $x
	 * @param  int $y
	 */
	public function image($path, $x, $y)
	{
		$this->preprocess();
		
		if ( is_array($path) )
		{
			$params = $path;
		}
		else
		{
			$params = array_merge(
								array('image_path' => '',    'x' => 0,  'y' => 0),
								array('image_path' => $path, 'x' => $x, 'y' => $y)
							);
		}
		if ( ! file_exists($params['image_path']) )
		{
			throw new Exception('Blend image file is not found!');
			return FALSE;
		}
		
		$img = $this->createImageHandle($params['image_path'], TRUE);
		$imgHandle = $img['handle'];
		$imgData   = $img['dat'];
		
		if ( ! imagecopyresampled(
									$this->source_handle,
									$imgHandle,
									$params['x'],
									$params['y'],
									0,
									0,
									$this->dat->width,
									$this->dat->height,
									$imgData->width,
									$imgData->height
								) )
		{
			throw new Exception('imagecopyresampled process failed!');
			return FALSE;
		}
		$this->dest_handle = $this->source_handle;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Text on image
	 * 
	 * @access public
	 * @param  mixed $text
	 * @param  int $x
	 * @param  int $y
	 * @param  int $size
	 * @param  string $fontPath
	 */
	public function text($text, $x, $y, $size, $fontPath)
	{
		$this->preprocess();
		
		// Parameter passed from Text_pict class instance
		if ( $text instanceof $this->textClass )
		{
			$color = imagecolorallocate($this->source_handle, $text->R, $text->G, $text->B);
			if ( ! imagettftext(
								$this->source_handle,
								$text->size,
								$text->angle,
								$text->x,
								$text->y + round((int)$text->size / 2),
								$color,
								$text->fontPath,
								$text
							) )
			{
				throw new Exception('imagettftext process failed!');
				return FALSE;
			}
			$this->dest_handle = $this->source_handle;
		}
		// normal rendering
		else
		{
			$color = imagecolorallocate($this->source_handle, 0x00, 0x00, 0x00);
			if ( ! imagettftext(
								$this->source_handle,
								$size,
								0,
								$x,
								$y + round((int)$size / 2),
								$color,
								( empty($fontPath) ) ? COREPATH . 'engines/font/mikachan.ttf' : $fontPath,
								$text
							) )
			{
				throw new Exception('imagettftext process failed!');
				return FALSE;
			}
			$this->dest_handle = $this->source_handle;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Resize / Crop process
	 * 
	 * @access potected
	 * @param  array $param
	 */
	protected function _process($param)
	{
		$this->dest_handle = imagecreatetruecolor($param['width'], $param['height']);
		
		if ( $this->dat->type == IMG_GIF )
		{
			$color = imagecolorallocatealpha($this->dest_handle, 0, 0, 0, 127);
			imagefill($this->dest_handle, 0, 0, $color);
			imagecolortransparent($this->dest_handle, $color);
		}
		else if ( $this->dat->type == IMG_PNG )
		{
			imagealphablending($this->dest_handle, FALSE);
			imagesavealpha($this->dest_handle, TRUE);
			$color = imagecolorallocatealpha($this->dest_handle, 0, 0, 0, 127);
			imagefill($this->dest_handle, 0, 0, $color);
		}
		else
		{
			$color = imagecolorallocate($this->dest_handle, 255, 255, 255);
			imagefill($this->dest_handle, 0, 0, $color);
		}
		
		
		if ( ! imagecopyresampled(
									$this->dest_handle,
									$this->source_handle,
									$param['to_x'],
									$param['to_y'],
									$param['x'],
									$param['y'],
									$param['width'],
									$param['height'],
									$param['src_width'],
									$param['src_height']
								) )
		{
			throw new Exception('imagecopyresampled process failed!');
			return FALSE;
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Format parameter % to px
	 * 
	 * @access protected
	 * @param  array $settings
	 * @return array
	 */
	protected function _formatNumber($settings)
	{
		if ( strrpos($settings['width'], '%') !== FALSE )
		{
			$settings['width'] = round($this->dat->width * (int)$settings['width'] / 100);
		}
		if ( strrpos($settings['height'], '%') !== FALSE )
		{
			$settings['height'] = round($this->dat->height * (int)$settings['height'] / 100);
		}
		if ( $settings['ratio'] === TRUE )
		{
			//if ( $settings['width'] > $settings['height'] ) 
			if ( $this->dat->width > $this->dat->height )
			{
				$settings['height'] =  round($settings['width'] * ( $this->dat->height / $this->dat->width));
			}
			else 
			{
				$settings['width'] =  round($settings['height'] * ( $this->dat->width / $this->dat->height));
			}
		}
		return $settings;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Target image source setup
	 * 
	 * @access protected
	 */
	protected function preprocess()
	{
		if ( is_resource($this->dest_handle) )
		{
			$this->_transfar();
		}
		else
		{
			$this->createImageHandle($this->setting['src_image']);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Convert image
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::convert()
	 * 
	 * @access public
	 * @param  string $ext
	 */
	public function convert($ext)
	{
		if ( ! isset($this->mimeMethods[$ext]) )
		{
			throw new Exception('Unsupported file extension!');
			return FALSE;
		}
		
		$this->dat->extension = $ext;
		$this->dat->filename  = $this->dat->filebody . '.' . $ext;
		list($this->dat->mime,
		     , // no use create function
		     $this->dat->outFunction,
		     $this->dat->type         ) = $this->mimeMethods[$ext];
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Save image
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::save()
	 * 
	 * @access public
	 * @param  string $path
	 */
	public function save($path)
	{
		if ( empty($path) )
		{
			$path = $this->setting['dest_image'];
		}
		
		if ( empty($path) || ! file_exists($path) )
		{
			$this->destroy();
			throw new Exception('destination directory is not exists!');
			return FALSE;
		}
		
		if ( ! is_dir($path) )
		{
			$filename  = basename($path);
			$exp       = explode('.', $filename);
			$extension = array_pop($exp);
			$file_body = implode('.', $exp);
			$path      = trail_slash(dirname($path));
		}
		else 
		{
			$filename  = $this->dat->filename;
			$extension = $this->dat->extension;
			$file_body = $this->dat->filebody;
			$path      = trail_slash($path); 
		}
		
		if ( ! really_writable($path) )
		{
			$this->destroy();
			throw new Exception('destination directory have not write permission!');
			return FALSE;
		}
		
		$function = $this->dat->outFunction;
		
		// May I overwrite old image?
		if ( $this->setting['overwrite'] === FALSE )
		{
			$idx = 0;
			while ( file_exists($path . $filename) )
			{
				$filename = $file_body . '_' . ++$idx . '.' . $extension;
			}
		}
		
		$function($this->dest_handle, $path . $filename);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Display image
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::display()
	 * 
	 * @access public
	 */
	public function display()
	{
		header('Content-Type: ' . $this->dat->mime);
		$function = $this->dat->outFunction;
		$function($this->dest_handle);
		$this->destroy();
		exit;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Destroy image handles
	 * 
	 * @access public
	 */
	public function destroy()
	{
		if ( is_resource($this->source_handle) )
		{
			imagedestroy($this->source_handle);
		}
		if ( is_resource($this->dest_handle) )
		{
			imagedestroy($this->dest_handle);
		}
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * pass the current process image resource
	 */
	protected function _transfar()
	{
		$this->source_handle = $this->dest_handle;
		
		// update processed image width/height
		$this->dat->width    = imagesx($this->source_handle);
		$this->dat->height   = imagesy($this->source_handle);
	}
}