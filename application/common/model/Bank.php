<?php

namespace app\common\model;

use app\common\service\ModelService;

/** 银行卡
 * Class Bank
 * @package app\common\model
 */
class Bank extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_bank_card';

    /**
     * 用户分组列表
     * @param int $page
     * @param int $limit
     * @param array $search
     * @param array $where
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = [], $where = []) {

        $order = ['update_at'=>'desc'];

        if(empty($search['uid']) && $search['uid'] != 0 ){
            $order['uid'] = 'desc';
            $where[] = ['uid','>',0];
        }

        //搜索条件
        $searchField['eq'] = ['uid'];
        $searchField['like'] = ['account_name'];
        $where = search($search,$searchField,$where);
        $field = '*';
        $count = $this->where($where)->count();



        $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select()->each(function ($item, $key) {
            !empty($item['update_by']) &&   $item['update_name'] =  getUnamebyId($item['update_by']);
            if($item['uid'] == 0)  $item['update_name'] =  getNamebyId($item['create_by']);
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

    public static function bList($uid){
        return self::where("uid",$uid)->cache('bank_list_'.$uid,3)->column("id,card_number,bank_name,account_name,branch_name,province,city,areas",'id');
    }

}