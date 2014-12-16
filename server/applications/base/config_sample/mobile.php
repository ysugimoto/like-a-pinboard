<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Mobile device detection settings
 * 
 * @package  Seezoo-Framework
 * @category config
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/*
 * ------------------------------------------------------
 * Mobile IP list cache directory
 * ------------------------------------------------------
 */
$mobile['cache_dir'] = ETCPATH . 'cache/';
	
/*
 * ------------------------------------------------------
 * Mobile IP list cache filename
 * ------------------------------------------------------
 */
$mobile['cache_file_name'] = 'ip_list.txt';
	
/*
 * ------------------------------------------------------
 * Mobile IP list cache expiration (sec degit)
 * ------------------------------------------------------
 */
$mobile['cache_expired_time'] = 604800; // 1 week
	
/*
 * ------------------------------------------------------
 * carrier scraping target page URLs
 * ------------------------------------------------------
 */
$mobile['carrier_urls'] = array(
	'au'       => 'http://www.au.kddi.com/ezfactory/tec/spec/ezsava_ip.html',
	'docomo'   => 'http://www.nttdocomo.co.jp/service/developer/make/content/ip/index.html',
	'softbank' => 'http://creation.mb.softbank.jp/mc/tech/tech_web/web_ipaddress.html',
	'willcom'  => 'http://www.willcom-inc.com/ja/service/contents_service/create/center_info/',
	'emobile'  => 'http://developer.emnet.ne.jp/ipaddress.html'
);
	
/*
 * ------------------------------------------------------
 * Carrier detection from IP
 * 
 * TRUE :
 * Judge from remote IP address
 * But also impossible to impersonate the user agent,
 * and in some cases, can not get a list of URI's official website is subject to change,
 * such as handling of cache files generated are somewhat slower
 * 
 * FALSE : 
 * Judge from User-Agent string
 * Determine the carrier from the user agent string easily.
 * Lighter-speed processing is not particularly need to update,
 * such as impersonation of a user agent is not available.
 * 
 * ------------------------------------------------------
 */
$mobile['ip_detection'] = FALSE;


