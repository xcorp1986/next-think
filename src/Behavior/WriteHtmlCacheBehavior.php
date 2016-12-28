<?php


    namespace Behavior;

    use Think\Behavior;
    use Think\Storage;

    /**
     * 静态缓存写入
     */
    final class WriteHtmlCacheBehavior extends Behavior
    {

        /**
         * 执行入口
         * @param mixed $content
         */
        public function run(&$content)
        {
            //如果有HTTP 4xx 3xx 5xx 头部，禁止存储
            //对注入的网址 防止生成，例如
            // /game/lst/SortType/hot/-e8-90-8c-e5-85-94-e7-88-b1-e6-b6-88-e9-99-a4/-e8-bf-9b-e5-87-bb-e7-9a-84-e9-83-a8-e8-90-bd/-e9-a3-8e-e4-ba-91-e5-a4-a9-e4-b8-8b/index.shtml
            if (C('HTML_CACHE_ON') && defined('HTML_FILE_NAME')
                && !preg_match('/Status.*[345]{1}\d{2}/i', implode(' ', headers_list()))
                && !preg_match('/(-[a-z0-9]{2}){3,}/i', HTML_FILE_NAME)
            ) {
                //静态文件写入
                Storage::put(HTML_FILE_NAME, $content);
            }
        }
    }