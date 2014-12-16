<?php if ( ! defined('SZ_EXEC') ) exit('access_denied');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * 
 * Application settings ( core basic settings override )
 * 
 * @package  Seezoo-Framework
 * @category config
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/*
 * --------------------------------------------------
 * base_url
 * 
 * set a your application root path
 * ( need slash on last character )
 * --------------------------------------------------
 */

$config['base_url'] = 'http://localhost/szfw/public/';


/*
 * --------------------------------------------------
 * application deploy_status
 * 
 * set a your application deploy_status below:
 * 
 * development - show all error and stacktrace
 * production  - hide stacktrace and error file and line number
 * --------------------------------------------------
 */
$config['deploy_status'] = 'development';


/*
 * --------------------------------------------------
 * enable_mod_rewrite
 * 
 * set TRUE when your application enables mod_rewrite
 * --------------------------------------------------
 */

$config['enable_mod_rewirte'] = FALSE;


/*
 * --------------------------------------------------
 * enable debug profiler
 * 
 * set TRUE when your application debug or develop
 * --------------------------------------------------
 */

$config['enable_debug'] = FALSE;


/*
 * --------------------------------------------------
 * Error reporting level
 * 
 * set repoting bit
 * --------------------------------------------------
 */

$config['error_reporting'] = E_ALL;



/*
 * --------------------------------------------------
 * Your application date-timezone
 * --------------------------------------------------
 */

$config['date_timezone'] = 'Asia/Tokyo';


/*
 * --------------------------------------------------
 * Server encoding
 * 
 * set your application server encoding
 * ( if your server cannot change )
 * --------------------------------------------------
 */

$config['server_encoding'] = 'UTF-8';


/*
 * --------------------------------------------------
 * Default database connection group
 * 
 * set default database connection group name that you want
 * --------------------------------------------------
 */
$config['default_database_connection_handle'] = 'default';


/*
 * --------------------------------------------------
 * Autoload
 * 
 * system loaded automatically
 * in controler instanciate process
 * --------------------------------------------------
 */
$config['autoload_database'] = FALSE;
$config['autoload_library']  = array();
$config['autoload_model']    = array();
$config['autoload_helper']   = array();


/*
 * --------------------------------------------------
 * default process mode
 * 
 * default process mode definition on Seezoo::init()
 * 
 * SZ_MODE_PROC    Function process mode
 * SZ_MODE_ACTION  Single action mode
 * SZ_MODE_DEFAULT Simple process mode
 * SZ_MODE_MVC     MVC-routing mode
 * --------------------------------------------------
 */

$config['default_process'] = SZ_MODE_MVC;


/*
 * --------------------------------------------------
 * default_module
 * 
 * If requested root path or directory,
 * use this controller at default. 
 * --------------------------------------------------
 */

$config['default_module'] = 'welcome';

/*
 * --------------------------------------------------
 * compile helper
 * 
 * Loaded helpers are able to use function
 * at viewfile that like CodeIgniter.
 * ex:
 * $Helpler->form->open() -> form_open()
 * --------------------------------------------------
 */

$config['compile_helper'] = TRUE;


/*
 * --------------------------------------------------
 * Model class treats Singleton
 * 
 * Through the this option to TRUE,
 * class becomes equal treatment and singleton class,
 * you will be sharing the instance.
 * You will save resources and to enable
 * if you do not want the status management in the class.
 * --------------------------------------------------
 */

$config['class_treats_singleton'] = TRUE;


/*
 * --------------------------------------------------
 * Logging setting
 * 
 * set a save to log filepath,
 * and set the logging level on your application status.
 * 0 : deploy
 * 1 : development
 * --------------------------------------------------
 */

$config['logging_level']     = 1;
$config['logging_save_type'] = 'file';
$config['logging_error']     = FALSE;
$config['logging_save_dir']  = ETCPATH . 'logs/';



/*
 * --------------------------------------------------
 * Default rendering engine
 * 
 * Framework use this default template engine.
 * this parameter is able to set these parameters
 * 
 * default:
 *  native PHP viewfile
 * smarty:
 *  use Smarty.
 *  it's need to set a Smarty package in engine/smarty/.
 * phptal:
 *  use PHPTAL
 *  it's need to set a PHPTAL package in engine/phptal/.
 * --------------------------------------------------
 */

$config['rendering_engine'] = 'default';


/*
 * --------------------------------------------------
 * Smarty setting
 * 
 * If you choose smarty on View rendering,
 * you have to determine some directories path,
 * and add write permission.
 * --------------------------------------------------
 */

$config['smarty_lib_path']             = ENGINEPATH . 'Smarty/';

$config['Smarty']['plugins_dir']       = ENGINEPATH . 'Smarty/plugins/';
$config['Smarty']['compile_dir']       = ETCPATH  . 'caches/smarty/templates_c/';
$config['Smarty']['config_dir']        = ETCPATH  . 'caches/smarty/configs/';
$config['Smarty']['cache_dir']         = ETCPATH  . 'caches/smarty/cache/';
$config['Smarty']['left_delimiter']    = '<!--{';
$config['Smarty']['right_delimiter']   = '}-->';
$config['Smarty']['default_modifiers'] = array('escape:"html"');


/*
 * --------------------------------------------------
 * PHPTAL setting
 * 
 * If you choose PHPTAL on View rendering,
 * you have to determine some directories path.
 * --------------------------------------------------
 */

$config['PHPTAL_lib_path'] = ENGINEPATH . 'phptal/';


/*
 * --------------------------------------------------
 * Twig setting
 * 
 * If you choose Twig on View rendering,
 * you have to determine some directories path.
 * --------------------------------------------------
 */

$config['Twig_lib_path'] = ENGINEPATH . 'Twig/';

$config['Twig']['cache'] = ETCPATH . 'caches/twig/';


/*
 * --------------------------------------------------
 * Cookie settings
 * 
 * Framework cookie settings. 
 * --------------------------------------------------
 */

$config['cookie_domain'] = '';
$config['cookie_path']   = '/';

/*
 * --------------------------------------------------
 * Session settings
 * 
 * Framework session settings. 
 * --------------------------------------------------
 */

$config['session_store_type']      = 'file';
$config['session_auth_key']        = 'seezoo_session_key';  // session authorize key
$config['session_lifetime']        = 500;                   // session expiration time ( sec digit )
$config['session_name']            = 'sz_session';          // session name
$config['session_encryption']      = TRUE;                  // encryption session auth key
$config['session_match_ip']        = TRUE;                  // session matching ip_address
$config['session_match_useragent'] = TRUE;                  // session matching User-Agent
$config['session_update_time']     = 300;                   // session_id update timing ( sec digit )


/* ----------------- File session config ---------------------- */
$config['session_filename_prefix'] = 'sess_';
$config['session_file_store_path'] = ETCPATH . 'caches/session/';

/* ----------------- Database session config ------------------ */
$config['session_db_tablename']    = 'sz_session';

/* ----------------- Memcache session config ------------------ */
$config['session_memcache_host']     = '127.0.0.1';
$config['session_memcache_port']     = 11211;
$config['session_memcache_pconnect'] = TRUE;

/* ----------------- Redis session config ------------------ */
$config['session_redis_host']     = '127.0.0.1';
$config['session_redis_port']     = 6379;
$config['session_redis_pconnect'] = TRUE;

/*
 * --------------------------------------------------
 * Encription settings
 * 
 * Encription key and Encription vector string 
 * --------------------------------------------------
 */

$config['encrypt_key_string']  = 'DogEncryption';
$config['encrypt_init_vector'] = 'szvector';

/*
 * --------------------------------------------------
 * Zip work mode
 * 
 * you can set these paramter:
 * auto   : auto detection
 * php    : use php ZipArchive class
 * manual : manually achive 
 * --------------------------------------------------
 */

$config['zip_mode'] = 'auto';

/*
 * --------------------------------------------------
 * Picture manipulation mode
 * 
 * you can set these paramter:
 * gd          : use GD libs ( default )
 * imagemagick : use Imagemagick ( need set imagemagick lib path )
 * --------------------------------------------------
 */

$config['picture_manipulation'] = 'gd';
$config['imagemagick_lib_path'] = '/usr/bin/convert';

/*
 * --------------------------------------------------
 * FTP server environment
 * 
 * Please set some parameters if you use FTP library:
 * 
 * hostname : FTP server address ( no need ftp:// protocol )
 * username : your account username
 * password : your account password
 * port     : FTP server port number
 * passive  : passive ransport mode boolean
 * --------------------------------------------------
 */
$config['FTP']['hostname'] = '';
$config['FTP']['username'] = '';
$config['FTP']['password'] = '';
$config['FTP']['port']     = 21;
$config['FTP']['passive']  = TRUE;


// EOF
