<?php

namespace Think;

/**
 * 控制器基类
 * Class Controller
 * @package Think
 */
abstract class Controller
{

    /**
     * 视图实例对象
     * @var View $view
     * @access protected
     */
    protected $view = null;

    /**
     * 取得模板对象实例
     * @access public
     */
    public function __construct()
    {
        //实例化视图类
        $this->view = new View;
        //控制器初始化
        if (method_exists($this, '_init')) {
            $this->_init();
        }
    }

    /**
     * 控制器初始化附加方法
     */
    protected function _init()
    {
    }

    /**
     * 模板显示 调用内置的模板引擎显示方法，
     * @access protected
     *
     * @param string $templateFile 指定要调用的模板文件
     *                             默认为空 由系统自动定位模板文件
     * @param string $charset 输出编码
     * @param string $contentType 输出类型
     * @param string $content 输出内容
     * @param string $prefix 模板缓存前缀
     *
     * @return void
     */
    protected function display($templateFile = '', $charset = '', $contentType = '', $content = '', $prefix = '')
    {
        $this->view->display($templateFile, $charset, $contentType, $content, $prefix);
    }

    /**
     * 输出内容文本可以包括Html 并支持内容解析
     * @access protected
     *
     * @param string $content 输出内容
     * @param string $charset 模板输出字符集
     * @param string $contentType 输出类型
     * @param string $prefix 模板缓存前缀
     *
     * @return void
     */
    protected function show($content, $charset = '', $contentType = '', $prefix = '')
    {
        $this->view->display('', $charset, $contentType, $content, $prefix);
    }

    /**
     *  获取输出页面内容
     * 调用内置的模板引擎fetch方法，
     * @access protected
     *
     * @param string $templateFile 指定要调用的模板文件
     *                             默认为空 由系统自动定位模板文件
     * @param string $content 模板输出内容
     * @param string $prefix 模板缓存前缀
     *
     * @return string
     */
    protected function fetch($templateFile = '', $content = '', $prefix = '')
    {
        return $this->view->fetch($templateFile, $content, $prefix);
    }

    /**
     *  创建静态页面
     * @access   protected
     *
     * @param string $htmlFile 生成的静态文件名称
     * @param string $htmlPath 生成的静态文件路径
     * @param string $templateFile 指定要调用的模板文件
     *                             默认为空 由系统自动定位模板文件
     *
     * @return string
     */
    protected function buildHtml($htmlFile = '', $htmlPath = '', $templateFile = '')
    {
        $content = $this->fetch($templateFile);
        $htmlPath = !empty($htmlPath) ? $htmlPath : HTML_PATH;
        $htmlFile = $htmlPath.$htmlFile.C('HTML_FILE_SUFFIX');
        Storage::put($htmlFile, $content);

        return $content;
    }

    /**
     * 模板主题设置
     * @access protected
     *
     * @param string $theme 模版主题
     *
     * @return $this
     */
    protected function theme($theme)
    {
        $this->view->theme($theme);

        return $this;
    }

    /**
     * 模板变量赋值
     * @access protected
     *
     * @param mixed $name 要显示的模板变量
     * @param mixed $value 变量的值
     *
     * @return $this
     */
    protected function assign($name, $value = '')
    {
        $this->view->assign($name, $value);

        return $this;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->assign($name, $value);
    }

    /**
     * 取得模板显示变量的值
     * @access protected
     *
     * @param string $name 模板显示变量
     *
     * @return mixed
     */
    public function get($name = '')
    {
        return $this->view->get($name);
    }

    /**
     * @param $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * 检测模板变量的值
     * @access public
     *
     * @param string $name 名称
     *
     * @return bool
     */
    public function __isset($name)
    {
        return $this->get($name);
    }

    /**
     * 魔术方法 有不存在的操作的时候执行
     * @access public
     *
     * @param string $method 方法名
     * @param array $args 参数
     * @return void
     * @throws BaseException
     */
    public function __call($method, $args)
    {
        if (0 === strcasecmp($method, ACTION_NAME)) {
            if (method_exists($this, '_empty')) {
                // 如果定义了_empty操作 则调用
                $this->_empty($method, $args);
            } else {
                throw new BaseException(L('_ERROR_ACTION_').':'.ACTION_NAME);
            }
        } else {
            throw new BaseException(__CLASS__.':'.$method.L('_METHOD_NOT_EXIST_'));

            return;
        }
    }

    /**
     * 操作错误跳转的快捷方法
     * @access protected
     *
     * @param string $message 错误信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     *
     * @return void
     */
    protected function error($message = '', $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 0, $jumpUrl, $ajax);
    }

    /**
     * 操作成功跳转的快捷方法
     * @access protected
     *
     * @param string $message 提示信息
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     *
     * @return void
     */
    protected function success($message = '', $jumpUrl = '', $ajax = false)
    {
        $this->dispatchJump($message, 1, $jumpUrl, $ajax);
    }

    /**
     * Ajax方式返回数据到客户端
     * @access protected
     *
     * @param mixed $data 要返回的数据
     * @param String $type AJAX返回数据格式
     * @param int $json_option 传递给json_encode的option参数
     *
     * @return void
     */
    protected function ajaxReturn($data, $type = '', $json_option = 0)
    {
        //禁止trace以免影响输出
        C('SHOW_PAGE_TRACE',false);
        if (empty($type)) {
            $type = C('DEFAULT_AJAX_RETURN');
        }
        switch (strtoupper($type)) {
            case 'JSON' :
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:application/json; charset=utf-8');
                echo json_encode($data, $json_option);
                return;
            case 'XML'  :
                // 返回xml格式数据
                header('Content-Type:text/xml; charset=utf-8');
                echo xml_encode($data);
                return;
            case 'JSONP':
                // 返回JSON数据格式到客户端 包含状态信息
                header('Content-Type:text/javascript; charset=utf-8');
                $handler = isset($_GET[C('VAR_JSONP_HANDLER')]) ? $_GET[C('VAR_JSONP_HANDLER')] : C(
                    'DEFAULT_JSONP_HANDLER'
                );
                echo $handler.'('.json_encode($data, $json_option).');';
                return;
            case 'EVAL' :
                // 返回可执行的js脚本
                header('Content-Type:text/javascript; charset=utf-8');
                echo $data;
                return;
            default     :
                break;
            // 用于扩展其他返回格式数据
//                    Hook::listen('ajax_return', $data);
        }
    }

    /**
     * Action跳转(URL重定向） 支持指定模块和延时跳转
     * @access protected
     *
     * @param string $url 跳转的URL表达式
     * @param array $params 其它URL参数
     * @param int $delay 延时跳转的时间 单位为秒
     * @param string $msg 跳转提示信息
     *
     * @return void
     */
    protected function redirect($url, $params = [], $delay = 0, $msg = '')
    {
        $url = U($url, $params);
        redirect($url, $delay, $msg);
    }

    /**
     * 默认跳转操作 支持错误导向和正确跳转
     * 调用模板显示 默认为public目录下面的success页面
     * 提示页面为可配置 支持模板标签
     *
     * @param string $message 提示信息
     * @param bool|int $status 状态
     * @param string $jumpUrl 页面跳转地址
     * @param mixed $ajax 是否为Ajax方式 当数字时指定跳转时间
     *
     * @return void
     * @access private
     */
    private function dispatchJump($message, $status = 1, $jumpUrl = '', $ajax = false)
    {
        // AJAX提交
        if (true === $ajax || IS_AJAX) {
            $data = is_array($ajax) ? $ajax : [];
            $data['info'] = $message;
            $data['status'] = $status;
            $data['url'] = $jumpUrl;
            $this->ajaxReturn($data);
        }
        if (is_int($ajax)) {
            $this->assign('waitSecond', $ajax);
        }
        if (!empty($jumpUrl)) {
            $this->assign('jumpUrl', $jumpUrl);
        }
        // 提示标题
        $this->assign('msgTitle', $status ? L('_OPERATION_SUCCESS_') : L('_OPERATION_FAIL_'));
        //如果设置了关闭窗口，则提示完毕后自动关闭窗口
        if ($this->get('closeWin')) {
            $this->assign('jumpUrl', 'javascript:window.close();');
        }
        // 状态
        $this->assign('status', $status);
        //保证输出不受静态缓存影响
        C('HTML_CACHE_ON', false);
        //发送成功信息
        if ($status) {
            // 提示信息
            $this->assign('message', $message);
            // 成功操作后默认停留1秒
            if (!isset($this->waitSecond)) {
                $this->assign('waitSecond', '1');
            }
            // 默认操作成功自动返回操作前页面
            if (!isset($this->jumpUrl)) {
                $this->assign('jumpUrl', $_SERVER["HTTP_REFERER"]);
            }
            $this->display(C('TMPL_ACTION_SUCCESS'));
        } else {
            // 提示信息
            $this->assign('error', $message);
            //发生错误时候默认停留3秒
            if (!isset($this->waitSecond)) {
                $this->assign('waitSecond', '3');
            }
            // 默认发生错误的话自动返回上页
            if (!isset($this->jumpUrl)) {
                $this->assign('jumpUrl', 'javascript:history.back(-1);');
            }
            $this->display(C('TMPL_ACTION_ERROR'));
            // 中止执行  避免出错后继续执行
            return;
        }
    }

    /**
     * 析构方法
     * @access public
     */
    public function __destruct()
    {
    }

    public function __debugInfo()
    {
        return [
            'ControllerName' => static::class,
            'view'           => $this->view,
        ];
    }

    public function __toString()
    {
        return static::class;
    }

}
