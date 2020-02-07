<?php
/**
 * 网站基础配置
 */
return [

    'host'=>'http://120.24.166.163',//首页
    'title'=>'什么付',//标题
    'link'=>'',//联系链接
    'qq'=>'1234567',//开户QQ

    'is_close'=> false,//网站是否维护
    'is_api'=> false,//是否禁止下单
    'noentry'=> '0',//是否禁止入账  1 禁止所有通道入账

    'memberid'=> '20100002',//测试商户号

    'Md5key'=> '5aa92e107b75d63b9d94da50de7f3d1300aa2b41',//测试秘钥
    'api'=>'http://120.24.166.163:66/pay.php/api',//支付网关
    'query'=>'http://120.24.166.163:66/pay.php/query',//网关


    'DfMd5key'=> '8cd55dfb5a322cf4bd5a0f7629275d195e6c7fee',//代付测试秘钥
    'df_api'=>'http://120.24.166.163:66/withdrawal.php/api',
    'df_qurey'=>'http://120.24.166.163:66/withdrawal.php/api',
    'df_balance'=>'http://120.24.166.163:66/withdrawal.php/api',

   // 'df_api'=>'http://www.test4.com/withdrawal.php/api',

];


