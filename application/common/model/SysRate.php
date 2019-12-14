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
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_system_rate';



    /**
     * 费率列表
     * @param int $page
     * @param int $limit
     * @param array $search
     * @param array $where
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = [], $where = []) {
        
        //搜索条件
        $searchField['eq'] = ['type','uid','status'];

        $where = search($search,$searchField,$where);

        $type = $search['type'];
        if($type == 0){
            $level =  \app\common\model\Ulevel::idArr();
        }elseif($search['type'] == 1){
            $level =  [];
        }
        $product =  \app\common\model\PayProduct::idArr();

        $field = 'id, uid, type,rate,update_at,p_id';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['uid desc','update_at desc'])->select()->each(function ($item, $key) use ($level,$product,$type) {
            if($type == 0){
                $item['level'] = $level[$item['uid']];
            }
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