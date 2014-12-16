<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Google-Oauth driver
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Google_oauth extends SZ_Oauth_driver
{
	const REQUEST_BASE        = 'https://accounts.google.com/o/oauth2';
	const AUTHORIZE_PATH      = '/auth';
	const ACCESS_TOKEN_PATH   = '/token';
	const ACCOUNT_REQUEST_URI = 'https://www.googleapis.com/oauth2/v1/userinfo';

	protected $authName       = 'google';
	protected $scope          = 'https://www.googleapis.com/auth/userinfo.profile';
	protected $client_id;
	protected $clientSecret;
	
	public function __construct()
	{
		parent::__construct();
		$this->configure(array(
			'authorize_uri'    => self::REQUEST_BASE . self::AUTHORIZE_PATH,
			'access_token_uri' => self::REQUEST_BASE . self::ACCESS_TOKEN_PATH
		));
	}
	
	public function auth2($code = '')
	{
		$request = Seezoo::getRequest();
		// Did you authrize?
		if ( empty($code) && ! $request->get('code') )
		{
			if ( ! $this->client_id )
			{
				$this->_setError('Client ID is not found.');
				return FALSE;
			}
			else if ( ! $this->callback_url )
			{
				$this->_setError('Callback URI is not found.');
				return FALSE;
			}
			// not authorized
			$uri = $this->authorize_uri
			        . '?client_id='    . $this->client_id
			        . '&redirect_uri=' . rawurlencode($this->callback_url)
			        . '&scope='        . rawurlencode($this->scope)
			        . '&response_type=code'; 

			Seezoo::$Response->forceRedirect($uri);
		}
		else 
		{
			$uri  = $this->access_token_uri;
			$post =   'code='           . $request->get('code')
			        . '&client_id='     . $this->client_id
			        . '&client_secret=' . $this->client_secret
			        . '&redirect_uri='  . rawurlencode($this->callback_url)
			        . '&grant_type=authorization_code';
			
			$resp = $this->http>request('POST', $uri, array(), $post);
	
			if ( $resp->status !== 200 )
			{
				$this->_setError('OAuth Request Faild.');
				return FALSE;
			}
			
			$this->requestTokens = json_decode($resp->body);
			$this->requestTokens = object_to_array($this->requestTokens);
			$this->requestTokens['authorized'] = TRUE;
			$this->_saveToken();
			return TRUE;
			
		}
	}
	
	public function getUser()
	{
		if ( ! $this->isAuthorized() )
		{
			$this->_setError('Unauthorized on getUser method.');
			return FALSE;
		}

		$uri  = self::ACCOUNT_REQUEST_URI . '?access_token=' . $this->get('access_token');
		$resp = $this->http->request('GET', $uri);
		if ( $resp->status !== 200 )
		{
			$this->_setError('Request Faild.');
			return FALSE;
		}
		$data = json_decode($resp->body);
		return $data;
	}
}
