<?php

    namespace Behavior;

    use Think\Behavior;

    /**
     * 创建Lite运行文件
     * 可以替换框架入口文件运行
     * 建议绑定位置app_init
     * Class BuildLiteBehavior
     * @package Behavior
     * @deprecated
     */
    class BuildLiteBehavior extends Behavior
    {
        public function run(&$params)
        {
            if (!defined('BUILD_LITE_FILE')) {
                return;
            }
            $litefile = C('RUNTIME_LITE_FILE', null, RUNTIME_PATH . 'lite.php');
            if (is_file($litefile)) {
                return;
            }

            $defs = get_defined_constants(true);
            $content = 'namespace {$GLOBALS[\'_beginTime\'] = microtime(TRUE);';
            if (MEMORY_LIMIT_ON) {
                $content .= '$GLOBALS[\'_startUseMems\'] = memory_get_usage();';
            }

            // 生成数组定义
            unset($defs['user']['BUILD_LITE_FILE']);
            $content .= $this->buildArrayDefine($defs['user']) . '}';

            // 处理Think类的start方法
            $content = preg_replace('/\$runtimefile = RUNTIME_PATH(.+?)(if\(APP_STATUS)/', '\2', $content, 1);
            $content .= "\nnamespace { ";
            $content .= "\nL(" . var_export(L(), true) . ");
            \nC(" . var_export(C(), true) . ');
            \\Think\\Hook::import(' . var_export(\Think\Hook::get(), true) . ');
            \\Think\\Think::start();}';

            // 生成运行Lite文件
            file_put_contents($litefile, strip_whitespace('<?php ' . $content));
        }

        // 根据数组生成常量定义
        private function buildArrayDefine($array)
        {
            $content = "\n";
            foreach ($array as $key => $val) {
                $key = strtoupper($key);
                $content .= 'defined(\'' . $key . '\') or ';
                if (is_int($val) || is_float($val)) {
                    $content .= "define('" . $key . "'," . $val . ');';
                } elseif (is_bool($val)) {
                    $val = ($val) ? 'true' : 'false';
                    $content .= "define('" . $key . "'," . $val . ');';
                } elseif (is_string($val)) {
                    $content .= "define('" . $key . "','" . addslashes($val) . "');";
                }
                $content .= "\n";
            }

            return $content;
        }
    }