<?php


    namespace Think;

    /**
     * 加密解密类
     * Class Crypt
     * @package Think
     */
    class Crypt
    {

        private static $handler = '';

        public static function init($type = '')
        {
            $type = $type ?: C('DATA_CRYPT_TYPE');
            $class = strpos($type, '\\') ? $type : 'Think\\Crypt\\Driver\\' . ucwords(strtolower($type));
            self::$handler = $class;
        }

        /**
         * 加密字符串
         * @param string $str    字符串
         * @param string $key    加密key
         * @param int    $expire 有效期（秒） 0 为永久有效
         * @return string
         */
        public static function encrypt($data, $key, $expire = 0)
        {
            if (empty(self::$handler)) {
                self::init();
            }
            $class = self::$handler;

            return $class::encrypt($data, $key, $expire);
        }

        /**
         * 解密字符串
         * @param string $str 字符串
         * @param string $key 加密key
         * @return string
         */
        public static function decrypt($data, $key)
        {
            if (empty(self::$handler)) {
                self::init();
            }
            $class = self::$handler;

            return $class::decrypt($data, $key);
        }
    }