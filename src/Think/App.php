<?php
    
    namespace Think;
    
    /**
     * 应用程序类 执行应用过程管理
     * Class App
     * @package Think
     */
    class App
    {
        
        /**
         * 应用程序初始化
         * @access public
         * @return void
         */
        public static function init()
        {
            // 加载动态应用公共文件和配置
            load_ext_file(COMMON_PATH);
            
            // 日志目录转换为绝对路径 默认情况下存储到公共模块下面
            C('LOG_PATH', realpath(LOG_PATH) . '/Common/');
            
            // 定义当前请求的系统常量
            define('NOW_TIME', $_SERVER['REQUEST_TIME']);
            define('REQUEST_METHOD', $_SERVER['REQUEST_METHOD']);
            define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
            define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
            define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
            define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
            
            // URL调度
            Dispatcher::dispatch();
            
            if (C('REQUEST_VARS_FILTER')) {
                // 全局安全过滤
                array_walk_recursive($_GET, 'think_filter');
                array_walk_recursive($_POST, 'think_filter');
                array_walk_recursive($_REQUEST, 'think_filter');
            }
            
            // URL调度结束标签
            //@todo check
//            Hook::listen('url_dispatch');
            
            define('IS_AJAX', ((isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') || !empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')])) ? true : false);
            
            // TMPL_EXCEPTION_FILE 改为绝对地址
            C('TMPL_EXCEPTION_FILE', realpath(C('TMPL_EXCEPTION_FILE')));
            
            return;
        }
        
        /**
         * 执行应用程序
         * @access public
         * @return void
         */
        public static function exec()
        {
            // 安全检测
            if (!preg_match('/^[A-Za-z](\/|\w)*$/', CONTROLLER_NAME)) {
                $module = false;
            } elseif (C('ACTION_BIND_CLASS')) {
                // 操作绑定到类：模块\Controller\控制器\操作
                $layer = C('DEFAULT_C_LAYER');
                if (is_dir(MODULE_PATH . $layer . '/' . CONTROLLER_NAME)) {
                    $namespace = MODULE_NAME . '\\' . $layer . '\\' . CONTROLLER_NAME . '\\';
                } else {
                    // 空控制器
                    $namespace = MODULE_NAME . '\\' . $layer . '\\_empty\\';
                }
                $actionName = strtolower(ACTION_NAME);
                if (class_exists($namespace . $actionName)) {
                    $class = $namespace . $actionName;
                } elseif (class_exists($namespace . '_empty')) {
                    // 空操作
                    $class = $namespace . '_empty';
                } else {
                    E(L('_ERROR_ACTION_') . ':' . ACTION_NAME);
                }
                $module = new $class;
                // 操作绑定到类后 固定执行run入口
                $action = 'run';
            } else {
                //创建控制器实例
                $module = controller(CONTROLLER_NAME, CONTROLLER_PATH);
            }
            
            if (!$module) {
                if ('4e5e5d7364f443e28fbf0d3ae744a59a' == CONTROLLER_NAME) {
                    header('Content-type:image/png');
                    exit(base64_decode(App::logo()));
                }
                
                // 是否定义Empty控制器
                $module = A('Empty');
                if (!$module) {
                    E(L('_CONTROLLER_NOT_EXIST_') . ':' . CONTROLLER_NAME);
                }
            }
            
            // 获取当前操作名 支持动态路由
            if (!isset($action)) {
                $action = ACTION_NAME . C('ACTION_SUFFIX');
            }
            try {
                self::invokeAction($module, $action);
            } catch (\ReflectionException $e) {
                // 方法调用发生异常后 引导到__call方法处理
                $method = new \ReflectionMethod($module, '__call');
                $method->invokeArgs($module, [$action, '']);
            }
            
            return;
        }
    
        /**
         * @param $module
         * @param $action
         * @throws \ReflectionException
         */
        public static function invokeAction($module, $action)
        {
            if (!preg_match('/^[A-Za-z](\w)*$/', $action)) {
                // 非法操作
                throw new \ReflectionException();
            }
            //执行当前操作
            $method = new \ReflectionMethod($module, $action);
            if ($method->isPublic() && !$method->isStatic()) {
                $class = new \ReflectionClass($module);
                // 前置操作
                if ($class->hasMethod('_before_' . $action)) {
                    $before = $class->getMethod('_before_' . $action);
                    if ($before->isPublic()) {
                        $before->invoke($module);
                    }
                }
                // URL参数绑定检测
                if ($method->getNumberOfParameters() > 0 && C('URL_PARAMS_BIND')) {
                    switch ($_SERVER['REQUEST_METHOD']) {
                        case 'POST':
                            $vars = array_merge($_GET, $_POST);
                            break;
                        case 'PUT':
                            parse_str(file_get_contents('php://input'), $vars);
                            break;
                        default:
                            $vars = $_GET;
                    }
                    $params = $method->getParameters();
                    $paramsBindType = C('URL_PARAMS_BIND_TYPE');
                    foreach ($params as $param) {
                        $name = $param->getName();
                        if (1 == $paramsBindType && !empty($vars)) {
                            $args[] = array_shift($vars);
                        } elseif (0 == $paramsBindType && isset($vars[$name])) {
                            $args[] = $vars[$name];
                        } elseif ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            E(L('_PARAM_ERROR_') . ':' . $name);
                        }
                    }
                    array_walk_recursive($args, 'think_filter');
                    $method->invokeArgs($module, $args);
                } else {
                    $method->invoke($module);
                }
                // 后置操作
                if ($class->hasMethod('_after_' . $action)) {
                    $after = $class->getMethod('_after_' . $action);
                    if ($after->isPublic()) {
                        $after->invoke($module);
                    }
                }
            } else {
                // 操作方法不是Public 抛出异常
                throw new \ReflectionException();
            }
        }
        
        /**
         * 运行应用实例 入口文件使用的快捷方法
         * @access public
         * @return void
         */
        public static function run()
        {
            // 应用初始化标签
            Hook::listen('app_init');
            self::init();
            // 应用开始标签
            Hook::listen('app_begin');
            // Session初始化
            if (!IS_CLI) {
                session(C('SESSION_OPTIONS'));
            }
            // 记录应用初始化时间
            G('initTime');
            self::exec();
            // 应用结束标签
            Hook::listen('app_end');
            
            return;
        }
    
        /**
         * @return string
         */
        public static function logo()
        {
            return 'iVBORw0KGgoAAAANSUhEUgAAADAAAAAwBAMAAAClLOS0AAAAHlBMVEVmAMz///+MQNnZwPKzgOaBLNWgYN/j0Pbx6PvBmOsL4vlcAAAAdklEQVR4XmMAglGQPAGHhKMBLokWFyBQwCIhCAIOWCSajYEggYAdhCSCjYGgEWyUGYqEoiAciKBKCClBgTqahACMxUxABwE7UCRw2kF/y1EtIizBagwGhcLGxqhRxSSIAhwQOlzAYKIIhFbAaQcNJYINGEYQAAA5wB30z2L7JgAAAABJRU5ErkJggg==';
        }
    }
