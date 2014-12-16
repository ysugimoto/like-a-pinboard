<?php if ( ! defined('SZ_EXEC') ) exit('access denied.');

/**
 * ====================================================================
 * 
 * Seezoo-Framework
 * 
 * A simple MVC/action Framework on PHP 5.1.0 or newer
 * 
 * ------------------------------------------------------------------
 * 
 * Frameworn original Exception classes
 * 
 * @package  Seezoo-Framework
 * @category System
 * @author   Yoshiaki Sugimoto <neo.yoshiaki.sugimoto@gmail.com>
 * @license  MIT Licence
 * 
 * ====================================================================
 */

/**
 * Framework common Exception
 */
class SeezooException extends Exception {}

/**
 * Class not found Exeption
 */
class UndefinedClassException extends Exception {}

/**
 * File not found Exception
 */
class FileNotFoundException extends Exception {}
