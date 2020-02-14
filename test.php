<?php
//转换数组
$tempArr = array_column($arr, null, 'value');

//删除某个会员
$uid = 20100010;
\app\common\model\Umember::destroy(function($query) use ($uid){
    $query->where('uid','=',$uid);
});
\app\common\model\Uprofile::destroy(function($query)use ($uid){
    $query->where('uid','=',$uid);
});
\app\common\model\Umoney::destroy(function($query)use ($uid){
    $query->where('uid','=',$uid);
});









