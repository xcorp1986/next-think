<?php
/**
 * 行为定义
 */

use Behavior\ContentReplaceBehavior;
use Behavior\ParseTemplateBehavior;
use Behavior\ReadHtmlCacheBehavior;
use Behavior\ShowPageTraceBehavior;
use Behavior\WriteHtmlCacheBehavior;

return [
    'app_init'        => [
    ],
    'app_begin'       => [
        // 读取静态缓存
        ReadHtmlCacheBehavior::class,
    ],
    'app_end'         => [
        // 页面Trace显示
        ShowPageTraceBehavior::class,
    ],
    'view_parse'      => [
        // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
        ParseTemplateBehavior::class,
    ],
    'template_filter' => [
        // 模板输出替换
        ContentReplaceBehavior::class,
    ],
    'view_filter'     => [
        // 写入静态缓存
        WriteHtmlCacheBehavior::class,
    ],
];