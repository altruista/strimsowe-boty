<?php

define('DIR_ROOT',      dirname(__FILE__) . '/');
define('DIR_TMP',       DIR_ROOT . 'tmp/');
define('DIR_COOKIES',   DIR_TMP .  'cookies/');
define('DIR_CONFIGS',   DIR_ROOT . 'configs/');
define('DIR_LOGS',      DIR_ROOT . 'tmp/logs/');
define('DIR_LIBRARY',   DIR_ROOT . 'library/');
define('DIR_MODULES',   DIR_ROOT . 'modules/');

function require_dir_once($path)
{       
    $files = glob($path . '*.php');
    foreach ($files as $file)
    {
        require_once $file;        
    }
    return $files;
}

function get_options($defaults)
{
    $options = array();
    return array_merge($defaults, $options);
}