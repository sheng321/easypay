<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: yunwuxin <448901948@qq.com>
// +----------------------------------------------------------------------
return [
    'connector'  => 'sync',		    // 可选驱动类型：sync(默认)、Redis、database、topthink等其他自定义类型
    'expire'     => 120,				    // 任务的过期时间，默认为60秒; 若要禁用，则设置为 null
    'default'    => 'default',		    // 默认的队列名称
    'host'       => \think\facade\Env::get('redis.host'),	    // redis 主机ip
    'port'       => \think\facade\Env::get('redis.port'),			    // redis 端口
    'password'   => \think\facade\Env::get('redis.password'),        // redis 连接密码
    'select'     => 3,				    // 使用哪一个 db，默认为 db0
    'timeout'    => 60,				    // redis连接的超时时间
    'persistent' => false,			    // 是否是长连接
];
