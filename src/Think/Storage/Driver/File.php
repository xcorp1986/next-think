<?php


    namespace Think\Storage\Driver;

    use Think\Storage;

    /**
     * 本地文件存储类
     * Class File
     * @todo 重写成静态调用
     * @package Think\Storage\Driver
     */
    class File extends Storage
    {

        private static $contents = [];

        /**
         * 读取文件内容
         * @access public
         * @internal
         * @param string $filename 文件名
         * @return string
         */
        public static function read($filename)
        {
            return self::get($filename, 'content');
        }

        /**
         * 文件写入
         * @access public
         * @param string $filename 文件名
         * @param string $content  文件内容
         * @return bool
         */
        public static function put($filename, $content)
        {
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            if (false === file_put_contents($filename, $content)) {
                E(L('_STORAGE_WRITE_ERROR_') . ':' . $filename);
            } else {
                self::$contents[$filename] = $content;

                return true;
            }
        }

        /**
         * 文件追加写入
         * @access public
         * @param string $filename 文件名
         * @param string $content  追加的文件内容
         * @return bool
         */
        public static function append($filename, $content)
        {
            if (is_file($filename)) {
                $content = self::read($filename) . $content;
            }

            return self::put($filename, $content);
        }

        /**
         * 加载文件
         * @access public
         * @param string $filename 文件名
         * @param array  $vars     传入变量
         * @return void
         */
        public static function load($_filename, $vars = null)
        {
            if (!is_null($vars)) {
                extract($vars, EXTR_OVERWRITE);
            }
            include $_filename;
        }

        /**
         * 文件是否存在
         * @access public
         * @param string $filename 文件名
         * @param string $type
         * @return bool
         */
        public static function has($filename)
        {
            return is_file($filename);
        }

        /**
         * 文件删除
         * @access public
         * @param string $filename 文件名
         * @return bool
         */
        public static function unlink($filename)
        {
            unset(self::$contents[$filename]);

            return is_file($filename) ? unlink($filename) : false;
        }

        /**
         * 读取文件信息
         * @access public
         * @param string $filename 文件名
         * @param string $name     信息名 mtime或者content
         * @return bool
         */
        public static function get($filename, $name)
        {
            if (!isset(self::$contents[$filename])) {
                if (!is_file($filename)) return false;
                self::$contents[$filename] = file_get_contents($filename);
            }
            $content = self::$contents[$filename];
            $info = [
                'mtime'   => filemtime($filename),
                'content' => $content,
            ];

            return $info[$name];
        }
    }
