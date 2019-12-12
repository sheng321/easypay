<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用行为扩展定义文件
return [
    // 应用初始化
    'app_init'     => [],
    // 应用开始
    'app_begin'    => [],
    // 模块初始化
    'module_init'  => [],
    // 操作开始执行
    'action_begin' => function () {
        //声明模板常量，保证在修改后台模块名的时候可以正常访问
        list($thisModule, $thisController, $thisAction) = [app('request')->module(), app('request')->controller(), app('request')->action()];
        $thisClass = parseNodeStr("{$thisModule}/{$thisController}");
        $thisRequest = parseNodeStr("{$thisModule}/{$thisController}/{$thisAction}");
        app('view')->init(config('template.'))->assign([
            'thisModule'     => $thisModule,
            'thisController' => $thisController,
            'thisClass'      => $thisClass,
            'thisRequest'    => $thisRequest,
        ]);
    },
    // 视图内容过滤
    'view_filter'  => [],
    // 日志写入
    'log_write'    => [],
    // 应用结束
    'app_end'      => [],
];
