<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * XML-RPC Server driver
 * 
 * @package  Seezoo-Framework
 * @category Drivers
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

define('SZ_XMLRPC_ERROR_INVALID',    0x0001);
define('SZ_XMLRPC_ERROR_NOMETHOD',   0x0010);
define('SZ_XMLRPC_ERROR_BADREQUEST', 0x0100);
define('SZ_XMLRPC_SUCCESS',          0x1000);


class SZ_Xmlrpc_server
{
	/**
	 * Excutable method maps
	 * @var array
	 */
	protected $_methodMaps = array();
	
	
	/**
	 * Classname stack encoder,decoder
	 * @var string
	 */
	protected $_decoder;
	protected $_encoder;
	
	
	public function __construct($maps, $encoder, $decoder)
	{
		$this->_encoder = $encoder;
		$this->_decoder = $decoder;
		
		$this->addMethod($maps);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Add executable methos
	 * 
	 * @access public
	 * @param  mixed  $methodCall
	 * @param  string $mapController
	 * @param  string $execMode
	 */
	public function addMethod($methodCall, $mapController = '', $execMode = FALSE)
	{
		$mode = ( $execMode ) ? $execMode : SZ_MODE_MVC;
		if ( is_array($methodCall) )
		{
			foreach ( $methodCall as $method => $map )
			{
				$this->_methodMaps[$method] = array(str_replace('.',  '/', $map), $mode);
			}
		}
		else
		{
			$this->_methodMaps[$methodCall] = array(str_replace('.', '/', $mapController), $mode);
		}
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Server listen
	 * 
	 * @access public
	 */
	public function listen()
	{
		$requestData = $this->_parseXMLRequest();
		if ( $requestData->status !== SZ_XMLRPC_SUCCESS )
		{
			return $requestData->status;
		}
		else if ( ! array_key_exists($requestData->methodCall, $this->_methodMaps) )
		{
			return SZ_XMLRPC_ERROR_NOMETHOD;
		}
		
		list($pathInfo, $mode) = $this->_methodMaps[$requestData->methodCall];
		
		// set return value mode
		Seezoo::$outpuBufferMode = FALSE;
		$return = Seezoo::init($mode, $pathInfo, $requestData->params);
		
		$responseBody = $this->_buildResponseBody($return);
		$this->_sendResponseHeader(strlen($responseBody));
		
		// XML output
		echo $responseBody;
		exit;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Parse XML-RPC request
	 * 
	 * @access protected
	 * @return object
	 */
	protected function _parseXMLRequest()
	{
		$request = new stdClass;
		$request->status = TRUE;
		$request->params = array();
		
		// Get native input
		$post = trim(file_get_contents('php://input'));
		$DOM  = new DOMDocument('1.0', 'UTF-8');
		if ( empty($post) || $DOM->loadXML($post) === FALSE )
		{
			$request->status = SZ_XMLRPC_ERROR_BADREQUEST;
			return $request;
		}
		
		// Detect <methodCall>
		$methodNameElements = $DOM->getElementsByTagName('methodName');
		if ( $methodNameElements->length === 0 )
		{
			$request->status = SZ_XMLRPC_ERROR_NOMETHOD;
			return $request;
		}
		
		$methodNameElement   = $methodNameElements->item(0);
		$request->methodCall = $methodNameElement->nodeValue;
		
		// Parse Params
		$paramsElements = $DOM->getElementsByTagName('params');
		if ( $paramsElements->length > 0 )
		{
			$paramsElement = $paramsElements->item(0);
			foreach ( $paramsElement->childNodes as $paramElement )
			{
				$valueElement = $paramElement->firstChild;
				$type         = call_user_func(array($this->_decoder, 'detectType'), $valueElement);
				if ( $type === FALSE )
				{
					$request->status = SZ_XMLRPC_ERROR_INVALIDPARAM;
					return $request;
				}
				$decoder = new $this->_decoder($valueElement, $type);
				$request->params[] = $decoder->getValue();
			}
		}
		
		return $request;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send response headers
	 * 
	 * @access protected
	 * @param  int $contentLength
	 */
	protected function _sendResponseHeader($contentLength)
	{
		header('HTTP/1.1 200 OK', TRUE);
		header('Connection: close');
		header('Content-Length: ' . $contentLength);
		header('Content-Type: text/xml');
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Build response XML body
	 * 
	 * @access protected
	 * @param  $params
	 * @return string
	 */
	protected function _buildResponseBody($param)
	{
		if ( is_object($param) )
		{
			$param = get_object_vars($param);
		}
		
		$body = '<?xml version="1.0" encoding="UTF-8"?>';
		$body .= '<methodResponse>';
		$body .=   '<params>';
		$body .=     $this->_encodeParameters((array)$param);
		$body .=   '</params>';
		$body .= '</methodResponse>';
		
		return $body;
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Encode response parameters
	 * 
	 * @access protected
	 * @return string
	 */
	protected function _encodeParameters($params)
	{
		$encoded = array();
		foreach ( $params as $param )
		{
			if ( $param instanceof $this->_encoder )
			{
				$encodedValue = $param->getValue();
				continue;
			}
			else
			{
				$type         = call_user_func(array($this->_encoder, 'detectType'), $param);
				$value        = new $this->_encoder($param, $type);
				$encodedValue = $value->getValue();
			}
			$encoded[] = '<param>' . $encodedValue . '</param>';
			
		}
		return implode('', $encoded);
	}
	
	
	// ---------------------------------------------------------------
	
	
	/**
	 * Send
	 * 
	 * @access protected
	 * @param  object $response
	 * @return string
	 */
	public function responseError($code = 400, $message = '')
	{
		$grep = array('&', '<', '>');
		$sed  = array('&amp;', '&lt;', '&gt;');
		$msg  = str_replace($grep, $sed, $message);
		
		$body = '<?xml version="1.0" encoding="UTF-8"?>';
		$body .= '<methodResponse>';
		$body .=   '<fault>';
		$body .=     '<value>';
		$body .=       '<struct>';
		$body .=         '<member>';
		$body .=           '<name>faultCode</name>';
		$body .=           '<value><int>' . $code . '</int></value>';
		$body .=         '</member>';
		$body .=         '<member>';
		$body .=           '<name>faultString</name>';
		$body .=           '<value><string>' . $msg . '</string></value>';
		$body .=         '</member>';
		$body .=       '</struct>';
		$body .=     '</value>';
		$body .=   '</fault>';
		$body .= '</methodResponse>';
		
		$this->_sendResponseHeader(strlen($body));
		echo $body;
		exit;
	}
}
