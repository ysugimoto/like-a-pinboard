<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Github-Oauth driver
 * 
 * @package  Seezoo-Framework
 * @category drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */
class SZ_Github_oauth extends SZ_Oauth_driver
{
	const REQUEST_BASE      = 'https://github.com';
	const AUTHORIZE_PATH    = '/login/oauth/authorize';
	const ACCESS_TOKEN_PATH = '/login/oauth/access_token';
	const USER_ACCOUNT_URI  = 'https://api.github.com/user';
	
	protected $authName     = 'github';
	
	protected $client_id;
	protected $application_secret;
	protected $scope;
	
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
			        . '&redirect_uri=' . rawurlencode($this->callback_url);
			
			if ( $this->scope )
			{
				$uri .= '&scope=' . $this->scope;
			}
			
			Seezoo::$Response->forceRedirect($uri);
		}
		else 
		{
			$uri = $this->access_token_uri
			        . '?client_id='     . $this->client_id
			        . '&redirect_uri='  . rawurlencode($this->callback_url)
			        . '&client_secret=' . $this->application_secret
			        . '&code='          . $request->get('code') ;
			
			$resp = $this->http->request('POST', $uri);
			if ( $resp->status !== 200 )
			{
				$this->_setError('OAuth Request Faild.');
				return FALSE;
			}
			
			parse_str($resp->body, $this->requestTokens);
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
		
		$uri  = self::USER_ACCOUNT_URI .'?access_token=' . $this->get('access_token');
		
		$resp = $this->http->request('GET', $uri);
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
