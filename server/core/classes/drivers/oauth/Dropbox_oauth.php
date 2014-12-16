<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Dropbox-Oauth driver
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
 
class SZ_Dropbox_oauth extends SZ_Oauth_driver
{
	// Base uri constants
	const BASE_URL     = 'https://www.dropbox.com/1/';
	const API_URL      = 'https://api.dropbox.com/1/';
	const CONTENT_URL  = 'https://api-content.dropbox.com/1/';
	const MAX_FILESIZE = 157286400;
	
	protected $authName = 'dropbox';
	protected $_root    = 'sandbox';
	
	
	public function __construct()
	{
		parent::__construct();
		$this->configure(array(
			'request_token_uri' => self::API_URL  . 'oauth/request_token',
			'authorize_uri'     => self::BASE_URL . 'oauth/authorize',
			'access_token_uri'  => self::API_URL  . 'oauth/access_token'
		));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Authneticate request
	 * 
	 * @access public
	 * @param  string $oauth_token
	 * @param  string $oauth_token_secret
	 * @param  array $ext_params
	 * @return mixed
	 */
	public function auth($oauth_token = null, $oauth_token_secret = null, $ext_params = array())
	{
		if ( $this->consumer_key === '' || $this->consumer_secret === '' )
		{
			$this->_setError('undefined consumer_key or consumer_secret.');
			return FALSE;
		}
		else if ( $oauth_token && $oauth_token_secret )
		{
			$this->requestTokens['oauth_token']        = $oauth_token;
			$this->requestTokens['oauth_token_secret'] = $oauth_token_secret;
			$this->requestTokens['authorized']         = TRUE;
			return TRUE;
		}

		if ( ! $this->isAuthorized() && $this->_isCallbackAuth() === TRUE )
		{
			return $this->callbackAuth($ext_params);
		}

		// build base string and paramter query
		$this->queryString = $this->_buildParameter($this->request_token_uri, $ext_params,  TRUE, TRUE);
		
		if ( ! $this->queryString )
		{
			return FALSE;
		}
		
		// do request
		$resp = $this->http->request(
			'POST',
			$this->request_token_uri,
			array('Authorization: OAuth ' . implode(', ', $this->queryString))
		);
		
		if ( ! $resp->body )
		{
			$this->_setError('OAuth Request Failed.');
			return FALSE;
		}
		else if ( $resp->status !== 200 )
		{
			$this->_setError($resp->status . ':' . $resp->body);
			return FALSE;
		}
		
		// If response exists, parse to array
		parse_str($resp->body, $this->requestTokens);
		
		// save tokens
		$this->_saveToken();
		
		// redirect to request tokens URI.
		$redirectURI = rtrim($this->authorize_uri, '?')
		               . '?oauth_token=' . $this->get('oauth_token')
		               . '&oauth_callback=' . rawurlencode($this->callback_url);
		
		Seezoo::$Response->forceRedirect($redirectURI);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check is callback-auth
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function _isCallbackAuth()
	{
		$request = Seezoo::getRequest();
		
		$q = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : FALSE;
		
		if ( ! ($q = $request->server('QUERY_STRNG')) )
		{
			return FALSE;
		}
		
		parse_str($q, $get);
		
		return (isset($get['oauth_token']) && isset($get['uid']))
		        ? TRUE
		        : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get user's account info
	 * 
	 * @access public
	 * @return mixed
	 */
	public function getUser()
	{
		if ( ! $this->isAuthorized() )
		{
			$this->_setError('Not authorized yet.');
			return FALSE;
		}
		
		$uri = self::API_URL . 'account/info';
		$headers = $this->_buildParameter(
			$uri,
			array(
				'oauth_token'        => $this->get('oauth_token'),
				'oauth_token_secret' => $this->get('oauth_token_secret')
			),
			FALSE,
			TRUE
		);
		$header = array(
			'Authorization: OAuth ' . implode(', ', $headers)
		);
		
		$response = $this->http->request('GET', $uri, $header);
		if ( $response->status !== 200 )
		{
			$this->_setError($response->status . ': ' . $response->body);
			return FALSE;
		}
		
		return json_decode($response->body);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get registed files from path
	 * 
	 * @access public
	 * @param  string $path
	 * @param  bool $download
	 */
	public function getFiles($path = null, $download = FALSE)
	{
		if ( ! $this->isAuthorized() )
		{
			$this->_setError('Not authorized yet.');
			return FALSE;
		}
		
		$file = $this->_prepFileName($path);
		$uri = self::CONTENT_URL . 'files/' . $this->_root . '/' . $this->_prepFileName($file);
		$headers = $this->_buildParameter(
			$uri,
			array(
				'oauth_token'        => $this->get('oauth_token'),
				'oauth_token_secret' => $this->get('oauth_token_secret')
			),
			FALSE,
			TRUE
		);
		$header = array(
			'Authorization: OAuth ' . implode(', ', $headers)
		);
		
		$response = $this->http->request('GET', $uri, $header);
		if ( $response->status !== 200 )
		{
			$this->_setError($response->status . ': ' . $response->body);
			return FALSE;
		}
		
		$ret = json_decode($response->body);
		if ( $download === FALSE )
		{
			return $ret;
		}
		
		Seezoo::$Response->download($ret->body, basename($file), TRUE);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add file to Dropbox
	 * 
	 * @access public
	 * @param  string $filePath
	 * @param  string $fileName
	 * @param  string $destPath
	 * @param  bool $overwrite
	 * @return mixed
	 */
	public function addFile($filePath, $fileName = '', $destPath = '', $overWrite = TRUE)
	{
		if ( ! file_exists($filePath) )
		{
			$this->_setError('Upload file is not exists on Dropbox Oauth.');
			return FALSE;
		}
		
		if ( filesize($filePath) > self::MAX_FILESIZE )
		{
			$this->_setError('Dropbox API uploads pemitted less than 150MB file.');
			return FALSE; 
		}
		
		$fileName = ( ! empty($fileName) ) ? $this->_prepFileName($fileName) : basename($filePath);
		$uri      = self::CONTENT_URL . 'files/' . $this->_root . '/' . $fileName;
		$posts    = array(
			'filename'  => $fileName,
			'file'      => $fileName,
			'overwrite' => (int)$overWrite
		);
		$headers = $this->_buildParameter(
			$uri,
			array_merge(
				array(
					'oauth_token'        => $this->get('oauth_token'),
					'oauth_token_secret' => $this->get('oauth_token_secret')
				),
				$posts
			),
			FALSE,
			TRUE
		);
		
		$authHeader = array();
		$post       = array();
		$ignores    = array('filename', 'file', 'overwrite');
		foreach ( $headers as $val )
		{
			$exp = explode('=', $val);
			if ( ! in_array(reset($exp), $ignores) )
			{
				$authHeader[] = $val;
			}
		}
		
		$post['filename']  = $fileName;
		$post['file']      = '@' . $filePath;
		$post['overwrite'] = (int)$overWrite;
		
		// TODO : continue implement base string override
		
		$response = $this->http->request(
			'POST',
			$uri,
			array('Authorization: OAuth ' . implode(', ', $headers)),
			$post
		);
		if ( $response->status !== 200 )
		{
			$this->_setError($response->status . ': ' . $response->body);
			return FALSE;
		}
		
		return json_decode($response->body);
		
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Prepare the filename
	 * 
	 * @access protected
	 * @param  string $file
	 * @return string
	 */
	protected function _prepFileName($file)
	{
		return preg_replace('#/+#', '/', trim($file, '/'));
	}
}
