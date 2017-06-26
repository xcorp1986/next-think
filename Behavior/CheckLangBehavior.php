<?php


namespace Behavior;

use Think\Behavior;

/**
 * 语言检测 并自动加载语言包
 */
final class CheckLangBehavior extends Behavior
{

    /**
     * 执行入口
     *
     * @param mixed $params
     */
    public function run(&$params)
    {
        self::_checkLanguage();
    }

    /**
     * 语言检查
     * 检查浏览器支持语言，并自动加载语言包
     * @access private
     * @return void
     */
    private static function _checkLanguage()
    {
        // 不开启语言包功能，仅仅加载框架语言文件直接返回
        if (!C('LANG_SWITCH_ON', null, false)) {
            return;
        }
        $langSet = C('DEFAULT_LANG');
        $varLang = C('VAR_LANGUAGE', null, 'l');
        $langList = C('LANG_LIST', null, 'zh-cn');
        // 启用了语言包功能
        // 根据是否启用自动侦测设置获取语言选择
        $_identity = md5('__language__');
        if (C('LANG_AUTO_DETECT', null, true)) {
            if (isset($_GET[$varLang])) {
                // url中设置了语言变量
                $langSet = $_GET[$varLang];
                cookie($_identity, $langSet, 3600);
            } elseif (cookie($_identity)) {
                // 获取上次用户的选择
                $langSet = cookie($_identity);
            } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                // 自动侦测浏览器语言
                preg_match('/^([a-z\d\-]+)/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches);
                $langSet = $matches[1];
                cookie($_identity, $langSet, 3600);
            }
            if (false === stripos($langList, $langSet)) {
                // 非法语言参数
                $langSet = C('DEFAULT_LANG');
            }
        }
        // 定义当前语言
        define('LANG_SET', strtolower($langSet));

        // 读取框架语言包
        $file = __DIR__.'/../Lang/'.LANG_SET.'.php';
        if (LANG_SET != C('DEFAULT_LANG') && is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            L(include $file);
        }

        // 读取应用公共语言包
        $file = LANG_PATH.LANG_SET.'.php';
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            L(include $file);
        }

        // 读取模块语言包
        $file = MODULE_PATH.'Lang/'.LANG_SET.'.php';
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            L(include $file);
        }

        // 读取当前控制器语言包
        $file = MODULE_PATH.'Lang/'.LANG_SET.'/'.strtolower(CONTROLLER_NAME).'.php';
        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            L(include $file);
        }
    }
}
