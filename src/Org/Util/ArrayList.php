<?php
    
    
    namespace Org\Util;
    /**
     * ArrayList实现类
     * @category    Think
     * @package     Think
     * @subpackage  Util
     */
    class ArrayList implements \IteratorAggregate
    {
        
        /**
         * 集合元素
         * @var array
         * @access protected
         */
        protected $_elements = [];
        
        /**
         * @access public
         * @param array $elements 初始化数组元素
         */
        public function __construct($elements = [])
        {
            if (!empty($elements)) {
                $this->_elements = $elements;
            }
        }
        
        /**
         * 若要获得迭代因子，通过getIterator方法实现
         * @access public
         * @return \ArrayObject
         */
        public function getIterator()
        {
            return new \ArrayObject($this->_elements);
        }
        
        /**
         * 增加元素
         * @access public
         * @param mixed $element 要添加的元素
         * @return bool
         */
        public function add($element)
        {
            return (array_push($this->_elements, $element)) ? true : false;
        }
    
        /**
         * @param $element
         * @return bool
         */
        public function unshift($element)
        {
            return (array_unshift($this->_elements, $element)) ? true : false;
        }
    
        /**
         * @return mixed
         */
        public function pop()
        {
            return array_pop($this->_elements);
        }
        
        /**
         * 增加元素列表
         * @access public
         * @param ArrayList $list 元素列表
         * @return bool
         */
        public function addAll($list)
        {
            $before = $this->size();
            foreach ($list as $element) {
                $this->add($element);
            }
            $after = $this->size();
            
            return ($before < $after);
        }
        
        /**
         * 清除所有元素
         * @access public
         */
        public function clear()
        {
            $this->_elements = [];
        }
        
        /**
         * 是否包含某个元素
         * @access public
         * @param mixed $element 查找元素
         * @return string
         */
        public function contains($element)
        {
            return (array_search($element, $this->_elements) !== false);
        }
        
        /**
         * 根据索引取得元素
         * @access public
         * @param int $index 索引
         * @return mixed
         */
        public function get($index)
        {
            return $this->_elements[$index];
        }
        
        /**
         * 查找匹配元素，并返回第一个元素所在位置
         * 注意 可能存在0的索引位置 因此要用===False来判断查找失败
         * @access public
         * @param mixed $element 查找元素
         * @return int
         */
        public function indexOf($element)
        {
            return array_search($element, $this->_elements);
        }
        
        /**
         * 判断元素是否为空
         * @access public
         * @return bool
         */
        public function isEmpty()
        {
            return empty($this->_elements);
        }
        
        /**
         * 最后一个匹配的元素位置
         * @access public
         * @param mixed $element 查找元素
         * @return int
         */
        public function lastIndexOf($element)
        {
            for ($i = (count($this->_elements) - 1); $i > 0; $i--) {
                if ($element == $this->get($i)) {
                    return $i;
                }
            }
        }
    
        /**
         * @return string
         */
        public function toJson()
        {
            return json_encode($this->_elements);
        }
        
        /**
         * 根据索引移除元素
         * 返回被移除的元素
         * @access public
         * @param int $index 索引
         * @return mixed
         */
        public function remove($index)
        {
            $element = $this->get($index);
            if (!is_null($element)) {
                array_splice($this->_elements, $index, 1);
            }
            
            return $element;
        }
        
        /**
         * 移出一定范围的数组列表
         * @access public
         * @param int $offset 开始移除位置
         * @param int $length 移除长度
         */
        public function removeRange($offset, $length)
        {
            array_splice($this->_elements, $offset, $length);
        }
        
        /**
         * 移出重复的值
         * @access public
         */
        public function unique()
        {
            $this->_elements = array_unique($this->_elements);
        }
    
        /**
         * 取出一定范围的数组列表
         * @access public
         * @param int $offset 开始位置
         * @param int $length 长度
         * @return array
         */
        public function range($offset, $length = null)
        {
            return array_slice($this->_elements, $offset, $length);
        }
        
        /**
         * 设置列表元素
         * 返回修改之前的值
         * @access public
         * @param int   $index   索引
         * @param mixed $element 元素
         * @return mixed
         */
        public function set($index, $element)
        {
            $previous = $this->get($index);
            $this->_elements[$index] = $element;
            
            return $previous;
        }
        
        /**
         * 获取列表长度
         * @access public
         * @return int
         */
        public function size()
        {
            return count($this->_elements);
        }
        
        /**
         * 转换成数组
         * @access public
         * @return array
         */
        public function toArray()
        {
            return $this->_elements;
        }
    
        /**
         * 列表排序
         */
        public function ksort()
        {
            ksort($this->_elements);
        }
    
        /**
         * 列表排序
         */
        public function asort()
        {
            asort($this->_elements);
        }
    
        /**
         * 逆向排序
         */
        public function rsort()
        {
            rsort($this->_elements);
        }
    
        /**
         * 自然排序
         */
        public function natsort()
        {
            natsort($this->_elements);
        }
        
    }
