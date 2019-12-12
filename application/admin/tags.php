<?php

// 应用行为扩展定义文件
return [
    // 应用初始化
    'app_init'     => [],
    // 应用开始
    'app_begin'    => [],
    // 模块初始化
    'module_init'  => function () {
        if (app('request')->module() == 'admin') {

            //缓存系统配置信息
            $SysInfo = \app\common\model\SysConfig::getSysConfig();

            //口令
            $word = \app\common\model\SysNode::wordArr();

            //渲染到视图层
            app('view')->init(config('template.'))->assign([
                'SysInfo'   => $SysInfo,
                'SysWord'   => json_encode($word),
            ]);
        }
    },
    // 操作开始执行
    'action_begin' => ['\\app\\admin\\behavior\\intBehavior'],
    // 视图内容过滤
    'view_filter'  => [],
    // 日志写入
    'log_write'    => [],
    // 应用结束
    'app_end'      => [],
];
