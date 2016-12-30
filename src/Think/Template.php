<?php
    
    namespace Think;
    
    use Think\Template\TagLib;
    
    /**
     * 内置模板引擎类
     * 支持XML标签和普通标签的模板解析
     * 编译型模板引擎 支持动态缓存
     * Class Template
     * @package Think
     */
    class  Template
    {
        
        /**
         * @var array $tagLib 模板页面中引入的标签库列表
         */
        protected $tagLib = [];
        /**
         * @var string $templateFile 当前模板文件
         */
        protected $templateFile = '';
        /**
         * @var array $tVar 模板变量
         */
        public $tVar = [];
        public $config = [];
        private $literal = [];
        private $block = [];
        
        /**
         * @access public
         */
        public function __construct()
        {
            $this->config['cache_path'] = C('CACHE_PATH');
            $this->config['template_suffix'] = C('TMPL_TEMPLATE_SUFFIX');
            $this->config['cache_suffix'] = C('TMPL_CACHFILE_SUFFIX');
            $this->config['tmpl_cache'] = C('TMPL_CACHE_ON');
            $this->config['cache_time'] = C('TMPL_CACHE_TIME');
            $this->config['taglib_begin'] = $this->stripPreg(C('TAGLIB_BEGIN'));
            $this->config['taglib_end'] = $this->stripPreg(C('TAGLIB_END'));
            $this->config['tmpl_begin'] = $this->stripPreg(C('TMPL_L_DELIM'));
            $this->config['tmpl_end'] = $this->stripPreg(C('TMPL_R_DELIM'));
            $this->config['default_tmpl'] = C('TEMPLATE_NAME');
            $this->config['layout_item'] = C('TMPL_LAYOUT_ITEM');
        }
        
        /**
         * 字符转义
         * @param $str
         * @return mixed
         */
        protected function stripPreg($str)
        {
            return str_replace(
                ['{', '}', '(', ')', '|', '[', ']', '-', '+', '*', '.', '^', '?'],
                ['\{', '\}', '\(', '\)', '\|', '\[', '\]', '\-', '\+', '\*', '\.', '\^', '\?'],
                $str);
        }
        
        /**
         * 模板变量获取
         * @access public
         * @param $name
         * @return bool|mixed
         */
        public function get($name)
        {
            if (isset($this->tVar[$name])) {
                return $this->tVar[$name];
            } else {
                return false;
            }
            
        }
        
        /**
         * 模板变量设置
         * @access public
         * @param $name
         * @param $value
         */
        public function set($name, $value)
        {
            $this->tVar[$name] = $value;
        }
        
        /**
         * 加载模板
         * @access public
         * @param string $templateFile 模板文件
         * @param array  $templateVar  模板变量
         * @param string $prefix       模板标识前缀
         */
        public function fetch($templateFile, $templateVar, $prefix = '')
        {
            $this->tVar = $templateVar;
            $templateCacheFile = $this->loadTemplate($templateFile, $prefix);
            
            return Storage::load($templateCacheFile, $this->tVar);
        }
        
        /**
         * 加载主模板并缓存
         * @access public
         * @param string $templateFile 模板文件
         * @param string $prefix       模板标识前缀
         * @return string
         */
        public function loadTemplate($templateFile, $prefix = '')
        {
            if (is_file($templateFile)) {
                $this->templateFile = $templateFile;
                // 读取模板文件内容
                $tmplContent = file_get_contents($templateFile);
            } else {
                $tmplContent = $templateFile;
            }
            // 根据模版文件名定位缓存文件
            $tmplCacheFile = $this->config['cache_path'] . $prefix . md5($templateFile) . $this->config['cache_suffix'];
            
            // 判断是否启用布局
            if (C('LAYOUT_ON')) {
                // 可以单独定义不使用布局
                if (false !== strpos($tmplContent, '{__NOLAYOUT__}')) {
                    $tmplContent = str_replace('{__NOLAYOUT__}', '', $tmplContent);
                } else {
                    // 替换布局的主体内容
                    $layoutFile = THEME_PATH . C('LAYOUT_NAME') . $this->config['template_suffix'];
                    // 检查布局文件
                    if (!is_file($layoutFile)) {
                        E(L('_TEMPLATE_NOT_EXIST_') . ':' . $layoutFile);
                    }
                    $tmplContent = str_replace($this->config['layout_item'], $tmplContent, file_get_contents($layoutFile));
                }
            }
            // 编译模板内容
            $tmplContent = $this->compiler($tmplContent);
            Storage::put($tmplCacheFile, trim($tmplContent));
            
            return $tmplCacheFile;
        }
        
        /**
         * 编译模板文件内容
         * @access protected
         * @param mixed $tmplContent 模板内容
         * @return string
         */
        protected function compiler($tmplContent)
        {
            //模板解析
            $tmplContent = $this->parse($tmplContent);
            // 还原被替换的Literal标签
            $tmplContent = preg_replace_callback('/<!--###literal(\d+)###-->/is', [$this, 'restoreLiteral'], $tmplContent);
            // 添加安全代码
            $tmplContent = '<?php if (!defined(\'APP_PATH\')) exit();?>' . $tmplContent;
            // 优化生成的php代码
            $tmplContent = str_replace('?><?php', '', $tmplContent);
            // 模版编译过滤标签
            Hook::listen('template_filter', $tmplContent);
            
            return strip_whitespace($tmplContent);
        }
        
        /**
         * 模板解析入口
         * 支持普通标签和TagLib解析 支持自定义标签库
         * @access public
         * @param string $content 要解析的模板内容
         * @return string
         */
        public function parse($content)
        {
            // 内容为空不解析
            if (empty($content)) {
                return '';
            }
            $begin = $this->config['taglib_begin'];
            $end = $this->config['taglib_end'];
            // 检查include标签
            $content = $this->parseInclude($content);
            // 检查PHP标签
            $content = $this->parsePhp($content);
            // 首先替换literal标签
            $content = preg_replace_callback('/' . $begin . 'literal' . $end . '(.*?)' . $begin . '\/literal' . $end . '/is', [$this, 'parseLiteral'], $content);
            
            // 内置标签库 不需使用标签库XML前缀
            $tagLibs = C('TAGLIB_BUILD_IN');
            /*
             * check if \Think\Template\TagLib\Cx::class is not loaded
             * \Think\Template\TagLib\Cx::class should load at last,
             * because other taglibs may depend on it,so push it at the
             * end of $tagLibs
             * @todo improve in future
             */
            if (!in_array(\Think\Template\TagLib\Cx::class, $tagLibs)) {
                array_push($tagLibs, \Think\Template\TagLib\Cx::class);
            }
            /**
             * @var $tag \Think\Template\TagLib
             */
            foreach ($tagLibs as $tag) {
                $this->parseTagLib(\Think\Think::instance($tag), $content);
            }
            
            //解析普通模板标签 {$tagName}
            return preg_replace_callback('/(' . $this->config['tmpl_begin'] . ')([^\d\w\s' . $this->config['tmpl_begin'] . $this->config['tmpl_end'] . '].+?)(' . $this->config['tmpl_end'] . ')/is'
                , [$this, 'parseTag'], $content);
        }
        
        /**
         * 解析PHP标签
         * @access protected
         * @param $content
         * @return mixed
         */
        protected function parsePhp($content)
        {
            if (ini_get('short_open_tag')) {
                // 开启短标签的情况要将<?标签用echo方式输出 否则无法正常输出xml标识
                $content = preg_replace('/(<\?(?!php|=|$))/i', '<?php echo \'\\1\'; ?>' . "\n", $content);
            }
            // PHP标签检查
            if (C('TMPL_DENY_PHP') && false !== strpos($content, '<?php')) {
                E(L('_NOT_ALLOW_PHP_'));
            }
            
            return $content;
        }
        
        /**
         * 解析模板中的布局标签
         * @access protected
         * @param $content
         * @return mixed
         */
        protected function parseLayout($content)
        {
            // 读取模板中的布局标签
            $find = preg_match('/' . $this->config['taglib_begin'] . 'layout\s(.+?)\s*?\/' . $this->config['taglib_end'] . '/is', $content, $matches);
            if ($find) {
                //替换Layout标签
                $content = str_replace($matches[0], '', $content);
                //解析Layout标签
                $array = $this->parseXmlAttrs($matches[1]);
                if (!C('LAYOUT_ON') || C('LAYOUT_NAME') != $array['name']) {
                    // 读取布局模板
                    $layoutFile = THEME_PATH . $array['name'] . $this->config['template_suffix'];
                    $replace = isset($array['replace']) ? $array['replace'] : $this->config['layout_item'];
                    // 替换布局的主体内容
                    $content = str_replace($replace, $content, file_get_contents($layoutFile));
                }
            } else {
                $content = str_replace('{__NOLAYOUT__}', '', $content);
            }
            
            return $content;
        }
        
        /**
         * 解析模板中的include标签
         * @access protected
         * @param      $content
         * @param bool $extend
         * @return mixed|string
         */
        protected function parseInclude($content, $extend = true)
        {
            // 解析继承
            if ($extend) {
                $content = $this->parseExtend($content);
            }
            // 解析布局
            $content = $this->parseLayout($content);
            // 读取模板中的include标签
            $find = preg_match_all('/' . $this->config['taglib_begin'] . 'include\s(.+?)\s*?\/' . $this->config['taglib_end'] . '/is', $content, $matches);
            if ($find) {
                for ($i = 0; $i < $find; $i++) {
                    $include = $matches[1][$i];
                    $array = $this->parseXmlAttrs($include);
                    $file = $array['file'];
                    unset($array['file']);
                    $content = str_replace($matches[0][$i], $this->parseIncludeItem($file, $array, $extend), $content);
                }
            }
            
            return $content;
        }
        
        /**
         * 解析模板中的extend标签
         * @access protected
         * @param $content
         * @return mixed|string
         */
        protected function parseExtend($content)
        {
            $begin = $this->config['taglib_begin'];
            $end = $this->config['taglib_end'];
            // 读取模板中的继承标签
            $find = preg_match('/' . $begin . 'extend\s(.+?)\s*?\/' . $end . '/is', $content, $matches);
            if ($find) {
                //替换extend标签
                $content = str_replace($matches[0], '', $content);
                // 记录页面中的block标签
                preg_replace_callback('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/is', [$this, 'parseBlock'], $content);
                // 读取继承模板
                $array = $this->parseXmlAttrs($matches[1]);
                $content = $this->parseTemplateName($array['name']);
                //对继承模板中的include进行分析
                $content = $this->parseInclude($content, false);
                // 替换block标签
                $content = $this->replaceBlock($content);
            } else {
                $content = preg_replace_callback('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '(.*?)' . $begin . '\/block' . $end . '/is', function ($match) {
                    return stripslashes($match[2]);
                }, $content);
            }
            
            return $content;
        }
        
        /**
         * 分析XML属性
         * @access private
         * @param string $attr XML属性字符串
         * @return array
         */
        private function parseXmlAttrs($attr)
        {
            $xml = '<tpl><tag ' . $attr . ' /></tpl>';
            /**
             * @var $xml \SimpleXMLElement
             */
            $xml = \simplexml_load_string($xml);
            if (!$xml) {
                E(L('_XML_TAG_ERROR_'));
            }
            $xml = (array)($xml->tag->attributes());
            
            return array_change_key_case($xml['@attributes']);
            
        }
        
        /**
         * 替换页面中的literal标签
         * @access private
         * @param string $content 模板内容
         * @return string|false
         */
        private function parseLiteral($content)
        {
            if (is_array($content)) {
                $content = $content[1];
            }
            if (trim($content) == '') {
                return '';
            }
            $i = count($this->literal);
            $parseStr = "<!--###literal{$i}###-->";
            $this->literal[$i] = $content;
            
            return $parseStr;
        }
        
        /**
         * 还原被替换的literal标签
         * @access private
         * @param string $tag literal标签序号
         * @return string|false
         */
        private function restoreLiteral($tag)
        {
            if (is_array($tag)) {
                $tag = $tag[1];
            }
            // 还原literal标签
            $parseStr = $this->literal[$tag];
            // 销毁literal记录
            unset($this->literal[$tag]);
            
            return $parseStr;
        }
        
        /**
         * 记录当前页面中的block标签
         * @access private
         * @param string $name    block名称
         * @param string $content 模板内容
         * @return string
         */
        private function parseBlock($name, $content = '')
        {
            if (is_array($name)) {
                $content = $name[2];
                $name = $name[1];
            }
            $this->block[$name] = $content;
            
            return '';
        }
        
        /**
         * 替换继承模板中的block标签
         * @access private
         * @param string $content 模板内容
         * @return string|bool
         */
        private function replaceBlock($content)
        {
            static $parse = 0;
            $begin = $this->config['taglib_begin'];
            $end = $this->config['taglib_end'];
            $reg = '/(' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . ')(.*?)' . $begin . '\/block' . $end . '/is';
            if (is_string($content)) {
                do {
                    $content = preg_replace_callback($reg, [$this, 'replaceBlock'], $content);
                } while ($parse && $parse--);
                
                return $content;
            } elseif (is_array($content)) {
                //存在嵌套，进一步解析
                if (preg_match('/' . $begin . 'block\sname=[\'"](.+?)[\'"]\s*?' . $end . '/is', $content[3])) {
                    $parse = 1;
                    $content[3] = preg_replace_callback($reg, [$this, 'replaceBlock'], "{$content[3]}{$begin}/block{$end}");
                    
                    return $content[1] . $content[3];
                } else {
                    $name = $content[2];
                    $content = $content[3];
                    $content = isset($this->block[$name]) ? $this->block[$name] : $content;
                    
                    return $content;
                }
            }
        }
        
        /**
         * TagLib库解析
         * @access public
         * @param TagLib $tagLib  要解析的标签库
         * @param string $content 要解析的模板内容
         * @return string
         */
        public function parseTagLib(TagLib $tagLib, &$content)
        {
            $begin = $this->config['taglib_begin'];
            $end = $this->config['taglib_end'];
            $that = &$this;
            foreach ($tagLib->getTags() as $name => $val) {
                $tags = [$name];
                // 别名设置
                if (isset($val['alias'])) {
                    $tags = explode(',', $val['alias']);
                    $tags[] = $name;
                }
                $level = isset($val['level']) ? $val['level'] : 1;
                $closeTag = isset($val['close']) ? $val['close'] : true;
                foreach ($tags as $tag) {
                    if (!method_exists($tagLib, '_' . $tag)) {
                        // 别名可以无需定义解析方法
                        $tag = $name;
                    }
                    $patternTail = empty($val['attr']) ? '(\s*?)' : '\s([^' . $end . ']*)';
                    
                    if (!$closeTag) {
                        $patterns = '/' . $begin . $tag . $patternTail . '\/(\s*?)' . $end . '/is';
                        $content = preg_replace_callback($patterns, function ($matches) use ($tagLib, $tag, $that) {
                            return $that->parseXmlTag($tagLib, $tag, $matches[1], $matches[2]);
                        }, $content);
                    } else {
                        $patterns = '/' . $begin . $tag . $patternTail . $end . '(.*?)' . $begin . '\/' . $tag . '(\s*?)' . $end . '/is';
                        for ($i = 0; $i < $level; $i++) {
                            $content = preg_replace_callback($patterns, function ($matches) use ($tagLib, $tag, $that) {
                                return $that->parseXmlTag($tagLib, $tag, $matches[1], $matches[2]);
                            }, $content);
                        }
                    }
                }
            }
        }
        
        /**
         * 解析标签库的标签
         * 需要调用对应的标签库文件解析类
         * @access public
         * @param TagLib $tagLib  标签库对象实例
         * @param string $tag     标签名
         * @param string $attr    标签属性
         * @param string $content 标签内容
         * @return string|false
         */
        public function parseXmlTag(TagLib $tagLib, $tag, $attr, $content)
        {
            $parse = '_' . $tag;
            $content = trim($content);
            $tags = $tagLib->parseXmlAttr($attr, $tag);
            
            return $tagLib->$parse($tags, $content);
        }
        
        /**
         * 模板标签解析
         * 格式： {TagName:args [|content] }
         * @access public
         * @param string $tagStr 标签内容
         * @return string
         */
        public function parseTag($tagStr)
        {
            if (is_array($tagStr)) {
                $tagStr = $tagStr[2];
            }
            $tagStr = stripslashes($tagStr);
            $flag = substr($tagStr, 0, 1);
            $flag2 = substr($tagStr, 1, 1);
            $name = substr($tagStr, 1);
            //解析模板变量 格式 {$varName}
            if ('$' == $flag && '.' != $flag2 && '(' != $flag2) {
                return $this->parseVar($name);
            } elseif ('-' == $flag || '+' == $flag) {
                // 输出计算
                return '<?php echo ' . $flag . $name . ';?>';
            } elseif (':' == $flag) {
                // 输出某个函数的结果
                return '<?php echo ' . $name . ';?>';
            } elseif ('~' == $flag) {
                // 执行某个函数
                return '<?php ' . $name . ';?>';
            } elseif (substr($tagStr, 0, 2) == '//' || (substr($tagStr, 0, 2) == '/*' && substr(rtrim($tagStr), -2) == '*/')) {
                //注释标签
                return '';
            }
            
            // 未识别的标签直接返回
            return C('TMPL_L_DELIM') . $tagStr . C('TMPL_R_DELIM');
        }
        
        /**
         * 模板变量解析,支持使用函数
         * 格式： {$varname|function1|function2=arg1,arg2}
         * @access public
         * @param string $varStr 变量数据
         * @return string
         */
        public function parseVar($varStr)
        {
            $varStr = trim($varStr);
            static $_varParseList = [];
            //如果已经解析过该变量字串，则直接返回变量值
            if (isset($_varParseList[$varStr])) {
                return $_varParseList[$varStr];
            }
            $parseStr = '';
            if (!empty($varStr)) {
                $varArray = explode('|', $varStr);
                //取得变量名称
                $var = array_shift($varArray);
                if (false !== strpos($var, '.')) {
                    //支持 {$var.property}
                    $vars = explode('.', $var);
                    $var = array_shift($vars);
                    switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
                        // 识别为数组
                        case 'array':
                            $name = '$' . $var;
                            foreach ($vars as $key => $val) {
                                $name .= '["' . $val . '"]';
                            }
                            break;
                        // 识别为对象
                        case 'obj':
                            $name = '$' . $var;
                            foreach ($vars as $key => $val) {
                                $name .= '->' . $val;
                            }
                            break;
                        // 自动判断数组或对象 只支持二维
                        default:
                            $name = 'is_array($' . $var . ')?$' . $var . '["' . $vars[0] . '"]:$' . $var . '->' . $vars[0];
                    }
                } elseif (false !== strpos($var, '[')) {
                    //支持 {$var['key']} 方式输出数组
                    $name = "$" . $var;
                    preg_match('/(.+?)\[(.+?)\]/is', $var, $match);
                    $var = $match[1];
                } elseif (false !== strpos($var, ':') && false === strpos($var, '(') && false === strpos($var, '::') && false === strpos($var, '?')) {
                    //支持 {$var:property} 方式输出对象的属性
                    $vars = explode(':', $var);
                    $var = str_replace(':', '->', $var);
                    $name = "$" . $var;
                    $var = $vars[0];
                } else {
                    $name = "$$var";
                }
                //对变量使用函数
                if (count($varArray) > 0) {
                    $name = $this->parseVarFunction($name, $varArray);
                }
                $parseStr = '<?php echo (' . $name . '); ?>';
            }
            $_varParseList[$varStr] = $parseStr;
            
            return $parseStr;
        }
        
        /**
         * 对模板变量使用函数
         * 格式 {$varname|function1|function2=arg1,arg2}
         * @access public
         * @param string $name     变量名
         * @param array  $varArray 函数列表
         * @return string
         */
        public function parseVarFunction($name, $varArray)
        {
            //对变量使用函数
            $length = count($varArray);
            //取得模板禁止使用函数列表
            $template_deny_funs = explode(',', C('TMPL_DENY_FUNC_LIST'));
            for ($i = 0; $i < $length; $i++) {
                $args = explode('=', $varArray[$i], 2);
                //模板函数过滤
                $fun = trim($args[0]);
                switch ($fun) {
                    // 特殊模板函数
                    case 'default':
                        $name = '(isset(' . $name . ') && (' . $name . ' !== ""))?(' . $name . '):' . $args[1];
                        break;
                    // 通用模板函数
                    default:
                        if (!in_array($fun, $template_deny_funs)) {
                            if (isset($args[1])) {
                                if (strstr($args[1], '###')) {
                                    $args[1] = str_replace('###', $name, $args[1]);
                                    $name = "$fun($args[1])";
                                } else {
                                    $name = "$fun($name,$args[1])";
                                }
                            } elseif (!empty($args[0])) {
                                $name = "$fun($name)";
                            }
                        }
                }
            }
            
            return $name;
        }
        
        /**
         * 加载公共模板并缓存 和当前模板在同一路径，否则使用相对路径
         * @access private
         * @param string $tmplPublicName 公共模板文件名
         * @param array  $vars           要传递的变量列表
         * @return string
         */
        private function parseIncludeItem($tmplPublicName, $vars = [], $extend = true)
        {
            // 分析模板文件名并读取内容
            $parseStr = $this->parseTemplateName($tmplPublicName);
            // 替换变量
            foreach ($vars as $key => $val) {
                $parseStr = str_replace('[' . $key . ']', $val, $parseStr);
            }
            
            // 再次对包含文件进行模板分析
            return $this->parseInclude($parseStr, $extend);
        }
        
        /**
         * 分析加载的模板文件并读取内容 支持多个模板文件读取
         * @access private
         * @param string $templateName 模板文件名
         * @return string
         */
        private function parseTemplateName($templateName)
        {
            if (substr($templateName, 0, 1) == '$') {
                //支持加载变量文件名
                $templateName = $this->get(substr($templateName, 1));
            }
            $array = explode(',', $templateName);
            $parseStr = '';
            foreach ($array as $templateName) {
                if (empty($templateName)) {
                    continue;
                }
                if (false === strpos($templateName, $this->config['template_suffix'])) {
                    // 解析规则为 模块@主题/控制器/操作
                    $templateName = T($templateName);
                }
                // 获取模板文件内容
                $parseStr .= file_get_contents($templateName);
            }
            
            return $parseStr;
        }
    }
