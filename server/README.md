# "SZFW" : An application oriented framework

SZFW is a Simplified PHP MVC/Action Framework.

# Descriptions

* I can be described in the application structure controller general, models, and views.
* API like a CodeIgniter has been implemented. I mean, it is almost intact.
* It does not use the closure and name space and can work in a relatively old environment PHP5.1.6 more.
* Compatibility class of the function / class that are not supported by the old version is also implemented.
* I can use ActiveRecord like it.
* Core class extension, of course, I am also supports multi-stage inherited by prefix management.
* View engine can be selected from the default PHP / Smarty / PHPTAL / Twig.
* Normally the request, Ajax request, CLI execution because the separation, isolation Easy to implement.
* Requests that have been isolated are shared by a connector called "Lead".
* MVC routing / Action routing is possible.
* I can use multiple routing. (After routing, re-routing on the specified parameters)
* It is implemented in a class-based, helper will be compiled into a function depending on the setting.
* Others, library class that will be used almost implements roughly.
* There is also a console interface.
* It is lightweight and fast implementation so simple.

# Version requirements

PHP 5.1.6 or later.
mbstring/PDO/SPL enabled environment

# Installation

Git clone or Download archive and only accessed as document root public directory.

# Supported Features

* Database API（PDO-mysql/PDO-postgres/SQLite/odbc/Firebird）
* Aspect Wrapper
* Simple DI
* (Like) Activerecord
* Event-based hooks
* KVS-support (Memcache/Redis) - No need pecl extension
* Console Interface
* Extensible/compatible classes(YAML/JSON/ZipArchive)
* Multiple view engine (PHP/Smarty/PHPTAL/Twig)
* Picture manipulation (GD/Imagemagick)
* Encryption (mcrypt/xor/blowfish)
* Japanese optimized Mail sender (PHP-mail/SMTP/SMTP-AUTH)
* Japanese optimized Mail receiver (stdin/pop3/imap)
* Multi-backend session (Cookie/PHP/File/Database/Memcache/Redis)
* Validation/Verification (multiple-form/parameters/single-value)
* XML-RPC (Client/Server)
* OAuth
* Calendar
* Captcha (Japanese optimized)
* FTP
* Pagination
* Mobile detection
* RSS builder/parser
* File upload
* CSV Utility
* HTTP Request

# Resources

In preparation...

