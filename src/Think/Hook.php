<?php
    
    namespace Think;
    
    /**
     * 系统钩子实现
     * Class Hook
     * @package Think
     */
    class Hook
    {
        
        /**
         * @var array $tags 行为集合
         */
        private static $tags = [];
        
        /**
         * 动态添加插件到某个标签
         * @param string       $tag  标签名称
         * @param array|string $name Behavior名称
         * @return void
         */
        public static function add($tag, $name)
        {
            if (!isset(static::$tags[$tag])) {
                static::$tags[$tag] = [];
            }
            if (is_array($name)) {
                static::$tags[$tag] = array_merge(static::$tags[$tag], $name);
            } else {
                static::$tags[$tag][] = $name;
            }
        }
        
        /**
         * 批量导入插件
         * @param array $data      插件信息
         * @param bool  $recursive 是否递归合并
         * @return void
         */
        public static function import(array $data = [], $recursive = true)
        {
            // 覆盖导入
            if (!$recursive) {
                static::$tags = array_merge(static::$tags, $data);
                // 合并导入
            } else {
                foreach ($data as $tag => $val) {
                    if (!isset(static::$tags[$tag])) {
                        static::$tags[$tag] = [];
                    }
                    if (!empty($val['_overlay'])) {
                        // 可以针对某个标签指定覆盖模式
                        unset($val['_overlay']);
                        static::$tags[$tag] = $val;
                    } else {
                        // 合并模式
                        static::$tags[$tag] = array_merge(static::$tags[$tag], $val);
                    }
                }
            }
        }
        
        /**
         * 获取插件信息
         * @param string $tag 插件位置 留空获取全部
         * @return array
         */
        public static function get($tag = '')
        {
            if (empty($tag)) {
                // 获取全部的插件信息
                return static::$tags;
            } else {
                return static::$tags[$tag];
            }
        }
        
        /**
         * 监听标签的插件
         * @param string $tag    标签名称
         * @param mixed  $params 传入参数
         * @return void
         */
        public static function listen($tag, &$params = null)
        {
            if (isset(static::$tags[$tag])) {
                if (APP_DEBUG) {
                    G($tag . 'Start');
                    trace('[ ' . $tag . ' ] --START--', '', 'INFO');
                }
                foreach (static::$tags[$tag] as $name) {
                    APP_DEBUG && G($name . '_start');
                    $result = static::exec($name, $tag, $params);
                    if (APP_DEBUG) {
                        G($name . '_end');
                        trace('Run ' . $name . ' [ RunTime:' . G($name . '_start', $name . '_end', 6) . 's ]', '', 'INFO');
                    }
                    if (false === $result) {
                        // 如果返回false 则中断插件执行
                        return;
                    }
                }
                // 记录行为的执行日志
                if (APP_DEBUG) {
                    trace('[ ' . $tag . ' ] --END-- [ RunTime:' . G($tag . 'Start', $tag . 'End', 6) . 's ]', '', 'INFO');
                }
            }
            
            return;
        }
        
        /**
         * 执行某个插件
         * @todo $name 这传参方式真的有点- -
         * @param string $name   插件名称
         * @param string $tag    方法名（标签名）
         * @param mixed  $params 传入的参数
         * @return mixed
         */
        public static function exec($name, $tag, &$params = null)
        {
            if ('Behavior' == substr($name, -8)) {
                // 行为扩展必须用run入口方法
                //@todo 注意，这里还必须区分，$tag还可能不是run！这标识符设计还真有点坑
                $tag = 'run';
            }
            $addon = new $name();
            
            return $addon->$tag($params);
        }
    }
