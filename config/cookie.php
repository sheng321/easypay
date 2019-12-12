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
// | Cookie设置
// +----------------------------------------------------------------------
return [
    // cookie 名称前缀
    'prefix'    => 'juhe_',
    // cookie 保存时间
    'expire'    => 0,
    // cookie 保存路径
    'path'      => '/',
    // cookie 有效域名
    'domain'    => '',
    //  cookie 启用安全传输  只有https 请求，cookie才会被携带。避免http的问题。
    'secure'    => false,
    // httponly设置  用js 读不到？只是会在发送时自动携带。避免针对cookie的XSS
    'httponly'  => true,
    // 是否使用 setcookie
    'setcookie' => true,
    // samesite只有在相同网站中才会填充cookie。避免CSRF
    'samesite' => "Strict"
];
