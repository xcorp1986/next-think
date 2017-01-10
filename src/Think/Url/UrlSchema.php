<?php
    namespace Think\Url;

    /**
     * Class UrlSchema
     * definition of url schema
     * 原url模式
     * @package Think\Url
     */
    class UrlSchema
    {
        /**
         * @const COMMON 普通模式
         */
        const COMMON = 0;
        /**
         * @const PATHINFO PATHINFO模式
         */
        const PATHINFO = 1;
        /**
         * @const REWRITE REWRITE模式
         */
        const REWRITE = 2;
        /**
         * @const COMPAT 兼容模式
         */
        const COMPAT = 3;
    }