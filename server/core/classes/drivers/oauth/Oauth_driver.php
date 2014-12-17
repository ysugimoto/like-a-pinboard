<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Oauth driver
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
 
class SZ_Oauth_driver
{
	/**
	 * Oauth parameters
	 */
	protected $authorize_uri;
	protected $request_token_uri;
	protected $access_token_uri;
	protected $callback_url;
	protected $consumer_key;
	protected $consumer_secret;
	protected $signature_method = 'HMAC-SHA1';
	protected $request_method   = 'GET';
	protected $version          = '1.0';
	protected $userAgent        = 'SZFW.Oauth-client';
	protected $timeout          = 30;
	protected $connectTimeout   = 30;
	
	protected $authName;
	protected $error;
	protected $requestTokens = array();
	protected $queryString;
	protected $oauthAccessToken = FALSE;
	protected $oauthAccessTokenSecret = FALSE;
	
	protected $http;
	protected $session;
	
	
	public function __construct()
	{
		$this->http    = Seezoo::$Importer->library('Http');
		$this->session = Seezoo::$Importer->library('session');
		$this->_getTokens();
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Condigure
	 * 
	 * @access public
	 * @param  array $conf
	 */
	public function configure($conf = array())
	{
		foreach ( $conf as $key => $val )
		{
			$this->{$key} = $val;
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set callback URI
	 * 
	 * @access public
	 * @param  string $callback
	 */
	public function setCallback($callback = '')
	{
		$this->callback_url = $callback;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get an error
	 * 
	 * @access public
	 * @return string
	 */
	public function getError()
	{
		return $this->error;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Authenticate request
	 * 
	 * @access public
	 * @param  mixed $oauth_token
	 * @param  mixed $oauth_token_secret
	 * @param  array $ext_param
	 * @return bool
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

		if ( ! $this->get('authorized') && $this->_isCallbackAuth() === TRUE )
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
		                            $this->request_method,
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
		$redirectURI = rtrim($this->authorize_uri, '?') . '?oauth_token=' . $this->requestTokens['oauth_token'];
		Seezoo::$Response->forceRedirect($redirectURI);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Oauth requested callback
	 * 
	 * @access protected
	 * @param  array $param
	 * @return bool
	 */
	protected function callbackAuth($param = array())
	{
		$q = ( isset($_SERVER['QUERY_STRING']) ) ? $_SERVER['QUERY_STRING'] : FALSE;
		if ( ! $q )
		{
			return FALSE;
		}
		parse_str($q, $gets);
		
		// set callback parameters
		$verify = ( isset($gets['oauth_verifier']) ) ? $gets['oauth_verifier'] : '';
		$token  = ( isset($gets['oauth_token']) )    ? $gets['oauth_token']    : '';

		// Does oauth token match?
		if ( $token !== $this->get('oauth_token') )
		{
            $this->_setError('Callback: oauth_token not matched.');
			return FALSE;
		}
		
		// build query paramters to get Access token
		$data = array(
						'oauth_token'        => $token,
						'oauth_verifier'     => $verify,
						'oauth_token_secret' => $this->get('oauth_token_secret')
							);
		$this->queryString = $this->_buildParameter($this->access_token_uri, $data, FALSE, TRUE);
	
		// get Access token!
		$resp = $this->http->request(
		                            $this->request_method,
		                            $this->access_token_uri,
		                            array('Authorization: OAuth ' . implode(', ', $this->queryString))
									);
		
		if ( ! $resp->body )
		{
			$this->_setError('Access Token Check Faild.');
			return FALSE;
		}
		else if ( $resp->status !== 200 )
		{
			$this->_setError($resp->status . ':' . $resp->body);
			return FALSE;
		}
		
		parse_str($resp->body, $this->requestTokens);
		
		$this->requestTokens['authorized'] = TRUE;
		// save Access tokens
		$this->_saveToken();

		return TRUE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check request is already authorized?
	 * 
	 * @access public
	 * @return bool
	 */
	public function isAuthorized()
	{
		$data = $this->requestTokens;
		return ( isset($data['authorized']) && $data['authorized'] === TRUE ) ? TRUE : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get Oauth process parameters
	 * 
	 * @access public
	 * @param  string $key
	 * @return mixed
	 */
	public function get($key)
	{
		return ( isset($this->requestTokens[$key]) ) ? $this->requestTokens[$key] : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get all Oauth process parameters
	 * 
	 * @access public
	 * @return array
	 */
	public function getAll()
	{
		return $this->requestTokens;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode RFC-3986 string
	 * 
	 * @access protected
	 * @param  string $param
	 * @return string
	 */
	protected function _encodeRFC3986($param)
	{
		return str_replace(array('+', '%7E'), array(' ', '~'), rawurlencode($param));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Build Oauth request Parameter
	 * 
	 * @access protected
	 * @param  string $uri
	 * @param  array $arr
	 * @param  string $callback
	 * @param  bool $return_array
	 * @return mixed
	 */
	protected function _buildParameter($uri, $arr = array(), $callback = TRUE, $return_array = FALSE)
	{
		// Does consumer key exists?
		if ( $this->consumer_key == '' )
		{
			$this->_setError('undefined consumer key.');
			return FALSE;
		}
		
		// If secret key exists, pick paramter
		if ( isset($arr['oauth_token_secret']) )
		{
			$secret = $arr['oauth_token_secret'];
			unset($arr['oauth_token_secret']);
		}
		else
		{
			$secret = '';
		}
		
		// base paramters create
		$parameters = array(
			'oauth_consumer_key'     => $this->consumer_key,
			'oauth_signature_method' => $this->signature_method,
			'oauth_timestamp'        => time(),
			'oauth_nonce'            => md5(uniqid(mt_rand(), TRUE)),
			'oauth_version'          => $this->version,
		);
		
		// merge additional parameters
		// TODO : use array_merge function?
		foreach ( $arr as $key => $val )
		{
			$parameters[$key] = $val;
		}
		
		// If need callback, add paramter
		if ( $callback === TRUE )
		{
			if ( $this->callback_url === '' )
			{
				$this->_setError('Undefined Callback URI.');
				return FALSE;
			}
			$parameters['oauth_callback'] = $this->callback_url;
		}
		
		// encode RFC3986
		$params = array_map(array($this, '_encodeRFC3986'), $parameters);

		// sort key from strnatcmp
		uksort($params, 'strnatcmp');

		// build oauth signature
		// encrypt HMAC-SHA1
		$signature = hash_hmac(
								'sha1',
								$this->_buildBaseString($uri, $params),
								$this->_generateKey($secret),
								TRUE
							);

		$parameters['oauth_signature'] = base64_encode($signature);
		$query_string = array();
		// format query parameter from encoded array
		foreach ( array_map(array($this, '_encodeRFC3986'), $parameters) as $key => $val )
		{
			$query_string[] = $key . '=' . $val;
		}
		
		return ( $return_array )
		         ? array_map(array($this, '_quote'), $query_string)
		         : implode('&', $query_string);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Build Oauth base-string
	 * 
	 * @access protected
	 * @param  string $uri
	 * @param  array $params_array
	 * @return string
	 */
	protected function _buildBaseString($uri, $params_array = array())
	{
		$p = array();
		foreach ( $params_array as $key => $val )
		{
			$p[] = $key . '=' . $val;
		}
		
		$ret = array_map(
						array($this, '_encodeRFC3986'),
						array(
							$this->request_method,
							$uri,
							implode('&', $p)
						)
					);
					
		return implode('&', $ret);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Generate Key-string
	 * 
	 * @access protected
	 * @param  string $secret
	 * @return string
	 */
	protected function _generateKey($secret = '')
	{
		$key = array_map(
						array($this, '_encodeRFC3986'),
						array(
							$this->consumer_secret,
							($secret) ? $secret : ''
						)
					);
					
		return implode('&', $key);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Set an error string
	 * 
	 * @access protected
	 * @param  string $message
	 */
	protected function _setError($message)
	{
		$this->error = $message;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Save requested response to session
	 * 
	 * @access protected
	 */
	protected function _saveToken()
	{
		$this->session->set($this->authName, $this->requestTokens);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get saved tokens
	 * 
	 * @access protected
	 * @return mixed
	 */
	protected function _getTokens()
	{
		$dat = $this->session->get($this->authName);
		if ( $dat === FALSE )
		{
			$dat = array();
		}
		
		if ( count($dat) === 0 )
		{
			return FALSE;
		}
		$this->requestTokens = $dat;
		
		if ( ! isset($dat['authorized']) )
		{
			return $dat;
		}
		return FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Check is need to callback-auth?
	 * 
	 * @access protected
	 * @return bool
	 */
	protected function _isCallbackAuth()
	{
		$q = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : FALSE;
		
		if ( ! $q )
		{
			return FALSE;
		}
		
		parse_str($q, $get);
		
		return (isset($get['oauth_token']) && isset($get['oauth_verifier']))
		        ? TRUE
		        : FALSE;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Quote string
	 * 
	 * @access protected
	 * @param  string $str
	 * @return string
	 */
	protected function _quote($str)
	{
		return preg_replace('/(.+)=(.+)?/', '$1="$2"', $str);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Destroy tokens
	 * 
	 * @access public
	 */
	public function unsetToken()
	{
		$this->requestTokens = array();
		$this->session->remove($this->authName);
	}
}
