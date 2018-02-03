<?php


namespace Think\Storage\Driver;

use Think\BaseException;
use Think\Storage;

/**
 * 本地文件存储类
 * Class File
 * @package Think\Storage\Driver
 */
class File extends Storage
{
    /**
     * @var array
     */
    private static $contents = [];

    /**
     * 读取文件内容
     
     * @internal
     *
     * @param string $filename 文件名
     *
     * @return string
     */
    public static function read($filename)
    {
        return static::get($filename, 'content');
    }

    /**
     * 文件写入
     
     *
     * @param string $filename 文件名
     * @param string $content 文件内容
     * @return bool
     * @throws BaseException
     */
    public static function put($filename, $content)
    {
        $dir = dirname($filename);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        if (false === file_put_contents($filename, $content)) {
            throw new BaseException(L('_STORAGE_WRITE_ERROR_').':'.$filename);
        } else {
            static::$contents[$filename] = $content;

            return true;
        }
    }

    /**
     * 文件追加写入
     
     *
     * @param string $filename 文件名
     * @param string $content 追加的文件内容
     *
     * @return bool
     */
    public static function append($filename, $content)
    {
        if (is_file($filename)) {
            $content = static::read($filename).$content;
        }

        return static::put($filename, $content);
    }

    /**
     * 加载文件
     
     *
     * @param string $_filename 文件名
     * @param array $vars 传入变量
     *
     * @return void
     */
    public static function load($_filename, $vars = null)
    {
        if (!is_null($vars)) {
            extract($vars, EXTR_OVERWRITE);
        }
        /** @noinspection PhpIncludeInspection */
        include $_filename;
    }

    /**
     * 文件是否存在
     
     *
     * @param string $filename 文件名
     *
     * @return bool
     */
    public static function has($filename)
    {
        return is_file($filename);
    }

    /**
     * 文件删除
     
     *
     * @param string $filename 文件名
     *
     * @return bool
     */
    public static function unlink($filename)
    {
        unset(static::$contents[$filename]);

        return is_file($filename) ? unlink($filename) : false;
    }

    /**
     * 读取文件信息
     
     *
     * @param string $filename 文件名
     * @param string $name 信息名 mtime或者content
     *
     * @return bool
     */
    public static function get($filename, $name)
    {
        if (!isset(static::$contents[$filename])) {
            if (!is_file($filename)) {
                return false;
            }
            static::$contents[$filename] = file_get_contents($filename);
        }
        $content = static::$contents[$filename];
        $info = [
            'mtime'   => filemtime($filename),
            'content' => $content,
        ];

        return $info[$name];
    }
}
