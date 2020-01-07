php.ini
samesite只有在相同网站中才会填充cookie。避免CSRF
session.cookie_samesite=Strict

D:\phpStudy\WWW\www.test1.com\thinkphp\library\think\Cookie.php

setcookie ($name,$value,['expires'=>$expire, 'path'=>$option['path'], 'domain'=>$option['domain'], 'secure'=>$option['secure'], 'httponly'=>$option['httponly'],'samesite'=>$option['samesite']]);


$arr2 = array_column($arr, 'name');

$tempArr = array_column($arr, null, 'value');


2222222222







