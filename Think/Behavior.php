<?php
    
    
    namespace Think;
    
    /**
     * 系统行为基类
     * Class Behavior
     * @package Think
     */
    abstract class Behavior
    {
        /**
         * 执行行为 run方法是Behavior唯一的接口
         * @access public
         *
         * @param mixed $params 行为参数
         *
         * @return void
         */
        abstract public function run(&$params);
        
    }