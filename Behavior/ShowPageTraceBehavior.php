<?php
    
    
    namespace Behavior;
    
    use Think\Behavior;

    /**
     * 页面Trace显示输出
     */
    class ShowPageTraceBehavior extends Behavior
    {
        protected $tracePageTabs = [
            'BASE'      => '基本',
            'FILE'      => '文件',
            'INFO'      => '流程',
            'ERR|NOTIC' => '错误',
            'SQL'       => 'SQL',
            'DEBUG'     => '调试',
        ];
        
        /**
         * 执行入口
         *
         * @param mixed $params
         */
        public function run(&$params)
        {
            if ( ! IS_AJAX && ! IS_CLI && C('SHOW_PAGE_TRACE')) {
                echo $this->_showTrace();
            }
        }
        
        /**
         * 显示页面Trace信息
         * @access private
         */
        private function _showTrace()
        {
            // 系统默认显示信息
            $files = get_included_files();
            $info  = [];
            foreach ($files as $file) {
                $info[] = $file.' ( '.number_format(filesize($file) / 1024, 2).' KB )';
            }
            $trace = [];
            $base  = [
                '请求信息' => date(
                              'Y-m-d H:i:s',
                              $_SERVER['REQUEST_TIME']
                          ).' '.$_SERVER['SERVER_PROTOCOL'].' '.$_SERVER['REQUEST_METHOD'].' : '.__SELF__,
                '运行时间' => $this->showTime(),
                '吞吐率'  => number_format(1 / G('beginTime', 'viewEndTime'), 2).'req/s',
                '内存开销' => MEMORY_LIMIT_ON ? number_format(
                                                (memory_get_usage() - $GLOBALS['_startUseMems']) / 1024,
                                                2
                                            ).' kb' : '不支持',
//                '查询信息' => N('db_query') . ' queries ' . N('db_write') . ' writes ',
                '文件加载' => count(get_included_files()),
//                '缓存信息' => N('cache_read') . ' gets ' . N('cache_write') . ' writes ',
                '配置加载' => count(C()),
                '会话信息' => 'SESSION_ID='.session_id(),
            ];
            // 读取应用定义的Trace文件
            $traceFile = COMMON_PATH.'Conf/trace.php';
            if (is_file($traceFile)) {
                /** @noinspection PhpIncludeInspection */
                $base = array_merge($base, include $traceFile);
            }
            $debug = trace();
            $tabs  = C('TRACE_PAGE_TABS', null, $this->tracePageTabs);
            foreach ($tabs as $name => $title) {
                switch (strtoupper($name)) {
                    // 基本信息
                    case 'BASE':
                        $trace[$title] = $base;
                        break;
                    // 文件信息
                    case 'FILE':
                        $trace[$title] = $info;
                        break;
                    // 调试信息
                    default:
                        $name = strtoupper($name);
                        // 多组信息
                        if (strpos($name, '|')) {
                            $names  = explode('|', $name);
                            $result = [];
                            foreach ($names as $name) {
                                $result += isset($debug[$name]) ? $debug[$name] : [];
                            }
                            $trace[$title] = $result;
                        } else {
                            $trace[$title] = isset($debug[$name]) ? $debug[$name] : '';
                        }
                }
            }
            unset($files, $info, $base);
            // 调用Trace页面模板
            ob_start();
            /** @noinspection PhpIncludeInspection */
            include C('TMPL_TRACE_FILE') ? C('TMPL_TRACE_FILE') : __DIR__.'/../Resources/page_trace.tpl';
            
            return ob_get_clean();
        }
        
        /**
         * 获取运行时间
         */
        private function showTime()
        {
            // 显示运行时间
            G('beginTime', $GLOBALS['_beginTime']);
            G('viewEndTime');
            
            // 显示详细运行时间
            return G('beginTime', 'viewEndTime').'s ( Load:'.G('beginTime', 'loadTime').'s Init:'.G(
                    'loadTime',
                    'initTime'
                ).'s Exec:'.G('initTime', 'viewStartTime').'s Template:'.G('viewStartTime', 'viewEndTime').'s )';
        }
    }
