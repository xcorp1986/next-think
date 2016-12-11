<?php
    
    
    namespace Behavior;
    
    use Think\Behavior;
    
    /**
     * 模板内容输出替换
     */
    class ContentReplaceBehavior extends Behavior
    {
        
        /**
         * 执行入口
         * @param mixed $content
         */
        public function run(&$content)
        {
            $content = $this->templateContentReplace($content);
        }
        
        /**
         * 模板内容替换
         * @access protected
         * @param string $content 模板内容
         * @return string
         */
        protected function templateContentReplace($content)
        {
            // 系统默认的特殊变量替换
            $replace = [
                '__ROOT__'       => __ROOT__,       // 当前网站地址
                '__APP__'        => __APP__,        // 当前应用地址
                '__MODULE__'     => __MODULE__,
                '__ACTION__'     => __ACTION__,     // 当前操作地址
                '__SELF__'       => htmlentities(__SELF__),       // 当前页面地址
                '__CONTROLLER__' => __CONTROLLER__,
                '__URL__'        => __CONTROLLER__,
                '__PUBLIC__'     => __ROOT__ . '/Public',// 站点公共目录
            ];
            // 允许用户自定义模板的字符串替换
            if (is_array(C('TMPL_PARSE_STRING'))) {
                $replace = array_merge($replace, C('TMPL_PARSE_STRING'));
            }
            
            return str_replace(array_keys($replace), array_values($replace), $content);
        }
        
    }