<?php

    namespace Think;

    /**
     * 引导类
     */
    class Think
    {

        /**
         * @var array $_map 类映射
         */
        private static $_map = [];

        /**
         * @var array $_instance 实例化对象
         */
        private static $_instance = [];

        /**
         * 应用程序初始化
         * @access public
         * @return void
         */
        static public function start()
        {
            /*
             * 设定错误和异常处理
             */
            register_shutdown_function([__CLASS__, 'fatalError']);
            set_error_handler([__CLASS__, 'appError']);
            set_exception_handler([__CLASS__, 'appException']);

            /*
             * 初始化文件存储方式
             */
            Storage::connect();

            $runtimefile = RUNTIME_PATH . '__runtime.php';
            if (!APP_DEBUG && Storage::has($runtimefile)) {
                Storage::load($runtimefile);
            } else {
                if (Storage::has($runtimefile))
                    Storage::unlink($runtimefile);
                $content = '';
                /**
                 * 检查核心必须文件
                 */
                if (!Storage::has(__DIR__ . '/../Conf/core.php') ||
                    !Storage::has(__DIR__ . '/../Conf/config.php') ||
                    !Storage::has(__DIR__ . '/../Conf/tags.php')
                ) {
                    self::halt('系统核心文件缺失');
                }
                /*
                 * 加载核心文件
                 */
                $mode = include __DIR__ . '/../Conf/core.php';
                foreach ($mode as $file) {
                    if (is_file($file)) {
                        include $file;
                        if (!APP_DEBUG) $content .= compile($file);
                    }
                }

                /*
                 * 加载应用模式配置文件
                 */
                $config = include __DIR__ . '/../Conf/config.php';
                foreach ($config as $key => $file) {
                    is_numeric($key) ? C(load_config($file)) : C($key, load_config($file));
                }

                /*
                 * 加载模式行为定义
                 */
                $tags = include __DIR__ . '/../Conf/tags.php';
                if (isset($tags)) {
                    Hook::import(is_array($tags) ? $tags : include $tags);
                }

                /*
                 * 加载应用行为
                 */
                if (is_file(CONF_PATH . 'tags.php')) {
                    Hook::import(include CONF_PATH . 'tags.php');
                }

                /*
                 * 加载框架底层语言包
                 */
                $lang = include __DIR__ . '/../Lang/' . C('DEFAULT_LANG') . EXT;
                L($lang);

                if (!APP_DEBUG) {
                    $content .= "\nnamespace { ";
                    $content .= "\nL(" . var_export(L(), true) . ");
                    \nC(" . var_export(C(), true) . ');
                    \\Think\\Hook::import(' . var_export(Hook::get(), true) . ');}';
                    Storage::put($runtimefile, strip_whitespace('<?php ' . $content));
                } else {
                    // 调试模式加载系统默认的配置文件
                    C(include __DIR__ . '/../Conf/debug.php');
                    // 读取应用调试配置文件
                    if (is_file(CONF_PATH . 'debug' . EXT))
                        C(include CONF_PATH . 'debug' . EXT);
                }
            }

            /*
             * 读取当前应用状态对应的配置文件
             */
            if (APP_STATUS && is_file(CONF_PATH . APP_STATUS . EXT)) {
                C(include CONF_PATH . APP_STATUS . EXT);
            }

            /*
             * 设置系统时区
             */
            date_default_timezone_set(C('DEFAULT_TIMEZONE'));

            /*
             * 检查应用目录结构 如果不存在则自动创建
             */
            if (C('CHECK_APP_DIR')) {
                $module = defined('BIND_MODULE') ? BIND_MODULE : C('DEFAULT_MODULE');
                if (!is_dir(APP_PATH . $module) || !is_dir(LOG_PATH)) {
                    // 检测应用目录结构
                    Build::checkDir($module);
                }
            }

            /*
             * 记录加载文件时间
             */
            G('loadTime');

            /*
             * 运行应用
             */
            App::run();
        }

        /**
         * 取得对象实例 支持调用类的静态方法
         * @param string $class  对象类名
         * @param string $method 类的静态方法名
         * @return object
         */
        static public function instance($class, $method = '')
        {
            $identify = $class . $method;
            if (!isset(self::$_instance[$identify])) {
                if (class_exists($class)) {
                    $o = new $class();
                    if (!empty($method) && method_exists($o, $method))
                        self::$_instance[$identify] = call_user_func([&$o, $method]);
                    else
                        self::$_instance[$identify] = $o;
                } else
                    self::halt(L('_CLASS_NOT_EXIST_') . ':' . $class);
            }

            return self::$_instance[$identify];
        }

        /**
         * 自定义异常处理
         * @access public
         * @param mixed $e 异常对象
         */
        static public function appException($e)
        {
            $error = [];
            $error['message'] = $e->getMessage();
            $trace = $e->getTrace();
            if ('E' == $trace[0]['function']) {
                $error['file'] = $trace[0]['file'];
                $error['line'] = $trace[0]['line'];
            } else {
                $error['file'] = $e->getFile();
                $error['line'] = $e->getLine();
            }
            $error['trace'] = $e->getTraceAsString();
            Log::record($error['message'], Log::ERR);
            // 发送404信息
            header('HTTP/1.1 404 Not Found');
            header('Status:404 Not Found');
            self::halt($error);
        }

        /**
         * 自定义错误处理
         * @access public
         * @param int    $errno   错误类型
         * @param string $errstr  错误信息
         * @param string $errfile 错误文件
         * @param int    $errline 错误行数
         * @return void
         */
        static public function appError($errno, $errstr, $errfile, $errline)
        {
            switch ($errno) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_USER_ERROR:
                    ob_end_clean();
                    $errorStr = "$errstr " . $errfile . " 第 $errline 行.";
                    if (C('LOG_RECORD')) Log::write("[$errno] " . $errorStr, Log::ERR);
                    self::halt($errorStr);
                    break;
                default:
                    $errorStr = "[$errno] $errstr " . $errfile . " 第 $errline 行.";
                    self::trace($errorStr, '', 'NOTIC');
                    break;
            }
        }

        /**
         * 致命错误捕获
         */
        static public function fatalError()
        {
            Log::save();
            if ($e = error_get_last()) {
                switch ($e['type']) {
                    case E_ERROR:
                    case E_PARSE:
                    case E_CORE_ERROR:
                    case E_COMPILE_ERROR:
                    case E_USER_ERROR:
                        ob_end_clean();
                        self::halt($e);
                        break;
                }
            }
        }

        /**
         * 错误输出
         * @param mixed $error 错误
         * @return void
         */
        static public function halt($error)
        {
            $e = [];
            if (APP_DEBUG || IS_CLI) {
                //调试模式下输出错误信息
                if (!is_array($error)) {
                    $trace = debug_backtrace();
                    $e['message'] = $error;
                    $e['file'] = $trace[0]['file'];
                    $e['line'] = $trace[0]['line'];
                    ob_start();
                    debug_print_backtrace();
                    $e['trace'] = ob_get_clean();
                } else {
                    $e = $error;
                }
                if (IS_CLI) {
                    exit(iconv('UTF-8', 'gbk', $e['message']) . PHP_EOL . 'FILE: ' . $e['file'] . '(' . $e['line'] . ')' . PHP_EOL . $e['trace']);
                }
            } else {
                //否则定向到错误页面
                $error_page = C('ERROR_PAGE');
                if (!empty($error_page)) {
                    redirect($error_page);
                } else {
                    $message = is_array($error) ? $error['message'] : $error;
                    $e['message'] = C('SHOW_ERROR_MSG') ? $message : C('ERROR_MESSAGE');
                }
            }
            // 包含异常页面模板
            $exceptionFile = C('TMPL_EXCEPTION_FILE', null, C('TMPL_EXCEPTION_FILE'));
            include $exceptionFile;
            exit;
        }

        /**
         * 添加和获取页面Trace记录
         * @param string  $value  变量
         * @param string  $label  标签
         * @param string  $level  日志级别(或者页面Trace的选项卡)
         * @param boolean $record 是否记录日志
         * @return void|array
         */
        static public function trace($value = '[think]', $label = '', $level = 'DEBUG', $record = false)
        {
            static $_trace = [];
            // 获取trace信息
            if ('[think]' === $value) {
                return $_trace;
            } else {
                $info = ($label ? $label . ':' : '') . print_r($value, true);
                $level = strtoupper($level);

                if ((defined('IS_AJAX') && IS_AJAX) || !C('SHOW_PAGE_TRACE') || $record) {
                    Log::record($info, $level, $record);
                } else {
                    if (!isset($_trace[$level]) || count($_trace[$level]) > C('TRACE_MAX_RECORD')) {
                        $_trace[$level] = [];
                    }
                    $_trace[$level][] = $info;
                }
            }
        }
    }
