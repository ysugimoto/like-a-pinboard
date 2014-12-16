<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Ameba-Oauth driver
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Ameba_oauth extends SZ_Oauth_driver
{
	const REQUEST_BASE      = 'https://oauth.ameba.jp';
	const AUTHORIZE_PATH    = '/authorize';
	const ACCESS_TOKEN_PATH = '/token';
	const USER_PROFILE_URL  = 'http://platform.ameba.jp/api/profile/user/getLoginUserProfile/json';
	
	protected $auth_name    = 'ameba';
	protected $client_id;
	protected $client_secret;
	protected $scope        = 'w_ameba';
	
	public function __construct()
	{
		parent::__construct();
		$this->initialize(array(
			'authorize_uri'    => self::REQUEST_BASE . self::AUTHORIZE_PATH,
			'access_token_uri' => self::REQUEST_BASE . self::ACCESS_TOKEN_PATH
		));
	}
	
	public function auth2($code = '')
	{
		$request = Seezoo::getRequest();
		
		// not authorized
		if ( empty($code) && ! $request->get('code') )
		{
			if ( ! $this->client_id )
			{
				$this->_setError('Apprication ID is not found.');
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
			        . '&response_type=code'
			        . '&display=pc';
			
			if ( $this->scope )
			{
				$uri .= '&scope=' . $this->scope;
			}
			
			Seezoo::$Response->redirectForce($uri);
		}
		else 
		{
			$postdata = 
			          'client_id='      . $this->client_id
			        . '&client_secret=' . $this->client_secret
			        . '&code='          . $request->get('code')
			        . '&grant_type=authorization_code';
			
			$resp = $this->http->request('POST', $this->access_token_uri, array(), $postdata);
			if ( $resp->status !== 200 )
			{
				$this->_setError('OAuth Request Faild.');
				return FALSE;
			}
			
			$json = json_decode($resp->body);
			$this->requestTokens['refresh_token'] = $json->refresh_token;
			$this->requestTokens['expires_in']    = $json->expires_in;
			$this->requestTokens['access_token']  = $json->access_token;
			$this->requestTokens['authorized'] = TRUE;
			$this->_saveToken();
			return TRUE;
			
		}
	}
	
	public function getUser()
	{
		if ( ! $this->isAuthorized() )
		{
			$this->_setError('Unauthorized on get_member_data method.');
			return FALSE;
		}
		
		$header = array(
			'Authorization: OAuth ' . $this->get('access_token')
		);
		
		$resp = $this->request(self::USER_PROFILE_URL, $header);
		if ( $resp->status !== 200 )
		{
			$this->_setError('Request Faild.');
			return FALSE;
		}

		$data = json_decode($resp->body);
		if ( isset($data->error) && $data->error )
		{
			$this->requestTokens['authorized'] = FALSE;
			$this->_saveToken();
			$this->auth2();
			return FALSE;
		}
		return $data;
	}
}
