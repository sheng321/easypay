<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 代理关联模型
 * Class Auth
 * @package app\common\model
 */
class Urelations extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_member_relations';

    /**
     * redis
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3360 ,
        'key'=> "String:table:Urelations:pid:{pid}:uid:{uid}:id:{id}",
        'keyArr'=> ['id','uid','pid'],
    ];





}