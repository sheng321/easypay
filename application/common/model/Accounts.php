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
use think\Db;

/**
 * 金额对账模型
 * Class Auth
 * @package app\common\model
 */
class Accounts extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_accounts';

    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [

        'ttl'=> 3,
        'key'=> "String:table:Accounts:day:{day}:withdraw_id:{withdraw_id}:df_id:{df_id}:channel_id:{channel_id}:uid:{uid}:id:{id}",
        'keyArr'=> ['id','uid','channel_id','df_id','day'],
    ];

    /**
     * 获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = []) {
        $where = [];

        if(!empty($search['day'])) $search['day'] = date('Y-m-d',strtotime($search['day']));
        if(!empty($search['user'])) $where[] = ['type','in',[0,1]];//会员


        //搜索条件
        $searchField['eq'] = ['uid','channel_id','df_id','day','type','withdraw_id'];
        $where = search($search,$searchField,$where);
        $field = ['id','uid','channel_id','df_id','day','total_orders','total_fee_all','total_fee_paid','total_paid','rate','total_fee','type','title','platform','withdraw_id','update_at','do_orders','do_fee','agent','upstream'];

        $count = $this->where($where)->count(1);
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['day'=>'desc','id'=>'desc'])->select();
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


    public function bList($page = 1, $limit = 10, $search = []) {
        $where = [];

        if(!empty($search['day'])) $search['day'] = date('Y-m-d',strtotime($search['day']));
        if(!empty($search['user'])) $where[] = ['type','in',[0,1]];//会员


        //搜索条件
        $searchField['eq'] = ['uid','channel_id','df_id','day','type','withdraw_id'];
        $where = search($search,$searchField,$where);
        $field = ['id','info','day','update_at'];

        $count = $this->where($where)->count(1);
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['day'=>'desc','id'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';
        foreach ($data as $k => $v){
            $info = json_decode($v['info'],true);
            foreach ($info as $k1 =>$v1){
                foreach ($v1 as $k2 =>$v2){
                    $data[$k][$k1.'_'.$k2] = $v2;
                }
            }
        }



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