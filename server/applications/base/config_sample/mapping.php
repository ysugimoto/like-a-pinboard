<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * URI mapping definitions
 * 
 * @package  Seezoo-Framework
 * @category config
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/*
 * ====================================================================
 * Mapping definition format
 * 
 * Can be written in the following format,
 * you can specify mapping to access URI:
 * 
 * <code>
 * $mapping[<process>][<URIstring>] = <mapping>
 * </code>
 * 
 * Parameters:
 * <process>   : process mode
 * <URIstring> : mapping target URI-String ( enable use with regex )
 * <mapping>   : rewrited URIstring
 * 
 * <process> are available in the following string:
 * "action" : action mode
 * "mvc"    : MVC rotuing mode
 * "proc"   : process mode
 * "default": simple mode
 * 
 * ====================================================================
 */
