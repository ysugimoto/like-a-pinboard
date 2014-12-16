<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Simple HTTP requst utility
 * 
 * @package  Seezoo-Framework
 * @category Libraries
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Http extends SZ_Driver implements Growable
{
	/**
	 * Request class instance
	 * @var Request
	 */
	protected $req;
	
	
	/**
	 * Request URI
	 * @var string
	 */
	protected $uri;
	
	
	/**
	 * Request method
	 * @var string
	 */
	protected $method   = 'GET';
	
	
	/**
	 * Request headers
	 * @var array
	 */
	protected $header   = array();
	
	
	/**
	 * Error string
	 * @var string
	 */
	protected $_error;
	
	
	/**
	 * Post parameters
	 * @var string
	 */
	protected $postBody = '';
	
	public $connectTimeout = 30;
	public $timeout = 30;
	
	
	public function __construct()
	{
		parent::__construct();
		
		$this->req = Seezoo::getRequest();
	}
	
	
	/**
	 * Growable interface implementation
	 * 
	 * @access public static
	 * @return SZ_Http ( extended )
	 */
	public static function grow()
	{
		return Seezoo::$Importer->library('Http');
	}
	
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set request URI
	 * 
	 * @access public
	 * @param  string $uri
	 */
	public function setURL($uri)
	{
		$this->uri = $uri;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set request method
	 * 
	 * @access public
	 * @param string $method
	 */
	public function setMethod($method)
	{
		$this->method = $method;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set request headers
	 * 
	 * @access public
	 * @param array $header
	 */
	public function setHeader($header = array())
	{
		$this->header = $header;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set post body
	 * 
	 * @access public
	 * @param  mixed $body
	 */
	public function setBody($body = '')
	{
		if ( is_array($body) )
		{
			$postBody = array();
			foreach ( $body as $key => $val )
			{
				$postBody[] = rawurlencode($key) . '=' . rawurlencode($val);
			}
			$this->postBody = implode('&', $postBody);
		}
		else
		{
			$this->postBody = $body;
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get error message
	 * 
	 * @access public
	 * @return string
	 */
	public function getError()
	{
		return $this->_error;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send request
	 * 
	 * @access public
	 * @param  string $method
	 * @param  string $uri
	 * @param  array $header
	 * @param  string $postBody
	 * @return object
	 */
	public function request($method = FALSE, $uri = '', $header = array(), $postBody = '')
	{
		$method   = ( $method )            ? strtoupper($method) : $this->method;
		$uri      = ( ! empty($uri) )      ? $uri                : $this->uri;
		$header   = ( count($header) > 0 ) ? $header             : $this->header;
		$postBody = ( ! empty($postBody) ) ? $postBody           : $this->postBody;
		
		// Load driver
		$this->driver = ( extension_loaded('curl') )
		                  ? $this->loadDriver('Curl_http')
		                  : $this->loadDriver('Socket_http');
		
		$this->driver->configure(array(
			'req'            => $this->req,
			'connectTimeout' => $this->connectTimeout,
			'timeout'        => $this->timeout
		));
		
		// Send request
		return $this->driver->sendRequest($method, $uri, $header, $postBody);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send cURL request
	 * 
	 * @access protected
	 * @param  string $method
	 * @param  string $uri
	 * @param  array $header
	 * @param  string $postBody
	 * @return object
	 */
	protected function _curlRequest($method, $uri, $header, $postBody)
	{
		$handle = curl_init();
		curl_setopt_array(
				$handle,
				array(
					CURLOPT_USERAGENT      => $this->req->server('HTTP_USER_AGENT'),
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
					CURLOPT_TIMEOUT        => $this->timeout,
					CURLOPT_HTTPHEADER     => (count($header) > 0) ? $header : array('Except:'),
					CURLOPT_HEADER         => FALSE
				)
		);
		
		if ( $method === 'POST' )
		{
			curl_setopt($handle, CURLOPT_POST, TRUE);
			if ( $postBody != '' )
			{
				curl_setopt($handle, CURLOPT_POSTFIELDS, $postBody);
			}
		}
		curl_setopt($handle, CURLOPT_URL, $uri);
		//curl_setopt($handle, CURLINFO_HEADER_OUT, 1);
		
		$resp = curl_exec($handle);
		if ( ! $resp )
		{
			$this->_set_error(curl_error($handle));
			$resp = FALSE;
		}
		;
		$response         = new stdClass;
		$response->status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
		$response->body   = $resp;
		curl_close($handle);
		
		if ( preg_match('/30[1237]/', (string)$response->status) )
		{
			$movedURI = preg_replace('|.+href="([^"]+)".+|is', '$1', $response->body);
			return $this->request($method, $movedURI, $header, $postBody);
		}
		
		return $response;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send socket request
	 * 
	 * @access protected
	 * @param  string $method
	 * @param  string $uri
	 * @param  array $header
	 * @param  string $postBody
	 * @return object
	 */
	protected function _fsockRequest($method, $uri, $header, $postBody)
	{
		// parse URLs
		$URL = parse_url($uri);
		
		$scheme = $URL['scheme'];
		$path   = $URL['path'];
		$host   = $URL['host'];
		$query  = (isset($URL['query'])) ? '?' . $URL['query'] : '';
		$port   = (isset($URL['port'])) ? $URL['port'] : ($scheme == 'https') ? 443 : 80;
		
		// build request-line-header
		$request = $method . ' ' . $path . $query . ' HTTP/1.1' . "\r\n"
						. 'Host: ' . $host . "\r\n"
						. 'User-Agent: ' . $this->req->server('HTTP_USER_AGENT') . "\r\n";
		
		if ( count($header) > 0 )
		{
			foreach ( $header as $head )
			{
				$request .= $head . "\r\n";
			}
		}
		
		if ( $method === 'POST' )
		{
			$fileUploads   = array();
			$contentLength = 0;
			if ( ! empty($postBody) )
			{
				if ( is_array($postBody) )
				{
					$post = array();
					foreach ( $postBody as $key => $param )
					{
						if ( substr($param, 0, 1) === '@' )
						{
							$file = array($key, substr($param, 1));
							if ( ! file_exists($file[1]) )
							{
								throw new Exception('POST Upload file is not exists.');
							}
							// push stack
							$fileUploads[] = $file;
							continue;
						}
						// create url-encoded array data
						$post[rawurlencode($key)] = rawurlencode($param);
					}
					// replace postBody
					$postBody = $post;
				}
				else
				{
					$contentLength = strlen($postBody);
				}
				
			}
			
			if ( count($fileUploads) === 0 )
			{
				$request .= "Content-Type: application/x-www-form-urlencoded\r\n";
				$request .= 'Content-Length: ' . $contentLength . "\r\n";
				$request .= "\r\n";
				if ( is_array($postBody) )
				{
					foreach ( $postBody as $key => $val )
					{
						$request .= $key . '=' . $val;
					}
				}
				else
				{
					$request .= $postBody;
				}
			}
			else
			{
				$boundary = md5('szBoundary' . microtime());
				$postdata = '';
				$request .= 'Content-Type: multipart/form-data; boundary=' . $boundary . "\r\n";
				
				// multipart section --------------------------------------- //
				// text field
				foreach ( $postBody as $key => $post )
				{
					// note key:post is already encoded.
					$postdata .= '--' . $boundary . "\r\n";
					$postdata .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n";
					$postdata .= 'Content-Type: text/plain' . "\r\n";
					$postdata .= "\r\n";
					$postdata .= $post . "\r\n";
				}
				
				// file upload
				$mime = Seezoo::$Importer->library('Mimetype');
				foreach ( $fileUploads as $upfile )
				{
					$postdata .= "--" . $boundary . "\r\n";
					$postdata .= 'Content-Disposition: form-data; name="' . rawurlencode($upfile[0]) . '"; filename="' . basename($upfile[1]) . '"' . "\r\n";
					$postdata .= 'Content-Type: ' . $mime->detect($upfile[1]) . "\r\n";
					$postdata .= "\r\n";
					$postdata .= file_get_contents($upfile[1]) . "\r\n";
				}
				$postdata .= '--' . $boundary ."--\r\n";
				
				$contentLength = ( function_exists('mb_strlen') )
				                     ? mb_strlen($postdata, 'iso-8859-1')
				                     : strlen($postdata);
				$request .= 'Content-Length: ' . $contentLength . "\r\n";
				$request .= "\r\n";
				$request .= $postdata;
			}
		}
		else 
		{
			$request .= "\r\n";
		}
		
		$fp = @fsockopen($host, $port, $errno, $errstr);
		
		if ( ! $fp )
		{
			$this->_set_error($errno . ': ' . $errstr);
			return FALSE;
		}
		
		// send request
		// If file upload request, requestdata maybe too long.
		// So, we try to loop request until request is send all.
		$written = 0;
		for ( ; $written < strlen($request); $written += $fwrite )
		{
			$fwrite = fwrite($fp, substr($request, $written));
			if ( $fwrite === FALSE )
			{
				throw new Exception('Socket send request failed.');
			}
		}
		
		// get response
		$resp = '';
		while ( ! feof($fp) )
		{
			$resp .= fgets($fp, 4096);
		}
		fclose($fp);

		// split header
		$exp = explode("\r\n\r\n", $resp, 2);
		
		if ( count($exp) < 2 )
		{
			$body   = FALSE;
			$status = FALSE;
			$this->_set_error('Nothing Response Body.');
		}
		else 
		{
			
			// parse response code
			$status = preg_replace('#HTTP/[0-9\.]+\s([0-9]+)\s#u', '$1', $exp[0]);
			$body   = implode("\r\n\r\n", array_slice($exp, 1));
			
			if ( preg_match('/30[1237]/', (string)$response->status) )
			{
				$movedURI = preg_replace('|.+href="([^"]+)".+|is', '$1', $body);
				return $this->request($method, $movedURI, $header, $postBody);
			}
		}
		
		$response = new stdClass;
		$response->status = (int)$status;
		$response->body   = $body;
		
		return $response;
	}
	
	protected function _set_error($message)
	{
		$this->_error = $message;
	}
}
