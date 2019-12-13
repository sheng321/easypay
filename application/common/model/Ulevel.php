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
 * 用户等级模型
 * Class Auth
 * @package app\common\model
 */
class Ulevel extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_member_level';


    /**
     * 获取权限组
     * @param int $type 0 后台   1 商户端
     * @return array|\PDOStatement|string|\think\Collection
     */
    public function getList($type = 0) {
        $where_auth = [
            ['status', '=', 1],
            ['type', '=', $type],
        ];
        $order_auth = [
            'id' => 'asc',
        ];
        $auth = $this->where($where_auth)->field('id, title, status')->order($order_auth)->select();
        return $auth;
    }

    /**
     * 用户等级列表
     * @param int $page
     * @param int $limit
     * @param array $search
     * @param array $where
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = [], $where = []) {
        
        //搜索条件

        $searchField['like'] = ['title'];

        $where = search($search,$searchField,$where);

        $field = 'id, title, remark, channel_id,create_at';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['create_at desc'])->select();
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



    /**
     * 更新节点
     * @param $update
     * @return \think\response\Json
     */
    public function edit($update) {
        $data = $this->where('id', $update['id'])->update($update);
        if ($data == 1) {
            return __success('更新成功！');
        } else {
            return __error('数据没有修改！');
        }
    }

    /**
     * 修改系统节点字段值
     * @param $update
     * @return \think\response\Json
     */
    public function edit_field($update) {
        $data = $this->where('id', $update['id'])->update([$update['field'] => $update['value']]);
        if ($data == 1) {
            return __success('修改成功！');
        } else {
            return __error('数据没有修改！');
        }
    }



}