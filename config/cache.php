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

// +----------------------------------------------------------------------
// | 缓存设置
// +----------------------------------------------------------------------

/*return [

    // 缓存类型为File
    'type'  =>  'file',
    // 全局缓存有效期（0为永久有效）
    'expire'=>  3600,
    // 缓存前缀
    'prefix'=>  'think',
    // 缓存目录
    'path'  =>  '../runtime/cache/',

];*/

return [
    // 缓存配置为复合类型
    'type'  =>  'complex',
    'default'	=>	[
        'type'	=>	'redis',
        'host' => \think\facade\Env::get('redis.host'),//IP
        'port' => \think\facade\Env::get('redis.port'),         //端口
        'password' => \think\facade\Env::get('redis.password'),  //密码
        // 全局缓存有效期（0为永久有效）
        'expire'=>  36000,
        // 缓存前缀
        'prefix'=>  'think',
    ],
    'file'	=>	[
        'type'	=>	'File',
        // 全局缓存有效期（0为永久有效）
        'expire'=>  36000,
        // 缓存前缀
        'prefix'=>  'think',
        // 缓存目录
        'path'  =>  '../runtime/cache/',
    ],

];
