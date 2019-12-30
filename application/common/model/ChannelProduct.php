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

}