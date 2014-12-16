<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * KVS settings
 * 
 * @package  Seezoo-Framework
 * @category config
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

$kvs['driver'] = 'redis';

$kvs['memcache']['host'] = 'localhost';
$kvs['memcache']['port'] = 11211;

$kvs['redis']['host'] = 'localhost';
$kvs['redis']['port'] = 6379;

