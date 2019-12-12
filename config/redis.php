<?php

return [

    'host' => \think\facade\Env::get('redis.host'),//IP
    'port' => \think\facade\Env::get('database.port'),         //端口
    'database' => \think\facade\Env::get('database.database'),
    'password' => \think\facade\Env::get('database.password'),  //密码

];

