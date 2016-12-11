<?php
    
    
    namespace Think\Template;
    
    use Think\Think;
    
    /**
     * 标签库TagLib解析基类
     */
    class TagLib
    {
        
        /**
         * 标签库定义XML文件
         * @var string
         * @access protected
         */
        protected $xml = '';
        /**
         * @var array $tags 标签定义
         */
        protected $tags = [];
        /**
         * 标签库名称
         * @var string
         * @access protected
         */
        protected $tagLib = '';
        
        /**
         * 标签库标签列表
         * @var array $tagList
         * @access protected
         */
        protected $tagList = [];
        
        /**
         * 标签库分析数组
         * @var array $parse
         * @access protected
         */
        protected $parse = [];
        
        /**
         * 标签库是否有效
         * @var bool $valid
         * @deprecated
         * @access protected
         */
        protected $valid = false;
        
        /**
         * 当前模板对象
         * @var \Think\Template
         * @access protected
         */
        protected $tpl;
        
        protected $comparison = [
            ' nheq ' => ' !== ',
            ' heq '  => ' === ',
            ' neq '  => ' != ',
            ' eq '   => ' == ',
            ' egt '  => ' >= ',
            ' gt '   => ' > ',
            ' elt '  => ' <= ',
            ' lt '   => ' < ',
        ];
        
        /**
         * @access public
         */
        public function __construct()
        {
            $this->tagLib = strtolower(substr(get_class($this), 6));
            $this->tpl = Think::instance(\Think\Template::class);
        }
    
        /**
         * TagLib标签属性分析 返回标签属性数组
         * @access   public
         * @param $attr
         * @param $tag
         * @return array
         * @internal param string $tagStr 标签内容
         */
        public function parseXmlAttr($attr, $tag)
        {
            //XML解析安全过滤
            $attr = str_replace('&', '___', $attr);
            $xml = '<tpl><tag ' . $attr . ' /></tpl>';
            $xml = \simplexml_load_string($xml);
            if (!$xml) {
                E(L('_XML_TAG_ERROR_') . ' : ' . $attr);
            }
            $xml = (array)($xml->tag->attributes());
            if (isset($xml['@attributes'])) {
                $array = array_change_key_case($xml['@attributes']);
                if ($array) {
                    $tag = strtolower($tag);
                    if (!isset($this->tags[$tag])) {
                        // 检测是否存在别名定义
                        foreach ($this->tags as $val) {
                            if (isset($val['alias']) && in_array($tag, explode(',', $val['alias']))) {
                                $item = $val;
                                break;
                            }
                        }
                    } else {
                        $item = $this->tags[$tag];
                    }
                    $attrs = explode(',', $item['attr']);
                    if (isset($item['must'])) {
                        $must = explode(',', $item['must']);
                    } else {
                        $must = [];
                    }
                    foreach ($attrs as $name) {
                        if (isset($array[$name])) {
                            $array[$name] = str_replace('___', '&', $array[$name]);
                        } elseif (false !== array_search($name, $must)) {
                            E(L('_PARAM_ERROR_') . ':' . $name);
                        }
                    }
                    
                    return $array;
                }
            } else {
                return [];
            }
        }
        
        /**
         * 解析条件表达式
         * @access public
         * @param string $condition 表达式标签内容
         * @return array
         */
        public function parseCondition($condition)
        {
            $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
            $condition = preg_replace('/\$(\w+):(\w+)\s/is', '$\\1->\\2 ', $condition);
            switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
                // 识别为数组
                case 'array':
                    $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1["\\2"] ', $condition);
                    break;
                // 识别为对象
                case 'obj':
                    $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1->\\2 ', $condition);
                    break;
                // 自动判断数组或对象 只支持二维
                default:
                    $condition = preg_replace('/\$(\w+)\.(\w+)\s/is', '(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ', $condition);
            }
            //@todo remove in future
            if (false !== strpos($condition, '$Think')) {
                $condition = preg_replace_callback('/(\$Think.*?)\s/is', [$this, 'parseThinkVar'], $condition);
            }
            
            return $condition;
        }
        
        /**
         * 自动识别构建变量
         * @access public
         * @param string $name 变量描述
         * @return string
         */
        public function autoBuildVar($name)
        {
            /*
             * 特殊变量，指Think.xxx这样形式的，不过内置的变量有限
             */
            if ('Think.' == substr($name, 0, 6)) {
                return $this->parseThinkVar($name);
            } elseif (strpos($name, '.')) {
                $vars = explode('.', $name);
                $var = array_shift($vars);
                switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
                    // 识别为数组
                    case 'array':
                        $name = '$' . $var;
                        foreach ($vars as $key => $val) {
                            if (0 === strpos($val, '$')) {
                                $name .= '["{' . $val . '}"]';
                            } else {
                                $name .= '["' . $val . '"]';
                            }
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
            }
            /*
             * 移除$name:value这样的支持方式，统一$name.value By kwan 2016-9-13
             */
//            elseif (strpos($name, ':')) {
//                // 额外的对象方式支持
//                $name = '$' . str_replace(':', '->', $name);
//            }
            elseif (!defined($name)) {
                $name = '$' . $name;
            }
            
            return $name;
        }
        
        /**
         * 用于标签属性里面的特殊模板变量解析
         * @todo   与\Think\Template::parseThinkVar()重复定义了
         * 格式 以 Think. 打头的变量属于特殊模板变量
         * @deprecated
         * @access public
         * @param string $varStr 变量字符串
         * @return string
         */
        public function parseThinkVar($varStr)
        {
            //用于正则替换回调函数
            if (is_array($varStr)) {
                $varStr = $varStr[1];
            }
            $vars = explode('.', $varStr);
            $vars[1] = strtoupper(trim($vars[1]));
            $parseStr = '';
            if (count($vars) >= 3) {
                $vars[2] = trim($vars[2]);
                switch ($vars[1]) {
                    case 'SERVER':
                        $parseStr = '$_SERVER[\'' . $vars[2] . '\']';
                        break;
                    case 'GET':
                        $parseStr = '$_GET[\'' . $vars[2] . '\']';
                        break;
                    case 'POST':
                        $parseStr = '$_POST[\'' . $vars[2] . '\']';
                        break;
                    case 'COOKIE':
                        if (isset($vars[3])) {
                            $parseStr = '$_COOKIE[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                        } elseif (C('COOKIE_PREFIX')) {
                            $parseStr = '$_COOKIE[\'' . C('COOKIE_PREFIX') . $vars[2] . '\']';
                        } else {
                            $parseStr = '$_COOKIE[\'' . $vars[2] . '\']';
                        }
                        break;
                    case 'SESSION':
                        if (isset($vars[3])) {
                            $parseStr = '$_SESSION[\'' . $vars[2] . '\'][\'' . $vars[3] . '\']';
                        } elseif (C('SESSION_PREFIX')) {
                            $parseStr = '$_SESSION[\'' . C('SESSION_PREFIX') . '\'][\'' . $vars[2] . '\']';
                        } else {
                            $parseStr = '$_SESSION[\'' . $vars[2] . '\']';
                        }
                        break;
                    case 'ENV':
                        $parseStr = '$_ENV[\'' . $vars[2] . '\']';
                        break;
                    case 'REQUEST':
                        $parseStr = '$_REQUEST[\'' . $vars[2] . '\']';
                        break;
                    case 'CONST':
                        $parseStr = strtoupper($vars[2]);
                        break;
                    case 'LANG':
                        $parseStr = 'L("' . $vars[2] . '")';
                        break;
                    case 'CONFIG':
                        $parseStr = 'C("' . $vars[2] . '")';
                        break;
                    default:
                        break;
                }
            } elseif (count($vars) == 2) {
                switch ($vars[1]) {
                    case 'NOW':
                        $parseStr = "date('Y-m-d g:i a',time())";
                        break;
                    case 'VERSION':
                        $parseStr = 'THINK_VERSION';
                        break;
                    default:
                        if (defined($vars[1])) {
                            $parseStr = $vars[1];
                        }
                }
            }
            
            return $parseStr;
        }
        
        /**
         * 获取标签定义
         * @return array
         */
        public function getTags()
        {
            return $this->tags;
        }
    }