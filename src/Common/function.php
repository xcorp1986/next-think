<?php
    use Think\Cache;
    use Think\Exception;
    use Think\Log;
    use Think\Model;
    use Think\Storage;
    use Think\Think;
    
    /**
     * 获取和设置配置参数 支持批量定义
     * @param string|array $name    配置变量
     * @param mixed        $value   配置值
     * @param mixed        $default 默认值
     * @return mixed
     */
    function C($name = null, $value = null, $default = null)
    {
        static $_config = [];
        // 无参数时获取所有
        if (empty($name)) {
            return $_config;
        }
        // 优先执行设置获取或赋值
        if (is_string($name)) {
            if (!strpos($name, '.')) {
                $name = strtoupper($name);
                if (is_null($value)) {
                    return isset($_config[$name]) ? $_config[$name] : $default;
                }
                $_config[$name] = $value;
                
                return null;
            }
            // 二维数组设置和获取支持
            $name = explode('.', $name);
            $name[0] = strtoupper($name[0]);
            if (is_null($value)) {
                return isset($_config[$name[0]][$name[1]]) ? $_config[$name[0]][$name[1]] : $default;
            }
            $_config[$name[0]][$name[1]] = $value;
            
            return null;
        }
        // 批量设置
        if (is_array($name)) {
            $_config = array_merge($_config, array_change_key_case($name, CASE_UPPER));
            
            return null;
        }
        
        // 避免非法参数
        return null;
    }
    
    /**
     * 加载配置文件 支持格式转换 仅支持一级配置
     * @param string $file  配置文件名
     * @param string $parse 配置解析方法 有些格式需要用户自己解析
     * @return array
     */
    function load_config($file, $parse = CONF_PARSE)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);
        switch ($ext) {
            case 'php':
                /** @noinspection PhpIncludeInspection */
                return include $file;
            case 'ini':
                return parse_ini_file($file);
            //@todo yaml_parse_file need pecl yaml extension
            case 'yaml':
                return yaml_parse_file($file);
            case 'xml':
                return (array)simplexml_load_file($file);
            case 'json':
                return json_decode(file_get_contents($file), true);
            default:
                if (function_exists($parse)) {
                    return $parse($file);
                } else {
                    E(L('_NOT_SUPPORT_') . ':' . $ext);
                }
        }
    }
    
    /**
     * 抛出异常处理
     * @param string $msg  异常消息
     * @param int    $code 异常代码 默认为0
     * @throws \Think\Exception
     * @return void
     */
    function E($msg, $code = 0)
    {
        throw new Exception($msg, $code);
    }
    
    /**
     * 记录和统计时间（微秒）和内存使用情况
     * 使用方法:
     * <code>
     * G('begin'); // 记录开始标记位
     * // ... 区间运行代码
     * G('end'); // 记录结束标签位
     * echo G('begin','end',6); // 统计区间运行时间 精确到小数后6位
     * echo G('begin','end','m'); // 统计区间内存使用情况
     * 如果end标记位没有定义，则会自动以当前作为标记位
     * 其中统计内存使用需要 MEMORY_LIMIT_ON 常量为true才有效
     * </code>
     * @param string     $start 开始标签
     * @param string     $end   结束标签
     * @param int|string $dec   小数位或者m
     * @return mixed
     */
    function G($start, $end = '', $dec = 4)
    {
        static $_info = [];
        static $_mem = [];
        if (is_float($end)) {
            // 记录时间
            $_info[$start] = $end;
        } elseif (!empty($end)) {
            // 统计时间和内存使用
            if (!isset($_info[$end])) {
                $_info[$end] = microtime(true);
            }
            if (MEMORY_LIMIT_ON && $dec == 'm') {
                if (!isset($_mem[$end])) {
                    $_mem[$end] = memory_get_usage();
                }
                
                return number_format(($_mem[$end] - $_mem[$start]) / 1024);
            } else {
                return number_format(($_info[$end] - $_info[$start]), $dec);
            }
            
        } else {
            // 记录时间和内存使用
            $_info[$start] = microtime(true);
            if (MEMORY_LIMIT_ON) {
                $_mem[$start] = memory_get_usage();
            }
        }
        
        return null;
    }
    
    /**
     * 获取和设置语言定义(不区分大小写)
     * @param string|array $name  语言变量
     * @param mixed        $value 语言值或者变量
     * @return mixed
     */
    function L($name = null, $value = null)
    {
        static $_lang = [];
        // 空参数返回所有定义
        if (empty($name)) {
            return $_lang;
        }
        // 判断语言获取(或设置)
        // 若不存在,直接返回全大写$name
        if (is_string($name)) {
            $name = strtoupper($name);
            if (is_null($value)) {
                return isset($_lang[$name]) ? $_lang[$name] : $name;
            } elseif (is_array($value)) {
                // 支持变量
                $replace = array_keys($value);
                foreach ($replace as &$v) {
                    $v = '{$' . $v . '}';
                }
                
                return str_replace($replace, $value, isset($_lang[$name]) ? $_lang[$name] : $name);
            }
            // 语言定义
            $_lang[$name] = $value;
            
            return null;
        }
        // 批量定义
        if (is_array($name)) {
            $_lang = array_merge($_lang, array_change_key_case($name, CASE_UPPER));
        }
        
        return null;
    }
    
    /**
     * 添加和获取页面Trace记录
     * @param string $value  变量
     * @param string $label  标签
     * @param string $level  日志级别
     * @param bool   $record 是否记录日志
     * @return void
     */
    function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
    {
        Think::trace($value, $label, $level, $record);
    }
    
    /**
     * 获取模版文件 格式 资源://模块@主题/控制器/操作
     * @todo check
     * @param string $template 模版资源地址
     * @param string $layer    视图层（目录）名称
     * @return string
     */
    function T($template = '', $layer = '')
    {
        
        // 解析模版资源地址
        if (false === strpos($template, '://')) {
            $template = 'http://' . str_replace(':', '/', $template);
        }
        $info = parse_url($template);
        $file = $info['host'] . (isset($info['path']) ? $info['path'] : '');
        $module = isset($info['user']) ? $info['user'] . '/' : MODULE_NAME . '/';
        $extend = $info['scheme'];
        $layer = $layer ? $layer : C('DEFAULT_V_LAYER');
        
        // 获取当前主题的模版路径
        $auto = C('AUTOLOAD_NAMESPACE');
        // 扩展资源
        if ($auto && isset($auto[$extend])) {
            $baseUrl = $auto[$extend] . $module . $layer . '/';
        } elseif (C('VIEW_PATH')) {
            // 改变模块视图目录
            $baseUrl = C('VIEW_PATH');
        } elseif (defined('TMPL_PATH')) {
            // 指定全局视图目录
            $baseUrl = TMPL_PATH . $module;
        } else {
            $baseUrl = APP_PATH . $module . $layer . '/';
        }
        
        // 获取主题
        $theme = substr_count($file, '/') < 2 ? C('DEFAULT_THEME') : '';
        
        // 分析模板文件规则
        $depr = C('TMPL_FILE_DEPR');
        if ('' == $file) {
            // 如果模板文件名为空 按照默认规则定位
            $file = CONTROLLER_NAME . $depr . ACTION_NAME;
        } elseif (false === strpos($file, '/')) {
            $file = CONTROLLER_NAME . $depr . $file;
        } elseif ('/' != $depr) {
            $file = substr_count($file, '/') > 1 ? substr_replace($file, $depr, strrpos($file, '/'), 1) : str_replace('/', $depr, $file);
        }
        
        return $baseUrl . ($theme ? $theme . '/' : '') . $file . C('TMPL_TEMPLATE_SUFFIX');
    }
    
    /**
     * 获取输入参数 支持过滤和默认值
     * 使用方法:
     * <code>
     * I('id',0); 获取id参数 自动判断get或者post
     * I('post.name','','htmlspecialchars'); 获取$_POST['name']
     * I('get.'); 获取$_GET
     * </code>
     * @param string $name    变量的名称 支持指定类型
     * @param mixed  $default 不存在的时候默认值
     * @param mixed  $filter  参数过滤方法
     * @param mixed  $datas   要获取的额外数据源
     * @return mixed
     */
    function I($name, $default = '', $filter = null, $datas = null)
    {
        static $_PUT = null;
        // 指定修饰符
        if (strpos($name, '/')) {
            list($name, $type) = explode('/', $name, 2);
            // 默认强制转换为字符串
        } elseif (C('VAR_AUTO_STRING')) {
            $type = 's';
        }
        // 指定参数来源
        if (strpos($name, '.')) {
            list($method, $name) = explode('.', $name, 2);
            // 默认为自动判断
        } else {
            $method = 'param';
        }
        switch (strtolower($method)) {
            case 'get':
                $input =& $_GET;
                break;
            case 'post':
                $input =& $_POST;
                break;
            case 'put'     :
                if (is_null($_PUT)) {
                    parse_str(file_get_contents('php://input'), $_PUT);
                }
                $input = $_PUT;
                break;
            case 'param':
                switch ($_SERVER['REQUEST_METHOD']) {
                    case 'POST':
                        $input = $_POST;
                        break;
                    case 'PUT':
                        if (is_null($_PUT)) {
                            parse_str(file_get_contents('php://input'), $_PUT);
                        }
                        $input = $_PUT;
                        break;
                    default:
                        $input = $_GET;
                }
                break;
            case 'path':
                $input = [];
                if (!empty($_SERVER['PATH_INFO'])) {
                    $depr = C('URL_PATHINFO_DEPR');
                    $input = explode($depr, trim($_SERVER['PATH_INFO'], $depr));
                }
                break;
            case 'request':
                $input =& $_REQUEST;
                break;
            case 'session':
                $input =& $_SESSION;
                break;
            case 'cookie':
                $input =& $_COOKIE;
                break;
            case 'server':
                $input =& $_SERVER;
                break;
            case 'globals':
                $input =& $GLOBALS;
                break;
            case 'data':
                $input =& $datas;
                break;
            default:
                return null;
        }
        // 获取全部变量
        if ('' == $name) {
            $data = $input;
            $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
            if ($filters) {
                if (is_string($filters)) {
                    $filters = explode(',', $filters);
                }
                foreach ($filters as $filter) {
                    // 参数过滤
                    $data = array_map_recursive($filter, $data);
                }
            }
            // 取值操作
        } elseif (isset($input[$name])) {
            $data = $input[$name];
            $filters = isset($filter) ? $filter : C('DEFAULT_FILTER');
            if ($filters) {
                if (is_string($filters)) {
                    if (0 === strpos($filters, '/')) {
                        if (1 !== preg_match($filters, (string)$data)) {
                            // 支持正则验证
                            return isset($default) ? $default : null;
                        }
                    } else {
                        $filters = explode(',', $filters);
                    }
                } elseif (is_int($filters)) {
                    $filters = [$filters];
                }
                
                if (is_array($filters)) {
                    foreach ($filters as $filter) {
                        if (function_exists($filter)) {
                            // 参数过滤
                            $data = is_array($data) ? array_map_recursive($filter, $data) : $filter($data);
                        } else {
                            $data = filter_var($data, is_int($filter) ? $filter : filter_id($filter));
                            if (false === $data) {
                                return isset($default) ? $default : null;
                            }
                        }
                    }
                }
            }
            if (!empty($type)) {
                switch (strtolower($type)) {
                    // 数组
                    case 'a':
                        $data = (array)$data;
                        break;
                    // 数字
                    case 'd':
                        $data = (int)$data;
                        break;
                    // 浮点
                    case 'f':
                        $data = (float)$data;
                        break;
                    // 布尔
                    case 'b':
                        $data = (bool)$data;
                        break;
                    // 字符串
                    case 's':
                    default:
                        $data = (string)$data;
                }
            }
            // 变量默认值
        } else {
            $data = isset($default) ? $default : null;
        }
        is_array($data) && array_walk_recursive($data, 'think_filter');
        
        return $data;
    }
    
    /**
     * @param $filter
     * @param $data
     * @return array
     */
    function array_map_recursive($filter, $data)
    {
        $result = [];
        foreach ($data as $key => $val) {
            $result[$key] = is_array($val)
                ? array_map_recursive($filter, $val)
                : call_user_func($filter, $val);
        }
        
        return $result;
    }
    
    /**
     * 字符串命名风格转换
     * @todo remove in future
     * type 0 将Java风格转换为C的风格 1 将C风格转换为Java的风格
     * @param string $name 字符串
     * @param int    $type 转换类型
     * @return string
     */
    function parse_name($name, $type = 0)
    {
        if ($type) {
            return ucfirst(preg_replace_callback('/_([a-zA-Z])/', function ($match) {
                return strtoupper($match[1]);
            }, $name));
        } else {
            return strtolower(trim(preg_replace('/[A-Z]/', "_\\0", $name), "_"));
        }
    }
    
    /**
     * 优化的require_once
     * @deprecated
     * @param string $filename 文件地址
     * @return bool
     */
    function require_cache($filename)
    {
        static $_importFiles = [];
        if (!isset($_importFiles[$filename])) {
            if (file_exists_case($filename)) {
                /** @noinspection PhpIncludeInspection */
                require $filename;
                $_importFiles[$filename] = true;
            } else {
                $_importFiles[$filename] = false;
            }
        }
        
        return $_importFiles[$filename];
    }
    
    /**
     * 区分大小写的文件存在判断
     * @param string $filename 文件地址
     * @return bool
     */
    function file_exists_case($filename)
    {
        if (is_file($filename)) {
            if (IS_WIN && APP_DEBUG && (basename(realpath($filename)) != basename($filename))) {
                return false;
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * 实例化模型类 格式 [资源://][模块/]模型
     * @param string $name  资源地址
     * @param string $layer 模型层名称
     * @return \Think\Model
     */
    function D($name = '', $layer = '')
    {
        if (empty($name)) {
            return new Model;
        }
        static $_model = [];
        $layer = $layer ?: C('DEFAULT_M_LAYER');
        if (isset($_model[$name . $layer])) {
            return $_model[$name . $layer];
        }
        $class = parse_res_name($name, $layer);
        if (class_exists($class)) {
            $model = new $class(basename($name));
        } elseif (false === strpos($name, '/')) {
            // 自动加载公共模块下面的模型
            $class = '\\Common\\' . $layer . '\\' . $name . $layer;
            $model = class_exists($class) ? new $class($name) : new Model($name);
        } else {
            Log::record('D方法实例化没找到模型类' . $class, Log::NOTICE);
            $model = new Model(basename($name));
        }
        $_model[$name . $layer] = $model;
        
        return $model;
    }
    
    /**
     * 解析资源地址并导入类库文件
     * 例如 module/controller addon://module/behavior
     * @param string $name  资源地址 格式：[扩展://][模块/]资源名
     * @param string $layer 分层名称
     * @param int    $level 控制器层次
     * @return string
     */
    function parse_res_name($name, $layer, $level = 1)
    {
        // 指定扩展资源
        if (strpos($name, '://')) {
            list($extend, $name) = explode('://', $name);
        } else {
            $extend = '';
        }
        // 指定模块
        if (strpos($name, '/') && substr_count($name, '/') >= $level) {
            list($module, $name) = explode('/', $name, 2);
        } else {
            $module = defined('MODULE_NAME') ? MODULE_NAME : '';
        }
        $array = explode('/', $name);
        $class = $module . '\\' . $layer;
        foreach ($array as $name) {
            $class .= '\\' . parse_name($name, 1);
        }
        // 导入资源类库
        // 扩展资源
        if ($extend) {
            $class = $extend . '\\' . $class;
        }
        
        
        return $class . $layer;
    }
    
    /**
     * 用于实例化访问控制器
     * @param string $name 控制器名
     * @return \Think\Controller|false
     */
    function controller($name)
    {
        $layer = C('DEFAULT_C_LAYER');
        
        $class = MODULE_NAME . '\\' . $layer;
        $array = explode('/', $name);
        foreach ($array as $name) {
            $class .= '\\' . parse_name($name, 1);
        }
        $class .= $layer;
        
        if (class_exists($class)) {
            return new $class();
        } else {
            return false;
        }
    }
    
    /**
     * 实例化多层控制器 格式：[资源://][模块/]控制器
     * @param string $name  资源地址
     * @param string $layer 控制层名称
     * @param int    $level 控制器层次
     * @return \Think\Controller|false
     */
    function A($name, $layer = '', $level = 0)
    {
        static $_action = [];
        $layer = $layer ?: C('DEFAULT_C_LAYER');
        $level = $level ?: ($layer == C('DEFAULT_C_LAYER') ? C('CONTROLLER_LEVEL') : 1);
        if (isset($_action[$name . $layer])) {
            return $_action[$name . $layer];
        }
        
        $class = parse_res_name($name, $layer, $level);
        if (class_exists($class)) {
            $action = new $class();
            $_action[$name . $layer] = $action;
            
            return $action;
        } else {
            return false;
        }
    }
    
    
    /**
     * 远程调用控制器的操作方法 URL 参数格式 [资源://][模块/]控制器/操作
     * @param string       $url   调用地址
     * @param string|array $vars  调用参数 支持字符串和数组
     * @param string       $layer 要调用的控制层名称
     * @return mixed
     */
    function R($url, $vars = [], $layer = '')
    {
        $info = pathinfo($url);
        $action = $info['basename'];
        $module = $info['dirname'];
        $class = A($module, $layer);
        if ($class) {
            if (is_string($vars)) {
                parse_str($vars, $vars);
            }
            
            return call_user_func_array([&$class, $action . C('ACTION_SUFFIX')], $vars);
        } else {
            return false;
        }
    }
    
    /**
     * 浏览器友好的变量输出
     * @param mixed  $var    变量
     * @param bool   $echo   是否输出 默认为True 如果为false 则返回输出字符串
     * @param string $label  标签 默认为空
     * @param bool   $strict 是否严谨 默认为true
     * @return mixed
     */
    function dump($var, $echo = true, $label = null, $strict = true)
    {
        $label = ($label === null) ? '' : rtrim($label) . ' ';
        if (!$strict) {
            if (ini_get('html_errors')) {
                $output = print_r($var, true);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            } else {
                $output = $label . print_r($var, true);
            }
        } else {
            ob_start();
            var_dump($var);
            $output = ob_get_clean();
            if (!extension_loaded('xdebug')) {
                $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
                $output = '<pre>' . $label . htmlspecialchars($output, ENT_QUOTES) . '</pre>';
            }
        }
        if ($echo) {
            echo $output;
            
            return null;
        } else {
            return $output;
        }
    }
    
    /**
     * URL组装 支持不同URL模式
     * @param string       $url    URL表达式，格式：'[模块/控制器/操作#锚点@域名]?参数1=值1&参数2=值2...'
     * @param string|array $vars   传入的参数，支持数组和字符串
     * @param string|bool  $suffix 伪静态后缀，默认为true表示获取配置值
     * @param bool         $domain 是否显示域名
     * @return string
     */
    function U($url = '', $vars = '', $suffix = true, $domain = false)
    {
        // 解析URL
        $info = parse_url($url);
        $url = !empty($info['path']) ? $info['path'] : ACTION_NAME;
        // 解析锚点
        if (isset($info['fragment'])) {
            $anchor = $info['fragment'];
            // 解析参数
            if (false !== strpos($anchor, '?')) {
                list($anchor, $info['query']) = explode('?', $anchor, 2);
            }
            // 解析域名
            if (false !== strpos($anchor, '@')) {
                list($anchor, $host) = explode('@', $anchor, 2);
            }
            // 解析域名
        } elseif (false !== strpos($url, '@')) {
            list($url, $host) = explode('@', $info['path'], 2);
        }
        // 解析子域名
        if (isset($host)) {
            $domain = $host . (strpos($host, '.') ? '' : strstr($_SERVER['HTTP_HOST'], '.'));
        } elseif ($domain === true) {
            $domain = $_SERVER['HTTP_HOST'];
            // 开启子域名部署
            if (C('APP_SUB_DOMAIN_DEPLOY')) {
                $domain = $domain == 'localhost' ? 'localhost' : 'www' . strstr($_SERVER['HTTP_HOST'], '.');
                // '子域名'=>array('模块[/控制器]');
                foreach (C('APP_SUB_DOMAIN_RULES') as $key => $rule) {
                    $rule = is_array($rule) ? $rule[0] : $rule;
                    if (false === strpos($key, '*') && 0 === strpos($url, $rule)) {
                        // 生成对应子域名
                        $domain = $key . strstr($domain, '.');
                        $url = substr_replace($url, '', 0, strlen($rule));
                        break;
                    }
                }
            }
        }
        
        // 解析参数
        // aaa=1&bbb=2 转换成数组
        if (is_string($vars)) {
            parse_str($vars, $vars);
        } elseif (!is_array($vars)) {
            $vars = [];
        }
        // 解析地址里面参数 合并到vars
        if (isset($info['query'])) {
            parse_str($info['query'], $params);
            $vars = array_merge($params, $vars);
        }
        
        // URL组装
        $depr = C('URL_PATHINFO_DEPR');
        $urlCase = C('URL_CASE_INSENSITIVE');
        if ($url) {
            // 定义路由
            if (0 === strpos($url, '/')) {
                $route = true;
                $url = substr($url, 1);
                if ('/' != $depr) {
                    $url = str_replace('/', $depr, $url);
                }
            } else {
                // 安全替换
                if ('/' != $depr) {
                    $url = str_replace('/', $depr, $url);
                }
                // 解析模块、控制器和操作
                $url = trim($url, $depr);
                $path = explode($depr, $url);
                $var = [];
                $varModule = C('VAR_MODULE');
                $varController = C('VAR_CONTROLLER');
                $varAction = C('VAR_ACTION');
                $var[$varAction] = !empty($path) ? array_pop($path) : ACTION_NAME;
                $var[$varController] = !empty($path) ? array_pop($path) : CONTROLLER_NAME;
                if ($maps = C('URL_ACTION_MAP')) {
                    if (isset($maps[strtolower($var[$varController])])) {
                        $maps = $maps[strtolower($var[$varController])];
                        if ($action = array_search(strtolower($var[$varAction]), $maps)) {
                            $var[$varAction] = $action;
                        }
                    }
                }
                if ($maps = C('URL_CONTROLLER_MAP')) {
                    if ($controller = array_search(strtolower($var[$varController]), $maps)) {
                        $var[$varController] = $controller;
                    }
                }
                if ($urlCase) {
                    $var[$varController] = parse_name($var[$varController]);
                }
                $module = '';
                
                if (!empty($path)) {
                    $var[$varModule] = implode($depr, $path);
                } else {
                    if (C('MULTI_MODULE')) {
                        if (MODULE_NAME != C('DEFAULT_MODULE') || !C('MODULE_ALLOW_LIST')) {
                            $var[$varModule] = MODULE_NAME;
                        }
                    }
                }
                if (isset($var[$varModule])) {
                    $module = $var[$varModule];
                    unset($var[$varModule]);
                }
                
            }
        }
        
        // 普通模式URL转换
        if (C('URL_MODEL') == 0) {
            /** @noinspection PhpUndefinedVariableInspection */
            $url = __APP__ . '?' . C('VAR_MODULE') . "={$module}&" . http_build_query(array_reverse($var));
            if ($urlCase) {
                $url = strtolower($url);
            }
            if (!empty($vars)) {
                $vars = http_build_query($vars);
                $url .= '&' . $vars;
            }
        } else {
            // PATHINFO模式或者兼容URL模式
            if (isset($route)) {
                $url = __APP__ . '/' . rtrim($url, $depr);
            } else {
                /** @noinspection PhpUndefinedVariableInspection */
                $module = (defined('BIND_MODULE') && BIND_MODULE == $module) ? '' : $module;
                /** @noinspection PhpUndefinedVariableInspection */
                $url = __APP__ . '/' . ($module ? $module . MODULE_PATHINFO_DEPR : '') . implode($depr, array_reverse($var));
            }
            if ($urlCase) {
                $url = strtolower($url);
            }
            // 添加参数
            if (!empty($vars)) {
                foreach ($vars as $var => $val) {
                    if ('' !== trim($val)) {
                        $url .= $depr . $var . $depr . urlencode($val);
                    }
                }
            }
            if ($suffix) {
                $suffix = $suffix === true ? C('URL_HTML_SUFFIX') : $suffix;
                if ($pos = strpos($suffix, '|')) {
                    $suffix = substr($suffix, 0, $pos);
                }
                if ($suffix && '/' != substr($url, -1)) {
                    $url .= '.' . ltrim($suffix, '.');
                }
            }
        }
        if (isset($anchor)) {
            $url .= '#' . $anchor;
        }
        if ($domain) {
            $url = (is_ssl() ? 'https://' : 'http://') . $domain . $url;
        }
        
        return $url;
    }
    
    /**
     * 渲染输出Widget
     * @deprecated
     * @param string $name Widget名称
     * @param array  $data 传入的参数
     * @return mixed
     */
    function W($name, $data = [])
    {
        return R($name, $data, 'Widget');
    }
    
    /**
     * 判断是否SSL协议
     * @return bool
     */
    function is_ssl()
    {
        if (isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))) {
            return true;
        } elseif (isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * URL重定向
     * @param string $url  重定向的URL地址
     * @param int    $time 重定向的等待时间（秒）
     * @param string $msg  重定向前的提示信息
     * @return void
     */
    function redirect($url, $time = 0, $msg = '')
    {
        //多行URL地址支持
        $url = str_replace(["\n", "\r"], '', $url);
        if (empty($msg)) {
            $msg = "系统将在{$time}秒之后自动跳转到{$url}！";
        }
        if (!headers_sent()) {
            // redirect
            if (0 === $time) {
                header('Location: ' . $url);
            } else {
                header("refresh:{$time};url={$url}");
                echo $msg;
            }
            exit();
        } else {
            $str = "<meta http-equiv='Refresh' content='{$time};URL={$url}'>";
            if ($time != 0) {
                $str .= $msg;
            }
            exit($str);
        }
    }
    
    /**
     * 缓存管理
     * @param mixed $name    缓存名称，如果为数组表示进行缓存设置
     * @param mixed $value   缓存值
     * @param mixed $options 缓存参数
     * @return mixed
     */
    function S($name, $value = '', $options = null)
    {
        static $cache = '';
        if (is_array($options)) {
            // 缓存操作的同时初始化
            $type = isset($options['type']) ? $options['type'] : '';
            $cache = Cache::getInstance($type, $options);
        } elseif (is_array($name)) {
            // 缓存初始化
            $type = isset($name['type']) ? $name['type'] : '';
            
            return Cache::getInstance($type, $name);
        } elseif (empty($cache)) {
            // 自动初始化
            $cache = Cache::getInstance();
        }
        if ('' === $value) {
            // 获取缓存
            return $cache->get($name);
        } elseif (is_null($value)) {
            // 删除缓存
            return $cache->rm($name);
        } else {
            // 缓存数据
            if (is_array($options)) {
                $expire = isset($options['expire']) ? $options['expire'] : null;
            } else {
                $expire = is_numeric($options) ? $options : null;
            }
            
            return $cache->set($name, $value, $expire);
        }
    }
    
    /**
     * 快速文件数据读取和保存 针对简单类型数据 字符串、数组
     * @deprecated
     * @param string $name  缓存名称
     * @param mixed  $value 缓存值
     * @param string $path  缓存路径
     * @return mixed
     */
    function F($name, $value = '', $path = DATA_PATH)
    {
        static $_cache = [];
        $filename = $path . $name . '.php';
        if ('' !== $value) {
            if (is_null($value)) {
                // 删除缓存
                if (false !== strpos($name, '*')) {
                    // TODO
                    return false;
                } else {
                    unset($_cache[$name]);
                    
                    return Storage::unlink($filename);
                }
            } else {
                Storage::put($filename, serialize($value));
                // 缓存数据
                $_cache[$name] = $value;
                
                return null;
            }
        }
        // 获取缓存数据
        if (isset($_cache[$name])) {
            return $_cache[$name];
        }
        if (Storage::has($filename)) {
            $value = unserialize(Storage::read($filename));
            $_cache[$name] = $value;
        } else {
            $value = false;
        }
        
        return $value;
    }
    
    /**
     * 根据PHP各种类型变量生成唯一标识号
     * @param mixed $mix 变量
     * @return string
     */
    function to_guid_string($mix)
    {
        if (is_object($mix)) {
            return spl_object_hash($mix);
        } elseif (is_resource($mix)) {
            $mix = get_resource_type($mix) . strval($mix);
        } else {
            $mix = serialize($mix);
        }
        
        return md5($mix);
    }
    
    /**
     * XML编码
     * @param mixed  $data     数据
     * @param string $root     根节点名
     * @param string $item     数字索引的子节点名
     * @param string $attr     根节点属性
     * @param string $id       数字索引子节点key转换的属性名
     * @param string $encoding 数据编码
     * @return string
     */
    function xml_encode($data, $root = 'think', $item = 'item', $attr = '', $id = 'id', $encoding = 'utf-8')
    {
        if (is_array($attr)) {
            $_attr = [];
            foreach ($attr as $key => $value) {
                $_attr[] = "{$key}=\"{$value}\"";
            }
            $attr = implode(' ', $_attr);
        }
        $attr = trim($attr);
        $attr = empty($attr) ? '' : " {$attr}";
        $xml = "<?xml version=\"1.0\" encoding=\"{$encoding}\"?>";
        $xml .= "<{$root}{$attr}>";
        $xml .= data_to_xml($data, $item, $id);
        
        return $xml . "</{$root}>";
    }
    
    /**
     * 数据XML编码
     * @param mixed  $data 数据
     * @param string $item 数字索引时的节点名称
     * @param string $id   数字索引key转换为的属性名
     * @return string
     */
    function data_to_xml($data, $item = 'item', $id = 'id')
    {
        $xml = $attr = '';
        foreach ($data as $key => $val) {
            if (is_numeric($key)) {
                $id && $attr = " {$id}=\"{$key}\"";
                $key = $item;
            }
            $xml .= "<{$key}{$attr}>";
            $xml .= (is_array($val) || is_object($val)) ? data_to_xml($val, $item, $id) : $val;
            $xml .= "</{$key}>";
        }
        
        return $xml;
    }
    
    /**
     * session管理函数
     * @param string|array $name  session名称 如果为数组则表示进行session设置
     * @param mixed        $value session值
     * @return mixed
     */
    function session($name = '', $value = '')
    {
        $prefix = C('SESSION_PREFIX');
        // session初始化 在session_start 之前调用
        if (is_array($name)) {
            if (isset($name['prefix'])) {
                C('SESSION_PREFIX', $name['prefix']);
            }
            if (C('VAR_SESSION_ID') && isset($_REQUEST[C('VAR_SESSION_ID')])) {
                session_id($_REQUEST[C('VAR_SESSION_ID')]);
            } elseif (isset($name['id'])) {
                session_id($name['id']);
            }
            if (isset($name['name'])) {
                session_name($name['name']);
            }
            if (isset($name['path'])) {
                session_save_path($name['path']);
            }
            if (isset($name['domain'])) {
                ini_set('session.cookie_domain', $name['domain']);
            }
            if (isset($name['expire'])) {
                ini_set('session.gc_maxlifetime', $name['expire']);
                ini_set('session.cookie_lifetime', $name['expire']);
            }
            if (isset($name['use_trans_sid'])) {
                ini_set('session.use_trans_sid', $name['use_trans_sid'] ? 1 : 0);
            }
            if (isset($name['use_cookies'])) {
                ini_set('session.use_cookies', $name['use_cookies'] ? 1 : 0);
            }
            if (isset($name['cache_limiter'])) {
                session_cache_limiter($name['cache_limiter']);
            }
            if (isset($name['cache_expire'])) {
                session_cache_expire($name['cache_expire']);
            }
            if (isset($name['type'])) {
                C('SESSION_TYPE', $name['type']);
            }
            if (C('SESSION_TYPE')) {
                // 读取session驱动
                $type = C('SESSION_TYPE');
                $class = strpos($type, '\\') ? $type : '\\Think\\Session\\Driver\\' . ucwords(strtolower($type));
                $hander = new $class();
                session_set_save_handler(
                    [&$hander, 'open'],
                    [&$hander, 'close'],
                    [&$hander, 'read'],
                    [&$hander, 'write'],
                    [&$hander, 'destroy'],
                    [&$hander, 'gc']);
            }
            // 启动session
            if (C('SESSION_AUTO_START')) {
                session_start();
            }
        } elseif ('' === $value) {
            if ('' === $name) {
                // 获取全部的session
                return $prefix ? $_SESSION[$prefix] : $_SESSION;
            } elseif (0 === strpos($name, '[')) {
                // session 操作
                if ('[pause]' == $name) {
                    // 暂停session
                    session_write_close();
                } elseif ('[start]' == $name) {
                    // 启动session
                    session_start();
                } elseif ('[destroy]' == $name) {
                    // 销毁session
                    $_SESSION = [];
                    session_unset();
                    session_destroy();
                } elseif ('[regenerate]' == $name) {
                    // 重新生成id
                    session_regenerate_id();
                }
            } elseif (0 === strpos($name, '?')) {
                // 检查session
                $name = substr($name, 1);
                if (strpos($name, '.')) {
                    // 支持数组
                    list($name1, $name2) = explode('.', $name);
                    
                    return $prefix ? isset($_SESSION[$prefix][$name1][$name2]) : isset($_SESSION[$name1][$name2]);
                } else {
                    return $prefix ? isset($_SESSION[$prefix][$name]) : isset($_SESSION[$name]);
                }
            } elseif (is_null($name)) {
                // 清空session
                if ($prefix) {
                    unset($_SESSION[$prefix]);
                } else {
                    $_SESSION = [];
                }
            } elseif ($prefix) {
                // 获取session
                if (strpos($name, '.')) {
                    list($name1, $name2) = explode('.', $name);
                    
                    return isset($_SESSION[$prefix][$name1][$name2]) ? $_SESSION[$prefix][$name1][$name2] : null;
                } else {
                    return isset($_SESSION[$prefix][$name]) ? $_SESSION[$prefix][$name] : null;
                }
            } else {
                if (strpos($name, '.')) {
                    list($name1, $name2) = explode('.', $name);
                    
                    return isset($_SESSION[$name1][$name2]) ? $_SESSION[$name1][$name2] : null;
                } else {
                    return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
                }
            }
        } elseif (is_null($value)) {
            // 删除session
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                if ($prefix) {
                    unset($_SESSION[$prefix][$name1][$name2]);
                } else {
                    unset($_SESSION[$name1][$name2]);
                }
            } else {
                if ($prefix) {
                    unset($_SESSION[$prefix][$name]);
                } else {
                    unset($_SESSION[$name]);
                }
            }
        } else {
            // 设置session
            if (strpos($name, '.')) {
                list($name1, $name2) = explode('.', $name);
                if ($prefix) {
                    $_SESSION[$prefix][$name1][$name2] = $value;
                } else {
                    $_SESSION[$name1][$name2] = $value;
                }
            } else {
                if ($prefix) {
                    $_SESSION[$prefix][$name] = $value;
                } else {
                    $_SESSION[$name] = $value;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Cookie 设置、获取、删除
     * @param string $name   cookie名称
     * @param mixed  $value  cookie值
     * @param mixed  $option cookie参数
     * @return mixed
     */
    function cookie($name = '', $value = '', $option = null)
    {
        // 默认设置
        $config = [
            // cookie 名称前缀
            'prefix'   => C('COOKIE_PREFIX'),
            // cookie 保存时间
            'expire'   => C('COOKIE_EXPIRE'),
            // cookie 保存路径
            'path'     => C('COOKIE_PATH'),
            // cookie 有效域名
            'domain'   => C('COOKIE_DOMAIN'),
            //  cookie 启用安全传输
            'secure'   => C('COOKIE_SECURE'),
            // httponly设置
            'httponly' => C('COOKIE_HTTPONLY'),
        ];
        // 参数设置(会覆盖黙认设置)
        if (!is_null($option)) {
            if (is_numeric($option)) {
                $option = ['expire' => $option];
            } elseif (is_string($option)) {
                parse_str($option, $option);
            }
            $config = array_merge($config, array_change_key_case($option));
        }
        if (!empty($config['httponly'])) {
            ini_set('session.cookie_httponly', 1);
        }
        // 清除指定前缀的所有cookie
        if (is_null($name)) {
            if (empty($_COOKIE)) {
                return null;
            }
            // 要删除的cookie前缀，不指定则删除config设置的指定前缀
            $prefix = empty($value) ? $config['prefix'] : $value;
            if (!empty($prefix)) {
                // 如果前缀为空字符串将不作处理直接返回
                foreach ($_COOKIE as $key => $val) {
                    if (0 === stripos($key, $prefix)) {
                        setcookie($key, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                        unset($_COOKIE[$key]);
                    }
                }
            }
            
            return null;
        } elseif ('' === $name) {
            // 获取全部的cookie
            return $_COOKIE;
        }
        $name = $config['prefix'] . str_replace('.', '_', $name);
        if ('' === $value) {
            if (isset($_COOKIE[$name])) {
                $value = $_COOKIE[$name];
                if (0 === strpos($value, 'think:')) {
                    $value = substr($value, 6);
                    
                    return array_map('urldecode', json_decode($value, true));
                } else {
                    return $value;
                }
            } else {
                return null;
            }
        } else {
            if (is_null($value)) {
                setcookie($name, '', time() - 3600, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                // 删除指定cookie
                unset($_COOKIE[$name]);
            } else {
                // 设置cookie
                if (is_array($value)) {
                    $value = 'think:' . json_encode(array_map('urlencode', $value));
                }
                $expire = !empty($config['expire']) ? time() + intval($config['expire']) : 0;
                setcookie($name, $value, $expire, $config['path'], $config['domain'], $config['secure'], $config['httponly']);
                $_COOKIE[$name] = $value;
            }
        }
        
        return null;
    }
    
    /**
     * 加载动态扩展文件
     * @var string $path 文件路径
     * @return void
     */
    function load_ext_file($path)
    {
        // 加载自定义外部文件
        if ($files = C('LOAD_EXT_FILE')) {
            $files = explode(',', $files);
            foreach ($files as $file) {
                $file = $path . 'Common/' . $file . '.php';
                if (is_file($file)) {
                    /** @noinspection PhpIncludeInspection */
                    include $file;
                }
            }
        }
        // 加载自定义的动态配置文件
        if ($configs = C('LOAD_EXT_CONFIG')) {
            if (is_string($configs)) {
                $configs = explode(',', $configs);
            }
            foreach ($configs as $key => $config) {
                $file = is_file($config) ? $config : $path . 'Conf/' . $config . '.php';
                if (is_file($file)) {
                    is_numeric($key) ? C(load_config($file)) : C($key, load_config($file));
                }
            }
        }
    }
    
    /**
     * 获取客户端IP地址
     * @param int  $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param bool $adv  是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function get_client_ip($type = 0, $adv = false)
    {
        $type = $type ? 1 : 0;
        static $ip = null;
        if ($ip !== null) {
            return $ip[$type];
        }
        if ($adv) {
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos = array_search('unknown', $arr);
                if (false !== $pos) {
                    unset($arr[$pos]);
                }
                $ip = trim($arr[0]);
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf('%u', ip2long($ip));
        $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
        
        return $ip[$type];
    }
    
    /**
     * 发送HTTP状态
     * @param int $code 状态码
     * @return void
     */
    function send_http_status($code)
    {
        static $_status = [
            // Informational 1xx
            100 => 'Continue',
            101 => 'Switching Protocols',
            // Success 2xx
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            // Redirection 3xx
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Moved Temporarily ',  // 1.1
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            // 306 is deprecated but reserved
            307 => 'Temporary Redirect',
            // Client Error 4xx
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            // Server Error 5xx
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported',
            509 => 'Bandwidth Limit Exceeded',
        ];
        if (isset($_status[$code])) {
            header('HTTP/1.1 ' . $code . ' ' . $_status[$code]);
            // 确保FastCGI模式下正常
            header('Status:' . $code . ' ' . $_status[$code]);
        }
    }
    
    /**
     * 安全过滤
     * @param $value
     */
    function think_filter(&$value)
    {
        // TODO 其他安全过滤
        
        // 过滤查询特殊字符
        if (preg_match('/^(EXP|NEQ|GT|EGT|LT|ELT|OR|XOR|LIKE|NOTLIKE|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|NOT IN|IN)$/i', $value)) {
            $value .= ' ';
        }
    }
    
    /**
     * 不区分大小写的in_array实现
     * @param $value
     * @param $array
     * @return bool
     */
    function in_array_case($value, $array)
    {
        return in_array(strtolower($value), array_map('strtolower', $array));
    }
