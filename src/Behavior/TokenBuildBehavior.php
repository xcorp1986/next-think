<?php


    namespace Behavior;

    use Think\Behavior;

    /**
     * 表单令牌生成
     */
    final class TokenBuildBehavior extends Behavior
    {

        /**
         * 执行入口
         * @param mixed $content
         */
        public function run(&$content)
        {
            if (C('TOKEN_ON')) {
                list($tokenName, $tokenKey, $tokenValue) = self::_getToken();
                $input_token = '<input type="hidden" name="' . $tokenName . '" value="' . $tokenKey . '_' . $tokenValue . '" />';
                $meta_token = '<meta name="' . $tokenName . '" content="' . $tokenKey . '_' . $tokenValue . '" />';
                if (strpos($content, '{__TOKEN__}')) {
                    // 指定表单令牌隐藏域位置
                    $content = str_replace('{__TOKEN__}', $input_token, $content);
                } elseif (preg_match('/<\/form(\s*)>/is', $content, $match)) {
                    // 智能生成表单令牌隐藏域
                    $content = str_replace($match[0], $input_token . $match[0], $content);
                }
                $content = str_ireplace('</head>', $meta_token . '</head>', $content);
            } else {
                $content = str_replace('{__TOKEN__}', '', $content);
            }
        }

        /**
         * 获得token
         * @return array
         */
        private static function _getToken()
        {
            $tokenName = C('TOKEN_NAME', null, '__hash__');
            $tokenType = C('TOKEN_TYPE', null, 'md5');
            if (!isset($_SESSION[$tokenName])) {
                $_SESSION[$tokenName] = [];
            }
            // 标识当前页面唯一性
            $tokenKey = md5($_SERVER['REQUEST_URI']);
            if (isset($_SESSION[$tokenName][$tokenKey])) {
                // 相同页面不重复生成session
                $tokenValue = $_SESSION[$tokenName][$tokenKey];
            } else {
                $tokenValue = is_callable($tokenType) ? $tokenType(microtime(true)) : md5(microtime(true));
                $_SESSION[$tokenName][$tokenKey] = $tokenValue;
                //ajax需要获得这个header并替换页面中meta中的token值
                if (IS_AJAX && C('TOKEN_RESET', null, true)) {
                    header($tokenName . ': ' . $tokenKey . '_' . $tokenValue);
                }
            }

            return [$tokenName, $tokenKey, $tokenValue];
        }
    }