<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 支付通道与通道分组与支付产品关联
 */
class ChannelProduct extends ModelService {
    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_channel_product';


    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [

        'ttl'=> 10,
        'key'=> "String:table:ChannelProduct:p_id:{p_id}:group_id:{group_id}:channel_id:{channel_id}:id:{id}",
        'keyArr'=> ['id','p_id','group_id','channel_id'],
    ];



}