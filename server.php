<?php
/**
* Web Socket Application Platfom loader
*
* @package      WebSocket
* @version      $Id$
* @author       [MF]
*
* RFC 6455
*/
namespace WebSocket;

define ('BASE_PATH', dirname(__FILE__) .  DIRECTORY_SEPARATOR);
define ('CONF_PATH', BASE_PATH . 'etc' .  DIRECTORY_SEPARATOR . 'config.ini');
define ('LIBS_PATH', BASE_PATH . 'libs' . DIRECTORY_SEPARATOR . __NAMESPACE__);

$_loadClasses = function ($path) use (&$_loadClasses)
{
    $iterator = new \DirectoryIterator($path);

    foreach ($iterator as $fileinfo) {

        if ($fileinfo->isFile()) {

            require_once ($fileinfo->getPath() . DIRECTORY_SEPARATOR . $fileinfo->getFilename());
        } else if ($fileinfo->isDir() && !$fileinfo->isDot()) {

            $_loadClasses($fileinfo->getPath() . DIRECTORY_SEPARATOR . $fileinfo->getFilename());
        }
    }
};

$_loadClasses(LIBS_PATH);

$config = parse_ini_file (CONF_PATH, true);

$master = new Manager (
    $config['server'],
    $config['handlers']
);
$master->listen();