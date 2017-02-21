<?php
    /**
     * 行为定义
     */
    
    return [
        'app_init'        => [
        ],
        'app_begin'       => [
            // 读取静态缓存
            \Behavior\ReadHtmlCacheBehavior::class,
        ],
        'app_end'         => [
            // 页面Trace显示
            \Behavior\ShowPageTraceBehavior::class,
        ],
        'view_parse'      => [
            // 模板解析 支持PHP、内置模板引擎和第三方模板引擎
            \Behavior\ParseTemplateBehavior::class,
        ],
        'template_filter' => [
            // 模板输出替换
            \Behavior\ContentReplaceBehavior::class,
        ],
        'view_filter'     => [
            // 写入静态缓存
            \Behavior\WriteHtmlCacheBehavior::class,
        ],
    ];