<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Image manipulation use Imagemagick ( execute on "convert" command )
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Imagemagick_pict extends SZ_Pict_driver
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
	
	
	/**
	 * process executed flag
	 * @var bool
	 */
	protected $once_processed = FALSE;
	
	
	/**
	 * Command stack
	 * @var array
	 */
	protected $_command = array();
	
	
	/**
	 * Composite stack
	 * @var array
	 */
	protected $_composite = array();
	
	
	/**
	 * Composite files
	 * @var array
	 */
	protected $_compositeFile;
	
	
	/**
	 * ImageMagick command path
	 * @var string
	 */
	protected $_commandPath;
	
	
	/**
	 * Process logs
	 * @var array
	 */
	protected $_log = array();
	
	
	public function __construct()
	{
		parent::__construct('imagemagick');
		$this->dest_handle = new stdClass;
		
		$env = Seezoo::getENV();
		$this->_commandPath = $env->getConfig('imagemagick_lib_path');
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add command stack
	 * 
	 * @access protected
	 * @param  string $cmd
	 */
	protected function cmd($cmd)
	{
		$this->_command[] = escapeshellarg('-' . trim($cmd, '-'));
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Add command argument
	 * 
	 * @access protected
	 * @param  string $arg
	 */
	protected function arg($arg)
	{
		$this->_command[] = escapeshellarg($arg);
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Execute command
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function exec()
	{
		if ( count($this->_command) === 0 && count($this->_composite) === 0 )
		{
			return FALSE;
		}
		$this->arg($this->source_handle);
		
		if ( ! $this->_compositeFile )
		{
			$this->arg($this->dest_handle->directory . $this->dest_handle->filename);
			$command = escapeshellcmd($this->_commandPath) . ' -verbose ' . implode(' ', $this->_command);
		}
		else
		{
			$this->arg($this->_compositeFile);
			$command = escapeshellcmd($this->_commandPath) . ' -verbose ' . implode(' ', $this->_command) . ' '
			             . implode(' ', $this->_composite) . ' '
			             . escapeshellarg($this->dest_handle->directory . $this->dest_handle->filename);
		}
		
		$ret = @shell_exec($command);
		
		$this->_command = array();
		$this->_log[] = $command;
		
		if ( ! $ret )
		{
			throw new Exception('Imagemagick convert command failed! Command:' . $command);
			return FALSE;
		}
		$this->once_processed = TRUE;
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Get process log
	 * 
	 * @access public
	 * @return string
	 */
	public function getLog()
	{
		return implode("\n", $this->_log);
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
		
		if ( $setting['ratio'] === TRUE )
		{
			$this->cmd('geometry');
		}
		else
		{
			$this->cmd('resize');
		}
		$this->arg($setting['width'] . 'x' . $setting['height']);
		$this->sharpness();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Crop image
	 * @see seezoo/core/classes/drivers/pict/SZ_Pict_driver::crop()
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
		
		$this->cmd('crop');
		$this->arg($setting['width'] . 'x' . $setting['height'] . '+' . $setting['x'] . '+' . $setting['y']);
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
			return;
		}
		
		$this->cmd('rotate');
		$this->arg($angle);
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
		
		$this->cmd('flip');
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
		
		$this->cmd('flop');
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
		$this->preprocess();
		
		$this->cmd('type');
		$this->arg('Grayscale');
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
		$this->preprocess();
		
		$this->cmd('unsharp');
		$this->arg('2x1.4+0');
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
								array('image_path' => '',    'x' =>  0, 'y' =>  0),
								array('image_path' => $path, 'x' => $x, 'y' => $y)
			);
		}
		if ( ! file_exists($params['image_path']) )
		{
			throw new Exception('Blend image file is not found!');
			return FALSE;
		}
		$this->_compositeFile = $params['image_path'];
		$this->_composite[] = '-geometry';
		$this->_composite[] = escapeshellarg(
		                                  (((int)$params['x'] > 0 ) ? '+' : '-') . (int)$params['x']
		                                  . (((int)$params['y'] > 0 ) ? '+' : '-') . (int)$params['y']
		                                      );
		$this->_composite[] = '-composite';
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Text on image
	 */
	public function text($text, $x, $y, $size, $fontPath)
	{
		// This process need "montage" command.
		// not implement...
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
		
		// May I overwrite old image?
		if ( $this->setting['overwrite'] === FALSE )
		{
			$idx = 0;
			while ( file_exists($path . $filename) && $idx <= 1000 )
			{
				$filename = $file_body . '_' . ++$idx . '.' . $extension;
			}
			
			if ( $idx >= 1000 )
			{
				throw new Exception('Too many page order number!');
				return FALSE;
			}
		}
		
		$this->dest_handle->directory = $path;
		$this->dest_handle->file_body = $file_body;
		$this->dest_handle->filename  = $filename;
		$this->dest_handle->extension = $extension;
		
		$this->exec();
	}
	
	
	// --------------------------------------------------
	
	
	/**
	 * Target image source setup
	 * 
	 * @access protected
	 */
	protected function preprocess()
	{
		// Is process over second time?
		if ( $this->once_processed === TRUE )
		{
			$this->source_handle = $this->dest_handle->directory
			                       . $this->dest_handle->filename;
		}
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
		$this->save();
		header('Content-Type: ' . $this->dat->mime);
		readfile($this->dest_handle->directory . $this->dest_handle->filename);
		unlink($this->dest_handle->directory . $this->dest_handle->filename);
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
		
	}
}