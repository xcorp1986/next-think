<?php
    
    
    namespace Think\Db;
    
    
    use Think\Exception;
    
    class DbDriverNotFoundException extends Exception
    {
        protected $message = '数据库驱动类不存在';
    }