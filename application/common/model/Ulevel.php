<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 用户分组模型
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
     * 用户分组列表
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
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['create_at desc'])->select()->each(function ($item, $key) {
            $arr = json_decode($item["channel_id"],true);
            $num =  count($arr,COUNT_RECURSIVE); //通道分组个数
            if($num > 1){
                $num =  $num - count($arr);
            }
            $item['mode'] = $num;
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


    /**
     * ID与等级名称数组
     * @param array $modules
     */
    public static function idArr() {

        \think\facade\Cache::remember('UlevelIdArr', function () {
            $data = self::column('id,title');
            \think\facade\Cache::tag('Ulevel')->set('UlevelIdArr',$data,3600);
            return \think\facade\Cache::get('UlevelIdArr');
        });
        return \think\facade\Cache::get('UlevelIdArr');
    }

}