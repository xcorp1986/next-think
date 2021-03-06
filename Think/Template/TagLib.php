<?php

namespace Think\Template;

use Think\BaseException;
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
     
     */
//        protected $tagList = [];

    /**
     * 标签库分析数组
     * @var array $parse
     
     */
//        protected $parse = [];

    /**
     * 标签库是否有效
     * @var bool $valid
     * @deprecated
     
     */
//        protected $valid = false;

    /**
     * 当前模板对象
     * @var \Think\Template
     
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
     * @return array
     * @throws BaseException
     */
    public function parseXmlAttr($attr = '', $tag = '')
    {
        //XML解析安全过滤
        $attr = str_replace('&', '___', $attr);
        $xml = '<tpl><tag '.$attr.' /></tpl>';
        $xml = \simplexml_load_string($xml);
        if (!$xml) {
            throw new BaseException(L('_XML_TAG_ERROR_').' : '.$attr);
        }
        /** @noinspection PhpUndefinedFieldInspection */
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
                        throw new BaseException(L('_PARAM_ERROR_').':'.$name);
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
     
     *
     * @param string $condition 表达式标签内容
     *
     * @return array
     */
    public function parseCondition($condition)
    {
        $condition = str_ireplace(array_keys($this->comparison), array_values($this->comparison), $condition);
        $condition = preg_replace('/\$(\w+):(\w+)\s/is', '$\\1->\\2 ', $condition);
        return preg_replace('/\$(\w+)\.(\w+)\s/is', '$\\1["\\2"] ', $condition);
    }

    /**
     * 自动识别构建变量
     
     *
     * @param string $name 变量描述
     *
     * @return string
     */
    public function autoBuildVar($name)
    {
        if (strpos($name, '.')) {
            $vars = explode('.', $name);
            $var = array_shift($vars);
            $name = '$'.$var;
            foreach ($vars as $key => $val) {
                if (0 === strpos($val, '$')) {
                    $name .= '["{'.$val.'}"]';
                } else {
                    $name .= '["'.$val.'"]';
                }
            }
        } elseif (!defined($name)) {
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