<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Database settings
 * 
 * @package  Seezoo-Framework
 * @category config
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

// default group --------------------------------------------- //
$database['default']['host']         = 'localhost';
$database['default']['port']         = 3306;
$database['default']['username']     = '';
$database['default']['password']     = '';
$database['default']['driver']       = 'mysql';
$database['default']['dbname']       = '';
$database['default']['table_prefix'] = '';
$database['default']['driver_name']  = '';
$database['default']['pconnect']     = TRUE;
$database['default']['query_debug']  = TRUE;

// sample sqlite group --------------------------------------- //
$database['sqlite']['path']        = '/opt/database/';
$database['sqlite']['port']        = null;
$database['sqlite']['username']    = null;
$database['sqlite']['password']    = null;
$database['sqlite']['driver']      = 'sqlite2';
$database['sqlite']['dbname']      = 'db.sq2';
$database['sqlite']['pconnect']    = TRUE;
$database['sqlite']['driver_name']  = '';
$database['sqlite']['query_debug'] = FALSE;
