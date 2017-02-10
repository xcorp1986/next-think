<?php
    
    
    namespace Think\Template\TagLib;
    
    use Think\Template\TagLib;
    
    /**
     * CX标签库解析类
     */
    class Cx extends TagLib
    {
    
        /**
         * 标签定义
         * @var array $tags
         */
        protected $tags = [
            // 标签定义： attr 属性列表 close 是否闭合（0 或者1 默认1） alias 标签别名 level 嵌套层次
            'php'        => [],
            'volist'     => ['attr' => 'name,id,offset,length,key,mod', 'level' => 3, 'alias' => 'iterate'],
            'foreach'    => ['attr' => 'name,item,key', 'level' => 3],
            'if'         => ['attr' => 'condition', 'level' => 2],
            'elseif'     => ['attr' => 'condition', 'close' => 0],
            'else'       => ['attr' => '', 'close' => 0],
            'switch'     => ['attr' => 'name', 'level' => 2],
            'case'       => ['attr' => 'value,break'],
            'default'    => ['attr' => '', 'close' => 0],
            'compare'    => ['attr' => 'name,value,type', 'level' => 3, 'alias' => 'eq,equal,notequal,neq,gt,lt,egt,elt,heq,nheq'],
            'range'      => ['attr' => 'name,value,type', 'level' => 3, 'alias' => 'in,notin,between,notbetween'],
            'empty'      => ['attr' => 'name', 'level' => 3],
            'notempty'   => ['attr' => 'name', 'level' => 3],
            'present'    => ['attr' => 'name', 'level' => 3],
            'notpresent' => ['attr' => 'name', 'level' => 3],
            'defined'    => ['attr' => 'name', 'level' => 3],
            'notdefined' => ['attr' => 'name', 'level' => 3],
            'assign'     => ['attr' => 'name,value', 'close' => 0],
            'define'     => ['attr' => 'name,value', 'close' => 0],
            'for'        => ['attr' => 'start,end,name,comparison,step', 'level' => 3],
        ];
        
        /**
         * php标签解析
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _php($tag, $content)
        {
            return '<?php ' . $content . ' ?>';
        }
        
        /**
         * volist标签解析 循环输出数据集
         * 格式：
         * <volist name="userList" id="user" empty="" >
         * {user.username}
         * {user.email}
         * </volist>
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _volist(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            $id = $tag['id'];
            $empty = isset($tag['empty']) ? $tag['empty'] : '';
            $key = !empty($tag['key']) ? $tag['key'] : 'i';
            $mod = isset($tag['mod']) ? $tag['mod'] : '2';
            // 允许使用函数设定数据集 <volist name=":fun('arg')" id="vo">{$vo.name}</volist>
            $parseStr = '<?php ';
            if (0 === strpos($name, ':')) {
                $parseStr .= '$_result=' . substr($name, 1) . ';';
                $name = '$_result';
            } else {
                $name = $this->autoBuildVar($name);
            }
            $parseStr .= 'if(is_array(' . $name . ')): $' . $key . ' = 0;';
            if (isset($tag['length']) && '' != $tag['length']) {
                $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $tag['offset'] . ',' . $tag['length'] . ',true);';
            } elseif (isset($tag['offset']) && '' != $tag['offset']) {
                $parseStr .= ' $__LIST__ = array_slice(' . $name . ',' . $tag['offset'] . ',null,true);';
            } else {
                $parseStr .= ' $__LIST__ = ' . $name . ';';
            }
            $parseStr .= 'if( count($__LIST__)==0 ) : echo "' . $empty . '" ;';
            $parseStr .= 'else: ';
            $parseStr .= 'foreach($__LIST__ as $key=>$' . $id . '): ';
            $parseStr .= '$mod = ($' . $key . ' % ' . $mod . ' );';
            $parseStr .= '++$' . $key . ';?>';
            $parseStr .= $this->tpl->parse($content);
            $parseStr .= '<?php endforeach; endif; else: echo "' . $empty . '" ;endif; ?>';
            $parseStr .= '<?php unset('.$name.');?>';
            
            return $parseStr;
        }
        
        /**
         * foreach标签解析 循环输出数据集
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _foreach(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            $item = $tag['item'];
            $key = !empty($tag['key']) ? $tag['key'] : 'key';
            $name = $this->autoBuildVar($name);
            $parseStr = '<?php if(is_array(' . $name . ')): foreach(' . $name . ' as $' . $key . '=>$' . $item . '): ?>';
            $parseStr .= $this->tpl->parse($content);
            $parseStr .= '<?php endforeach; endif; ?>';
            $parseStr .= '<?php unset('.$name.');?>';
            
            if (!empty($parseStr)) {
                return $parseStr;
            }
            
            return '';
        }
        
        /**
         * if标签解析
         * 格式：
         * <if condition=" $a eq 1" >
         * <elseif condition="$a eq 2" />
         * <else />
         * </if>
         * 表达式支持 eq neq gt egt lt elt == > >= < <= or and || &&
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _if(array $tag = [], $content = '')
        {
            $condition = $this->parseCondition($tag['condition']);
            
            return '<?php if(' . $condition . '): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * else标签解析
         * 格式：见if标签
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _elseif($tag, $content)
        {
            $condition = $this->parseCondition($tag['condition']);
            
            return '<?php elseif(' . $condition . '): ?>';
        }
        
        /**
         * else标签解析
         * @access public
         * @param array $tag 标签属性
         * @return string
         */
        public function _else($tag)
        {
            return '<?php else: ?>';
        }
        
        /**
         * switch标签解析
         * 格式：
         * <switch name="a.name" >
         * <case value="1" break="false">1</case>
         * <case value="2" >2</case>
         * <default />other
         * </switch>
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _switch(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            $varArray = explode('|', $name);
            $name = array_shift($varArray);
            $name = $this->autoBuildVar($name);
            if (count($varArray) > 0) {
                $name = $this->tpl->parseVarFunction($name, $varArray);
            }
            
            return '<?php switch(' . $name . '): ?>' . $content . '<?php endswitch;?>';
        }
        
        /**
         * case标签解析 需要配合switch才有效
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _case(array $tag = [], $content = '')
        {
            $value = $tag['value'];
            if ('$' == substr($value, 0, 1)) {
                $varArray = explode('|', $value);
                $value = array_shift($varArray);
                $value = $this->autoBuildVar(substr($value, 1));
                if (count($varArray) > 0) {
                    $value = $this->tpl->parseVarFunction($value, $varArray);
                }
                $value = 'case ' . $value . ': ';
            } elseif (strpos($value, '|')) {
                $values = explode('|', $value);
                $value = '';
                foreach ($values as $val) {
                    $value .= 'case "' . addslashes($val) . '": ';
                }
            } else {
                $value = 'case "' . $value . '": ';
            }
            $parseStr = '<?php ' . $value . ' ?>' . $content;
            $isBreak = isset($tag['break']) ? $tag['break'] : '';
            if ('' == $isBreak || $isBreak) {
                $parseStr .= '<?php break;?>';
            }
            
            return $parseStr;
        }
        
        /**
         * default标签解析 需要配合switch才有效
         * 使用： <default />some value
         * @access public
         * @param array  $tag     标签属性
         * @return string
         */
        public function _default($tag)
        {
            return '<?php default: ?>';
        }
        
        /**
         * compare标签解析
         * 用于值的比较 支持 eq neq gt lt egt elt heq nheq 默认是eq
         * 格式： <compare name="" type="eq" value="" >content</compare>
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @param string $type
         * @return string
         */
        public function _compare(array $tag = [], $content = '', $type = 'eq')
        {
            $name = $tag['name'];
            $value = $tag['value'];
            $type = isset($tag['type']) ? $tag['type'] : $type;
            $type = $this->parseCondition(' ' . $type . ' ');
            $varArray = explode('|', $name);
            $name = array_shift($varArray);
            $name = $this->autoBuildVar($name);
            if (count($varArray) > 0) {
                $name = $this->tpl->parseVarFunction($name, $varArray);
            }
            if ('$' == substr($value, 0, 1)) {
                $value = $this->autoBuildVar(substr($value, 1));
            } else {
                $value = '"' . $value . '"';
            }
            
            return '<?php if((' . $name . ') ' . $type . ' ' . $value . '): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * <EQ>...</EQ>
         * @param $tag
         * @param $content
         * @return string
         */
        public function _eq(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'eq');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _equal(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'eq');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _neq(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'neq');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _notequal(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'neq');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _gt(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'gt');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _lt(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'lt');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _egt(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'egt');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _elt(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'elt');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _heq(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'heq');
        }
        
        /**
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _nheq(array $tag = [], $content = '')
        {
            return $this->_compare($tag, $content, 'nheq');
        }
        
        /**
         * range标签解析
         * 如果某个变量存在于某个范围 则输出内容 type= in 表示在范围内 否则表示在范围外
         * 格式： <range name="var|function"  value="val" type='in|notin' >content</range>
         * example: <range name="a"  value="1,2,3" type='in' >content</range>
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @param string $type    比较类型
         * @return string
         */
        public function _range(array $tag = [], $content = '', $type = 'in')
        {
            $name = $tag['name'];
            $value = $tag['value'];
            $varArray = explode('|', $name);
            $name = array_shift($varArray);
            $name = $this->autoBuildVar($name);
            if (count($varArray) > 0) {
                $name = $this->tpl->parseVarFunction($name, $varArray);
            }
            
            $type = isset($tag['type']) ? $tag['type'] : $type;
            
            if ('$' == substr($value, 0, 1)) {
                $value = $this->autoBuildVar(substr($value, 1));
                $str = 'is_array(' . $value . ')?' . $value . ':explode(\',\',' . $value . ')';
            } else {
                $value = '"' . $value . '"';
                $str = 'explode(\',\',' . $value . ')';
            }
            if ($type == 'between') {
                $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '>= $_RANGE_VAR_[0] && ' . $name . '<= $_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
            } elseif ($type == 'notbetween') {
                $parseStr = '<?php $_RANGE_VAR_=' . $str . ';if(' . $name . '<$_RANGE_VAR_[0] || ' . $name . '>$_RANGE_VAR_[1]):?>' . $content . '<?php endif; ?>';
            } else {
                $fun = ($type == 'in') ? 'in_array' : '!in_array';
                $parseStr = '<?php if(' . $fun . '((' . $name . '), ' . $str . ')): ?>' . $content . '<?php endif; ?>';
            }
            
            return $parseStr;
        }
        
        /**
         * range标签的别名 用于in判断
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _in(array $tag = [], $content = '')
        {
            return $this->_range($tag, $content, 'in');
        }
        
        /**
         * range标签的别名 用于notin判断
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _notin(array $tag = [], $content = '')
        {
            return $this->_range($tag, $content, 'notin');
        }
        
        /**
         * 判断变量是否在某个区间范围内
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _between(array $tag = [], $content = '')
        {
            return $this->_range($tag, $content, 'between');
        }
        
        /**
         * 判断变量是否不在某个区间范围内
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _notbetween(array $tag = [], $content = '')
        {
            return $this->_range($tag, $content, 'notbetween');
        }
        
        /**
         * present标签解析
         * 如果某个变量已经设置 则输出内容
         * 格式： <present name="" >content</present>
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _present(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            $name = $this->autoBuildVar($name);
            
            return '<?php if(isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * notpresent标签解析
         * 如果某个变量没有设置，则输出内容
         * 格式： <notpresent name="" >content</notpresent>
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _notpresent(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            $name = $this->autoBuildVar($name);
            
            return '<?php if(!isset(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * empty标签解析
         * 如果某个变量为empty则输出内容
         * 格式： <empty name="" >content</empty>
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _empty(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            $name = $this->autoBuildVar($name);
            
            return '<?php if(empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * 如果某个变量不为empty则输出内容
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _notempty(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            $name = $this->autoBuildVar($name);
            
            return '<?php if(!empty(' . $name . ')): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * 判断是否已经定义了该常量
         * <defined name='TXT'>已定义</defined>
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _defined(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            
            return '<?php if(defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * 判断是否未某常量
         * @param array  $tag
         * @param string $content
         * @return string
         */
        public function _notdefined(array $tag = [], $content = '')
        {
            $name = $tag['name'];
            
            return '<?php if(!defined("' . $name . '")): ?>' . $content . '<?php endif; ?>';
        }
        
        /**
         * assign标签解析
         * 在模板中给某个变量赋值 支持变量赋值
         * 格式： <assign name="" value="" />
         * @deprecated
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _assign($tag, $content)
        {
            $name = $this->autoBuildVar($tag['name']);
            if ('$' == substr($tag['value'], 0, 1)) {
                $value = $this->autoBuildVar(substr($tag['value'], 1));
            } else {
                $value = '\'' . $tag['value'] . '\'';
            }
            
            return '<?php ' . $name . ' = ' . $value . '; ?>';
        }
        
        /**
         * define标签解析
         * 在模板中定义常量 支持变量赋值
         * 格式： <define name="" value="" />
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _define($tag, $content)
        {
            $name = '\'' . $tag['name'] . '\'';
            if ('$' == substr($tag['value'], 0, 1)) {
                $value = $this->autoBuildVar(substr($tag['value'], 1));
            } else {
                $value = '\'' . $tag['value'] . '\'';
            }
            
            return '<?php define(' . $name . ', ' . $value . '); ?>';
        }
        
        /**
         * for标签解析
         * 格式： <for start="" end="" comparison="" step="" name="" />
         * @access public
         * @param array  $tag     标签属性
         * @param string $content 标签内容
         * @return string
         */
        public function _for(array $tag = [], $content = '')
        {
            //设置默认值
            $start = 0;
            $end = 0;
            $step = 1;
            $comparison = 'lt';
            $name = 'i';
            //添加随机数，防止嵌套变量冲突
            $rand = mt_rand();
            //获取属性
            foreach ($tag as $key => $value) {
                $value = trim($value);
                if (':' == substr($value, 0, 1)) {
                    $value = substr($value, 1);
                } elseif ('$' == substr($value, 0, 1)) {
                    $value = $this->autoBuildVar(substr($value, 1));
                }
                switch ($key) {
                    case 'start':
                        $start = $value;
                        break;
                    case 'end' :
                        $end = $value;
                        break;
                    case 'step':
                        $step = $value;
                        break;
                    case 'comparison':
                        $comparison = $value;
                        break;
                    case 'name':
                        $name = $value;
                        break;
                    default:
                        break;
                }
            }
            
            $parseStr = '<?php $__FOR_START_' . $rand . '__=' . $start . ';$__FOR_END_' . $rand . '__=' . $end . ';';
            $parseStr .= 'for($' . $name . '=$__FOR_START_' . $rand . '__;' . $this->parseCondition('$' . $name . ' ' . $comparison . ' $__FOR_END_' . $rand . '__') . ';$' . $name . '+=' . $step . '){ ?>';
            $parseStr .= $content;
            return $parseStr.'<?php } ?>';
        }
        
    }
