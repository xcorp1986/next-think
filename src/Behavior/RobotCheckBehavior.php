<?php
    
    
    namespace Behavior;
    
    use Think\Behavior;
    
    /**
     * 机器人检测
     */
    class RobotCheckBehavior extends Behavior
    {
        
        /**
         * 执行入口
         * @param mixed $params
         */
        public function run(&$params)
        {
            // 机器人访问检测
            if (C('LIMIT_ROBOT_VISIT', null, true) && self::_isRobot()) {
                // 禁止机器人访问
                exit('Access Denied');
            }
        }
        
        /**
         * @return bool|null
         */
        private static function _isRobot()
        {
            static $_robot = null;
            if (is_null($_robot)) {
                $spiders = 'Bot|Crawl|Spider|slurp|sohu-search|lycos|robozilla';
                $browsers = 'MSIE|Netscape|Opera|Konqueror|Mozilla';
                if (preg_match("/($browsers)/", $_SERVER['HTTP_USER_AGENT'])) {
                    $_robot = false;
                } elseif (preg_match("/($spiders)/", $_SERVER['HTTP_USER_AGENT'])) {
                    $_robot = true;
                } else {
                    $_robot = false;
                }
            }
            
            return $_robot;
        }
    }