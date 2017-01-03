<?php
    
    
    namespace Behavior;
    
    use Think\Behavior;
    use Think\Storage;
    use Think\Think;
    use Think\View;

    /**
     * 静态缓存读取
     */
    final class ReadHtmlCacheBehavior extends Behavior
    {
        /**
         * 执行入口
         * @param mixed $params
         */
        public function run(&$params)
        {
            // 开启静态缓存
            if (IS_GET && C('HTML_CACHE_ON')) {
                $cacheTime = $this->requireHtmlCache();
                //静态页面有效
                if (false !== $cacheTime && $this->checkHTMLCache(HTML_FILE_NAME, $cacheTime)) {
                    // 读取静态页面输出
                    echo Storage::read(HTML_FILE_NAME);
                    exit();
                }
            }
        }
        
        /**
         * 判断是否需要静态缓存
         * @return bool|mixed
         */
        private static function requireHtmlCache()
        {
            // 读取静态规则
            $htmls = C('HTML_CACHE_RULES');
            if (!empty($htmls)) {
                $htmls = array_change_key_case($htmls);
                // 静态规则文件定义格式 actionName=>array('静态规则','缓存时间','附加规则')
                // 'read'=>array('{id},{name}',60,'md5') 必须保证静态规则的唯一性 和 可判断性
                // 检测静态规则
                $controllerName = strtolower(CONTROLLER_NAME);
                $actionName = strtolower(ACTION_NAME);
                if (isset($htmls[$controllerName . ':' . $actionName])) {
                    // 某个控制器的操作的静态规则
                    $html = $htmls[$controllerName . ':' . $actionName];
                } elseif (isset($htmls[$controllerName . ':'])) {
                    // 某个控制器的静态规则
                    $html = $htmls[$controllerName . ':'];
                } elseif (isset($htmls[$actionName])) {
                    // 所有操作的静态规则
                    $html = $htmls[$actionName];
                } elseif (isset($htmls['*'])) {
                    // 全局静态规则
                    $html = $htmls['*'];
                }
                if (!empty($html)) {
                    // 解读静态规则
                    $rule = is_array($html) ? $html[0] : $html;
                    // 以$_开头的系统变量
                    $callback = function ($match) {
                        switch ($match[1]) {
                            case '_GET':
                                $var = $_GET[$match[2]];
                                break;
                            case '_POST':
                                $var = $_POST[$match[2]];
                                break;
                            case '_REQUEST':
                                $var = $_REQUEST[$match[2]];
                                break;
                            case '_SERVER':
                                $var = $_SERVER[$match[2]];
                                break;
                            case '_SESSION':
                                $var = $_SESSION[$match[2]];
                                break;
                            case '_COOKIE':
                                $var = $_COOKIE[$match[2]];
                                break;
                            default:
                                break;
                        }
                        
                        return (count($match) == 4) ? $match[3]($var) : $var;
                    };
                    $rule = preg_replace_callback('/{\$(_\w+)\.(\w+)(?:\|(\w+))?}/', $callback, $rule);
                    // {ID|FUN} GET变量的简写
                    $rule = preg_replace_callback('/{(\w+)\|(\w+)}/', function ($match) {
                        return $match[2]($_GET[$match[1]]);
                    }, $rule);
                    $rule = preg_replace_callback('/{(\w+)}/', function ($match) {
                        return $_GET[$match[1]];
                    }, $rule);
                    // 特殊系统变量
                    $rule = str_ireplace(
                        ['{:controller}', '{:action}', '{:module}'],
                        [CONTROLLER_NAME, ACTION_NAME, MODULE_NAME],
                        $rule);
                    // {|FUN} 单独使用函数
                    $rule = preg_replace_callback('/{|(\w+)}/', function ($match) {
                        return $match[1]();
                    }, $rule);
                    $cacheTime = C('HTML_CACHE_TIME', null, 60);
                    if (is_array($html)) {
                        // 应用附加函数
                        if (!empty($html[2])) {
                            $rule = $html[2]($rule);
                        }
                        // 缓存有效期
                        $cacheTime = isset($html[1]) ? $html[1] : $cacheTime;
                    }
                    
                    // 当前缓存文件
                    define('HTML_FILE_NAME', HTML_PATH . $rule . C('HTML_FILE_SUFFIX', null, '.html'));
                    
                    return $cacheTime;
                }
            }
            
            // 无需缓存
            return false;
        }
        
        /**
         * 检查静态HTML文件是否有效
         * 如果无效需要重新更新
         * @access public
         * @param string $cacheFile 静态文件名
         * @param mixed    $cacheTime 缓存有效期
         * @return bool
         */
        public static function checkHTMLCache($cacheFile = '', $cacheTime = '')
        {
            if (!is_file($cacheFile)) {
                return false;
            } elseif (filemtime(Think::instance(View::class)->parseTemplate()) > Storage::get($cacheFile, 'mtime', 'html')) {
                // 模板文件如果更新静态文件需要更新
                return false;
            } elseif (!is_numeric($cacheTime) && function_exists($cacheTime)) {
                return $cacheTime($cacheFile);
            } elseif ($cacheTime != 0 && NOW_TIME > Storage::get($cacheFile, 'mtime', 'html') + $cacheTime) {
                // 文件是否在有效期
                return false;
            }
            
            //静态文件有效
            return true;
        }
        
    }