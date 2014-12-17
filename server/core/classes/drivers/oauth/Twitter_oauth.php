<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Twitter-Oauth driver
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Twitter_oauth extends SZ_Oauth_driver
{
	// Base url constants
	const TWITTER_BASE_URL      = 'http://twitter.com';
	const TWITTER_SEARCH_URL    = 'http://search.twitter.com/search.json';
	const REQUEST_BASE          = 'https://api.twitter.com';
	const REQUEST_TOKEN_PATH    = '/oauth/request_token';
	const AUTHORIZE_PATH        = '/oauth/authorize';
	const AUTHENTICATE_PATH     = '/oauth/authenticate';
	const ACCESS_TOKEN_PATH     = '/oauth/access_token';
	const ACCOUNT_VERIFY_PATH   = '/1.1/account/verify_credentials.json';
	const UPDATE_PATH           = '/1.1/statuses/update';
	const RETWEET_PATH          = '/1.1/statuses/retweet';
	const HOME_TIMELINE_PATH    = '/1.1/statuses/home_timeline';
	const FRIENDS_TIMELINE_PATH = '/1.1/statuses/friends_timeline';
	
	protected $authName         = 'twitter';
	
	
	public function __construct()
	{
		parent::__construct();
		$this->configure(array(
			'request_token_uri' => self::REQUEST_BASE . self::REQUEST_TOKEN_PATH,
			'authorize_uri'     => self::REQUEST_BASE . self::AUTHORIZE_PATH,
			'access_token_uri'  => self::REQUEST_BASE . self::ACCESS_TOKEN_PATH
		));
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Get userdata
	 * 
	 * @access public
	 * @param  string $user
	 * @return mixed
	 */
	public function getUser($user = '')
	{
		$uri = ( ! empty($user) )
			     ? self::REQUEST_BASE . '/users/show/' . $user . '.json'
			     : self::REQUEST_BASE . self::ACCOUNT_VERIFY_PATH;
			
		$header = array();
		if ( $this->isAuthorized() )
		{
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
		}
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
	 * Get timeline
	 * 
	 * @access public
	 * @param  bool $homeTimeline
	 * @param  int $limit
	 * @param int $page
	 * @return mixed
	 */
	public function getTimeline($homeTimeline = TRUE, $limit = 20, $page = 0)
	{
		if ( $this->isAuthorized() )
		{
			$this->_setError('Not authorized yet.');
			return FALSE;
		}
		
		$uri = ( $homeTimeline )
		         ? self::REQUEST_BASE . self::HOME_TIMELINE_PATH
		         : self::REQUEST_BASE . self::FRIENDS_TIMELINE_PATH;
		$uri .= '?count=' . $limit . '&page=' . $page;
		
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
		
		$response = $this->http->request('GET', $uri . '.json', $header);
		if ( $response->status !== 200 )
		{
			$this->_setError($response->status . ': ' . $response->body);
			return FALSE;
		}
		
		return json_decode($response->body);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send tweet
	 * 
	 * @access public
	 * @param  string $message
	 * @return mixed
	 */
	public function tweet($message)
	{
		if ( ! $this->isAuthorized() )
		{
			$this->_setError('Not authorized yet.');
			return FALSE;
		}
		
		$uri    = self::REQUEST_BASE . self::UPDATE_PATH . '.json';
		$length = ( function_exists('mb_strlen') )
		            ? mb_strlen($message)
		            : strlen($message);
		
		// Validate 140 bytes this library before sending tweet
		if ( $length > 140 )
		{
			$this->_setError('Tweet message must be less than 140 bytes.');
			return FALSE;
		}
		else if ( $length === 0 )
		{
			$this->_setError('Tweet message must not be empty.');
			return FALSE;
		}
		
		$this->_request_method = 'POST';
		
		$headers = $this->_buildParameter(
			$uri,
			array(
				'status'             => $message,
				'oauth_token'        => $this->get('oauth_token'),
				'oauth_token_secret' => $this->get('oauth_token_secret')
			),
			FALSE,
			TRUE
		);
		
		$authHeader = array();
		foreach ( $headers as $auth )
		{
			if ( ! preg_match('/^status/u', $auth) )
			{
				$authHeader[] = $auth;
			}
		}

		$response = $this->http->request(
										'POST',
										$uri,
										 array('Authorization: OAuth ' . implode(', ', $authHeader)),
										'status=' . rawurlencode($message)
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
	 * Send retweet
	 * 
	 * @access public
	 * @param  int $statusID
	 * @RETURN mixed
	 * 
	 */
	public function retweet($statusID = 0)
	{
		if ( ! $this->isAuthorized() )
		{
			$this->_setError('Not authorized yet.');
			return FALSE;
		}
		else if ( (int)$statusID === 0 )
		{
			$this->_setError('Retweet target ID is not specified');
			return FALSE;
		}
		
		$this->_request_method = 'POST';
		
		$statusID = (string)$statusID;
		$uri      = self::REQUEST_BASE . self::RETWEET_PATH . '/' . $statusID . '.json';
		$headers  = $this->_buildParameter(
			$uri,
			array(
				'oauth_token'        => $this->get('oauth_token'),
				'oauth_token_secret' => $this->get('oauth_token_secret')
			),
			FALSE,
			TRUE
		);
		
		$authHeader = array();
		foreach ( $headers as $auth )
		{
			if ( ! preg_match('/^id/u', $auth) )
			{
				$authHeader[] = $auth;
			}
		}
		
		$response = $this->http->request(
			'POST',
			$uri,
			array('Authorization: OAuth ' . implode(', ', $authHeader)),
			$header
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
	 * Search tweets
	 * 
	 * @access public
	 * @param  string $q
	 * @param  int $page
	 * @return mixed
	 */
	public function search($q, $page = 1)
	{
		$uri = self::TWITTER_SEARCH_URL . '?q=' . rawurlencode($q) . '&page=' . $page;
		$response = $this->http->request('GET', $uri);
		
		if ( $response->status !== 200 )
		{
			$this->_setError($response->status . ': ' . $response->body);
			return FALSE;
		}
		
		return json_decode($response->body);
	}
}
