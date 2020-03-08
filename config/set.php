<?php
/**
 * 网站基础配置
 */
return [
    'title'=>'什么付2',//标题
    'link'=>'',//联系链接
    'qq'=>'1234567',//开户QQ

    'is_close'=>'0',//网站是否维护
    'is_api'=>'0',//是否禁止下单
    'noentry'=>'0',//是否禁止入账  1 禁止所有通道入账

    'memberid'=> '20100002',//测试商户号
    'Md5key'=> '5aa92e107b75d63b9d94da50de7f3d1300aa2b41',//测试秘钥

    'api'=>'http://120.24.166.163:66/pay.php/api',//支付网关
    'query'=>'http://120.24.166.163:66/pay.php/query',//网关

     //'api'=>'http://www.test4.com/pay.php/api',//支付网关

    'DfMd5key'=> '4e10c1434813ac1ad65f82b608dbfe1f73204948',//代付测试秘钥
    'df_api'=>'http://120.24.166.163:66/withdrawal.php/api',
    'df_qurey'=>'http://120.24.166.163:66/withdrawal.php/query',
    'df_balance'=>'http://120.24.166.163:66/withdrawal.php/api',

   // 'df_api'=>'http://www.test4.com/withdrawal.php/api',

];


