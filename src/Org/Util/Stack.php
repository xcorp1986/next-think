<?php


    namespace Org\Util;

    /**
     * Stack实现类
     */
    class Stack extends ArrayList
    {
        
        /**
         * 将堆栈的内部指针指向第一个单元
         * @access public
         * @return mixed
         */
        public function peek()
        {
            return reset($this->toArray());
        }

        /**
         * 元素进栈
         * @access public
         * @param mixed $value
         * @return mixed
         */
        public function push($value)
        {
            $this->add($value);

            return $value;
        }

    }
