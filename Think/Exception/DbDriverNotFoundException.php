<?php
    
    namespace Think\Exception;
    
    
    use Think\BaseException;

    class DbDriverNotFoundException extends BaseException
    {
        protected $message = '数据库驱动类不存在';
    }