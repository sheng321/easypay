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
        
        //搜索条件
        $searchField['eq'] = ['uid'];
        $searchField['like'] = ['account_name'];
        $where = search($search,$searchField,$where);

        halt($where);

        $field = '*';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['update_at'=>'desc'])->select()->each(function ($item, $key) {
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