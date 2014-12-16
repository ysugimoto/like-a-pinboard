<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Socket http request driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Socket_http
{
	/**
	 * Request data
	 * @var array ( string only )
	 */
	protected $request = array();
	
	/**
	 * Carrige return
	 * @var string
	 */
	protected $CRLF    = "\r\n";
	
	/**
	 * Send cURL request
	 * 
	 * @access protected
	 * @param  string $method
	 * @param  string $uri
	 * @param  array $header
	 * @param  string $postBody
	 * @return stdClass
	 * @throws SeezooException
	 */
	public function sendRequest($method, $uri, $header, $postBody)
	{
		// Initialize
		$this->request = array();
		
		// Build base request headers string
		list($host, $port) = $this->_buildBaseRequestHeader($uri, $header);
		
		// Is POST request method?
		if ( $method === 'POST' )
		{
			$this->_buildPostRequestHeader($postBody);
		}
		else
		{
			$this->request[] = $this->CRLF;
		}
		
		// Send request
		$resp = $this->sendSocket($host, $port);
		// split header
		$exp  = explode("\r\n\r\n", $resp, 2);
		
		// Does response body exists?
		if ( count($exp) < 2 )
		{
			throw new SeezooException('Nothing Response Body.');
		}
		
		// parse response code
		$status = preg_replace('#HTTP/[0-9\.]+\s([0-9]+)\s#u', '$1', $exp[0]);
		$body   = implode("\r\n\r\n", array_slice($exp, 1));
		
		// Do we need redirect?
		if ( preg_match('/30[1237]/', $status) )
		{
			$movedURI = preg_replace('|.+href="([^"]+)".+|is', '$1', $body);
			return $this->sendRequest($method, $movedURI, $header, $postBody);
		}
		
		$response         = new stdClass;
		$response->status = (int)$status;
		$response->body   = $body;
		
		return $response;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Build base request header
	 * 
	 * @access protected
	 * @param  string $uri
	 * @param  array $header
	 * @return array ( contains host, port )
	 */
	protected function _buildBaseRequestHeader($uri, $header)
	{
		// parse URLs
		$URL    = parse_url($uri);
		
		$scheme = $URL['scheme'];
		$path   = $URL['path'];
		$host   = $URL['host'];
		$query  = ( isset($URL['query']) ) ? '?' . $URL['query'] : '';
		$port   = ( isset($URL['port'])  ) ? $URL['port'] : 80;
		
		// Does request uri is SSL?
		if ( $scheme === 'https' )
		{
			$port = 443;
		}
		
		// build request-line-header
		$this->request[] = $method . ' ' . $path . $query . ' HTTP/1.1';
		$this->request[] = 'Host: ' . $host;
		$this->request[] = 'User-Agent: ' . $this->req->server('HTTP_USER_AGENT');
		
		// Does extra header exists?
		if ( count($header) > 0 )
		{
			foreach ( $header as $head )
			{
				$this->request[] = rtrim($head, $this->CRLF);
			}
		}
		
		return array($host, $port);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Build POST request header
	 * 
	 * @access protected
	 * @param  mixed $postBody
	 * @return void
	 */
	protected function _buildPostRequestHeader($postBody)
	{
		$fileUploads   = array();
		$contentLength = 0;
		if ( ! empty($postBody) )
		{
			if ( is_array($postBody) )
			{
				list($postBody, $contentLength) = $this->_parsePostBody($postBody, $fileUploads);
			}
			else
			{
				$contentLength = strlen($postBody);
				$postBody      = array_filter(explode('&', $postBody));
			}
		}
		
		// If upload file not exists, simple add post request headers
		if ( count($fileUploads) === 0 )
		{
			$this->request[] = "Content-Type: application/x-www-form-urlencoded";
			$this->request[] = 'Content-Length: ' . $contentLength;
			$this->request[] = $this->CRLF;
			$this->request[] = implode('&', $postBody);
		}
		// Else, create boundary header for file upload
		else
		{
			$this->_createBoundary($postBody, $fileUploads);
		}
	
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse and make POST request body
	 * 
	 * @access protected
	 * @param  mixed $postBody
	 * @param  array $fileUploads ( reference )
	 * @return array ( contains array post, int $size )
	 * @throws FileNotFoundException
	 */
	protected function _parsePostBody($postBody, &$fileUploads)
	{
		$post = array();
		$size = 0;
		foreach ( $postBody as $key => $param )
		{
			// Array parameters case
			if ( is_array($param) )
			{
				$key = rawurlencode($key) . '[]';
				foreach ( $param as $v )
				{
					$v = rawurlencode($v);
					$post[] = $key . '=' . $v;
				}
				$size += strlen($key . '=' . $v);
			}
			// File upload case
			else if ( substr($param, 0, 1) === '@' )
			{
				$file = array(rawurlencode($key), substr($param, 1));
				if ( ! file_exists($file[1]) )
				{
					throw new FileNotFoundException('POST Upload file: ' . $file[1] . ' is not found.');
				}
				// push stack
				$fileUploads[] = $file;
			}
			// string key-value case
			else
			{
				$key    = rawurlencode($key);
				$param  = rawurlencode($param);
				$post[] = $key . '=' . $param;
				$size  += strlen($key . '=' . $param);
			}
		}
		
		return array($post, $size);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Create multipart boundary string
	 * 
	 * @access protected
	 * @param  array $postBody
	 * @param  array $files
	 * @return void
	 */
	protected function _createBoundary($postBody, $files)
	{
		// create unique boundary
		$boundary = md5('szBoundary' . microtime());
		$mime     = Seezoo::$Importer->library('Mimetype');
		$postData = 'Content-Type: multipart/form-data; boundary=' . $boundary . $this->CRLF;
		
		// multipart section --------------------------------------- //
		// text field
		foreach ( $postBody as $post )
		{
			// note key:post is already encoded.
			$exp = explode('=', $post);
			$postData .= '--' . $boundary . $this->CRLF;
			$postData .= 'Content-Disposition: form-data; name="' . $exp[0] . '"' . $this->CRLF;
			$postData .= 'Content-Type: text/plain' . $this->CRLF;
			$postData .= $this->CRLF;
			$postData .= $exp[1];
			$postData .= $this->CRLF;
		}
		
		// file upload
		foreach ( $files as $file )
		{
			$postData .= "--" . $boundary . $this->CRLF;
			$postData .= 'Content-Disposition: form-data; name="' . $file[0] . '"; filename="' . basename($file[1]) . '"' . $this->CRLF;
			$postData .= 'Content-Type: ' . $mime->detect($file[1]) . $this->CRLF;
			$postData .= $this->CRLF;
			$postData .= file_get_contents($file[1]) . $this->CRLF;
		}
		$postData .= '--' . $boundary . '--' . $this->CRLF;
		
		$this->request[] = 'Content-Length: ' . strlen($postData);
		$this->request[] = $this->CRLF;
		$this->request[] = $postData;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Connect socket and send request
	 * 
	 * @access protected
	 * @param  string $host
	 * @param  int $port
	 * @return string
	 * @throws SeezooException
	 */
	protected function sendSocket($host, $port)
	{
		// Connection start
		$fp = @fsockopen($host, $port, $errno, $errstr);
		
		if ( ! $fp )
		{
			throw new SeezooException($errno . ': ' . $errstr);
		}
		
		// build request string
		$request = implode($this->CRLF, $this->request);
		
		// send request
		// If file upload request, requestdata maybe too long.
		// So, we try to loop request until request is sent all.
		$written = 0;
		for ( ; $written < strlen($request); $written += $fwrite )
		{
			$fwrite = fwrite($fp, substr($request, $written));
			if ( $fwrite === FALSE )
			{
				throw new SeezooException('Socket send request failed.');
			}
		}
		
		// get response
		$resp = '';
		while ( ! feof($fp) )
		{
			$resp .= fgets($fp, 4096);
		}
		fclose($fp);
		
		return $resp;
	}
}
