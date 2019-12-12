<?php

return [

    'host' => \think\facade\Env::get('redis.host'),//IP
    'port' => \think\facade\Env::get('redis.port'),         //端口
    'database' => \think\facade\Env::get('redis.database'),
    'password' => \think\facade\Env::get('redis.password'),  //密码

];

