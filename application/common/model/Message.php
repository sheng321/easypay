<?php

namespace app\common\model;

use app\common\service\ModelService;

/** Message
 */
class Message extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_message';

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
        $searchField['eq'] = ['type'];

        $where = search($search,$searchField,$where);
        $field = '*';
        $count = $this->where($where)->count(1);

        $order = ['id'=>'desc'];
        $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select()->toArray();

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