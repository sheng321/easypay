<?php
//这里是下单借口
// [ 应用入口文件 ]
namespace think;

/*if (version_compare(PHP_VERSION, '7.3.0', 'lt')) {
    exit('PHP版本需要7.3以上~');
}*/

// 加载基础文件
require __DIR__ . '/../thinkphp/base.php';

// 支持事先使用静态方法设置Request对象和Config对象

// 执行应用并响应
Container::get('app')->bind('pay')->run()->send();
