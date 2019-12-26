<?php
// +----------------------------------------------------------------------
// | 99PHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2018~2020 https://www.99php.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Mr.Chung <chung@99php.cn >
// +----------------------------------------------------------------------

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 费率模型
 * Class Auth
 * @package app\common\model
 */
class SysRate extends ModelService {


    /**
     * redis
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3360 ,
        'key'=> "String:table:SysRate:type:{type}:group_id:{group_id}:uid:{uid}:p_id:{p_id}:channel_id:{channel_id}:id:{id}",
        'keyArr'=> ['id','group_id','uid','p_id','channel_id','type'],
    ];


    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_system_rate';

    //代理和商户分组费率
    public function aList($page = 1, $limit = 10, $search = [], $where = []) {

        //搜索条件
        $searchField['eq'] = ['type','uid','status','channel_id','p_id'];

        $where = search($search,$searchField,$where);

        $product =  \app\common\model\PayProduct::idArr();
        $level =  \app\common\model\Ulevel::idArr();
        $channel =  \app\common\model\ChannelGroup::idArr();

        $field = 'id, uid, type,rate,update_at,p_id,status,group_id,channel_id';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['uid desc','update_at desc'])->select()->each(function ($item, $key) use ($product,$level,$channel) {
          if(!empty($item['p_id']))  $item['product'] = $product[$item['p_id']];
          if(!empty($item['group_id']))  $item['level'] = $level[$item['group_id']];
            if(!empty($item['channel_id']))  $item['channel'] = $channel[$item['channel_id']];
        });

        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $info = [
            'limit' => $limit,
            'page_current' => $page,
            'page_sum'     => ceil($count / $limit),
        ];
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => $info,
            'data'  => $data,
        ];
        return $list;
    }

    //个人费率列表
    public function uList($page = 1, $limit = 10, $search = [], $where = []) {

        //搜索条件
        $searchField['eq'] = ['type','uid','status'];

        $where = search($search,$searchField,$where);


        $product =  \app\common\model\PayProduct::idArr();

        $field = 'id, uid, type,rate,update_at,p_id,status';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['uid desc','update_at desc'])->select()->each(function ($item, $key) use ($product) {
            $item['product'] = $product[$item['p_id']];
        });

        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $info = [
            'limit'        => $limit,
            'page_current' => $page,
            'page_sum'     => ceil($count / $limit),
        ];
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => $info,
            'data'  => $data,
        ];
        return $list;
    }


}