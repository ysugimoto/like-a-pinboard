<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Application response Management class
 * 
 * @package  Seezoo-Framework
 * @category Classes
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Response implements Growable, Singleton
{
	/**
	 * output headers stack
	 * @var array
	 */
	protected $_headers = array();
	
	
	/**
	 * Environment class instance
	 * @var Environment
	 */
	protected $env;
	
	
	/**
	 * Request class instance
	 * @var Request
	 */
	protected $req;
	
	protected $outputQueue = '';
	
	
	public function __construct()
	{
		$this->env = Seezoo::getENV();
		$this->req = Seezoo::getRequest();
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Response ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->classes('Response');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Graceful redirect
	 * 
	 * @access public
	 * @param  string uri
	 * @param  int    $code
	 * @return $this
	 */
	public function redirect($uri, $code = 302)
	{
		if ( ! preg_match('/^https?:/', $uri ) )
		{
			$rewrite = $this->env->getConfig('enable_mod_rewrite');
			$uri     = $this->env->getConfig('base_url')
			           . (( $rewrite ) ? '' : DISPATCHER . '/') . ltrim($uri, '/');
		}
		$this->setHeader('Location', $uri, TRUE, $code);
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Force redirect immidiately
	 * 
	 * @access public
	 * @param  string $uri
	 * @exit
	 */
	public function forceRedirect($uri)
	{
		$this->redirect($uri);
		$this->send();
		exit;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send Stacked headers
	 * 
	 * @access public
	 * @param  bool $andExit
	 */
	public function send($andExit = FALSE)
	{
		foreach ( $this->_headers as $header )
		{
			( $header[2] )
			  ? @header($header[0], $header[1], $header[2])
			  : @header($header[0], $header[1]);
		}
		
		Event::fire('session_update');
		
		if ( ! empty($this->outputQueue) )
		{
			echo $this->outputQueue;
		}
		
		if ( $andExit === TRUE )
		{
			exit;
		}
	}
	
	// ---------------------------------------------------------------
	
	
	/**
	 * add Output header
	 * 
	 * @access public
	 * @param  string $key
	 * @param  string $value
	 * @param  bool $replace
	 * @param  int $code
	 */
	public function setHeader($key, $value, $replace = TRUE, $code = 0)
	{
		$this->_headers[] = array($key . ': ' . $value, $replace, $code);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add no cahce header
	 * 
	 * @access public
	 */
	public function noCache()
	{
		$this->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', TRUE);
		$this->setHeader('Pragma', 'no-cache', TRUE);
		$this->setHeader('Expires', 'Mon, 26 Jul 1997 05:00:00 GMT', TRUE);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set response body
	 * 
	 * @access public
	 * @param  string $output
	 * @return $this
	 */
	public function setBody($output)
	{
		// Is it possible to transfer compressed gzip?
		$this->setGzipHandler();
		
		Event::fire('final_output', $output);
		
		if ( $this->env->getConfig('enable_debug') === TRUE )
		{
			$memory   = memory_get_usage();
			$debugger = Seezoo::$Importer->classes('Debugger');
			$output   = str_replace('</body>', $debugger->execute($memory) . "\n</body>", $output);
		}
		
		header('HTTP/1.1 200 OK');
		$this->outputQueue = $output;
		
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set json formatted body
	 * 
	 * @access public
	 * @param  mixed $json
	 * @return $this
	 */
	public function setJsonBody($json)
	{
		// Is it possible to transfer compressed gzip?
		$this->setGzipHandler();
		
		header('HTTP/1.1 200 OK');
		header('Content-Type: application/json', TRUE);
		
		$this->outputQueue = ( is_string($json) )
		                       ? trim($json)
		                       : json_encode($json);
		
		// no more output...
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Force download and exit
	 * 
	 * @access public
	 * @param string $filePath
	 * @param string $fileName
	 * @param bool   $isData
	 * @throws Exception
	 */
	public function download($filePath, $fileName = '', $isData = FALSE)
	{
		// Is Download data real data string?
		if ( $isData === TRUE )
		{
			if ( empty($fileName) )
			{
				throw new Exception('Download filename is not empty when direct data download.');
			}
			$fileSize = strlen($filePath);
			$mimeType = 'application/octet-stream';
		}
		// Else, download file
		else
		{
			if ( ! file_exists($filePath) )
			{
				throw new InvalidArgumentException('Download file is not exists! file: ' . $filePath);
			}
			
			if ( empty($fileName) )
			{
				$fileName = basename($filePath);
			}
			$fileSize = filesize($filePath);
			$Mime     = Seezoo::$Importer->library('Mimetype');
			$mimeType = $Mime->detect($filePath);
			if ( ! $mimeType )
			{
				$mimeType = 'application/octet-stream';
			}
		}
		
		// send headers
		$headers = array('Content-Type: ' . $mimeType);
		
		if ( $this->env->isIE )
		{
			$fileName = mb_convert_encoding($fileName, 'SHIFT_JIS', 'UTF-8');
			$this->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
			$this->setHeader('Expires', '0');
			$this->setHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
			$this->setHeader('Content-Transfar-Encoding', 'binary');
			$this->setHeader('Pragma', 'public');
			$this->setHeader('Content-Length', $fileSize);
		}
		else
		{
			$this->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
			$this->setHeader('Content-Transfar-Encoding', 'binary');
			$this->setHeader('Expires', '0');
			$this->setHeader('Pragma', 'no-cache');
			$this->setHeader('Content-Length', $fileSize);
		}
		
		// If download filesize over our PHP memory_limit,
		// we try to split download
		if ( $this->env->memoryLimit < $fileSize )
		{
			foreach ( $this->_headers as $header )
			{
				( $header[2] )
				  ? @header($header[0], $header[1], $header[2])
				  : @header($header[0], $header[1]);
			}
			
			flush();
			if ( ! $isData )
			{
				$fp = fopen($filePath, 'rb');
				do
				{
					echo fread($fp, 4096);
					flush();
				}
				while ( ! feof($fp) );
				fclose($fp);
			}
			else
			{
				$point = 0;
				do
				{
					echo substr($filePath, $point, 4096);
					flush();
					$point += 4096;
				} while ( $point < $fileSize );
			}
		}
		else
		{
			$this->outputQueue = ( $isData )
			                       ? $filePath
			                       : file_get_contents($filePath);
		}
		return $this;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Start gzip compressed output if enable
	 * @access protected
	 */
	protected function setGzipHandler()
	{
		if ( $this->env->getConfig('gzip_compress_output') === TRUE 
		     && extension_loaded('zlib')
		     && strpos((string)$this->req->server('HTTP_ACCEPT_ENCODING'), 'gzip') !== FALSE )
		{
			ob_start('ob_gzhandler');
		}
	}
}