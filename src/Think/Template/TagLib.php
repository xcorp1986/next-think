<?php
    namespace Think\Template;
    
    use Think\Template;

    /**
     * 标签库TagLib解析基类
     * @property-write array $tags 标签定义
     * @see \Think\Template\TagLib\Cx
     * @see \Think\Template\TagLib\Html
     */
    class TagLib
    {
        
        /**
         * @var array $tags 标签定义
         */
        protected $tags = [];
        
        /**
         * 标签库标签列表
         * @var array $tagList
         * @access protected
         */
//        protected $tagList = [];
        
        /**
         * 标签库分析数组
         * @var array $parse
         * @access protected
         */
//        protected $parse = [];
        
        /**
         * 标签库是否有效
         * @var bool $valid
         * @deprecated
         * @access protected
         */
//        protected $valid = false;
        
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
            $this->tpl = new Template;
        }
        
        /**
         * TagLib标签属性分析 返回标签属性数组
         * @access   public
         *
         * @param string $attr
         * @param string $tag
         *
         * @return array
         */
        public function parseXmlAttr($attr = '', $tag = '')
        {
            //XML解析安全过滤
            $attr = str_replace('&', '___', $attr);
            $xml  = '<tpl><tag '.$attr.' /></tpl>';
            $xml  = \simplexml_load_string($xml);
            if ( ! $xml) {
                E(L('_XML_TAG_ERROR_').' : '.$attr);
            }
            /** @noinspection PhpUndefinedFieldInspection */
            $xml = (array)($xml->tag->attributes());
            if (isset($xml['@attributes'])) {
                $array = array_change_key_case($xml['@attributes']);
                if ($array) {
                    $tag = strtolower($tag);
                    if ( ! isset($this->tags[$tag])) {
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
                            E(L('_PARAM_ERROR_').':'.$name);
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
         *
         * @param string $condition 表达式标签内容
         *
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
                    $condition = preg_replace(
                        '/\$(\w+)\.(\w+)\s/is',
                        '(is_array($\\1)?$\\1["\\2"]:$\\1->\\2) ',
                        $condition
                    );
            }
            
            return $condition;
        }
        
        /**
         * 自动识别构建变量
         * @access public
         *
         * @param string $name 变量描述
         *
         * @return string
         */
        public function autoBuildVar($name)
        {
            if (strpos($name, '.')) {
                $vars = explode('.', $name);
                $var  = array_shift($vars);
                switch (strtolower(C('TMPL_VAR_IDENTIFY'))) {
                    // 识别为数组
                    case 'array':
                        $name = '$'.$var;
                        foreach ($vars as $key => $val) {
                            if (0 === strpos($val, '$')) {
                                $name .= '["{'.$val.'}"]';
                            } else {
                                $name .= '["'.$val.'"]';
                            }
                        }
                        break;
                    // 识别为对象
                    case 'obj':
                        $name = '$'.$var;
                        foreach ($vars as $key => $val) {
                            $name .= '->'.$val;
                        }
                        break;
                    // 自动判断数组或对象 只支持二维
                    default:
                        $name = 'is_array($'.$var.')?$'.$var.'["'.$vars[0].'"]:$'.$var.'->'.$vars[0];
                }
            } elseif ( ! defined($name)) {
                $name = '$'.$name;
            }
            
            return $name;
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