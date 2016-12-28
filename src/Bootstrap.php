<?php
    
    /**
     * 公共入口文件
     */
    
    require __DIR__ . '/Base.php';
    
    if (!IS_CLI) {
        // 当前文件名
        if (!defined('_PHP_FILE_')) {
            if (IS_CGI) {
                //CGI/FASTCGI模式下
                $_temp = explode('.php', $_SERVER['PHP_SELF']);
                define('_PHP_FILE_', rtrim(str_replace($_SERVER['HTTP_HOST'], '', $_temp[0] . '.php'), '/'));
            } else {
                define('_PHP_FILE_', rtrim($_SERVER['SCRIPT_NAME'], '/'));
            }
        }
        if (!defined('__ROOT__')) {
            $_root = rtrim(dirname(_PHP_FILE_), '/');
            define('__ROOT__', (($_root == '/' || $_root == '\\') ? '' : $_root));
        }
    }
    /*
     * 加载引导类
     */
    require __DIR__ . '/Think/Think.php';