<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * CURL http request driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

class SZ_Curl_http extends SZ_Http_driver
{
	/**
	 * Send cURL request
	 * 
	 * @access public
	 * @param  string $method
	 * @param  string $uri
	 * @param  array $header
	 * @param  string $postBody
	 * @return object
	 */
	public function sendRequest($method, $uri, $header, $postBody)
	{
		$handle = curl_init();
		curl_setopt_array(
				$handle,
				array(
					CURLOPT_URL            => $uri,
					CURLOPT_USERAGENT      => $this->req->server('HTTP_USER_AGENT'),
					CURLOPT_RETURNTRANSFER => TRUE,
					CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
					CURLOPT_TIMEOUT        => $this->timeout,
					CURLOPT_HTTPHEADER     => (count($header) > 0) ? $header : array('Except:'),
					CURLOPT_HEADER         => FALSE
				)
		);
		
		if ( preg_match('#\Ahttps://#u', $uri) )
		{
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE);
		}
		
		if ( $method === 'POST' )
		{
			curl_setopt($handle, CURLOPT_POST, TRUE);
			if ( $postBody != '' )
			{
				curl_setopt($handle, CURLOPT_POSTFIELDS, $postBody);
			}
		}
		
		$resp = curl_exec($handle);
		if ( ! $resp )
		{
			throw new SeezooException(curl_error($handle));
		}
		;
		$response         = new stdClass;
		$response->status = (int)curl_getinfo($handle, CURLINFO_HTTP_CODE);
		$response->body   = $resp;
		curl_close($handle);
		
		if ( preg_match('/30[1237]/', (string)$response->status) )
		{
			$movedURI = preg_replace('|.+href="([^"]+)".+|is', '$1', $response->body);
			return $this->sendRequest($method, $movedURI, $header, $postBody);
		}
		
		return $response;
	}
}
