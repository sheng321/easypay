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
     * redis
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3360 ,
        'key'=> "String:table:Ulevel:uid:{uid}:id:{id}",
        'keyArr'=> ['id','uid'],
    ];


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
        $searchField['eq'] = ['type','uid'];
        $searchField['like'] = ['title'];

        $where = search($search,$searchField,$where);

        dump($where);
        $field = 'id, title, remark, channel_id,create_at,type1,type,uid';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['uid desc','create_at desc'])->select()->each(function ($item, $key) {
            $arr = json_decode($item["channel_id"],true);
            $num =  count($arr,COUNT_RECURSIVE); //通道分组个数
            if($num > 1){
                $num =  $num - count($arr);
            }
            $item['mode'] = $num;

            //费率
           $count =  \app\common\model\SysRate::where(['group_id'=>$item['id']])->count();
           $item['rate_count'] = $count;

        });
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        halt($data);
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
     * 获取所属支付通道分组的最大费率
     * @param ID    用户分组ID
     * @param $p_id 支付产品
     * @return mixed
     */
    public static function getMaxRate($id,$p_id) {

        \think\facade\Cache::tag('Ulevel')->remember('getMaxRate', function (){
            $channel_id = self::column('channel_id,id','id');
            foreach ($channel_id as $k => $v){
                $data[$k] = json_decode($v,true);
                foreach ($data[$k] as $k1 => $v1){
                    $data[$k][$k1] = \app\common\model\ChannelGroup::where(['id','in',$v1])->max('c_rate');
                }
            }
            return $data;
        },3600);
        $getMaxRate = \think\facade\Cache::get('getMaxRate');
        $max = isset($getMaxRate[$id][$p_id])?$getMaxRate[$id][$p_id]:0;
        return $max;
    }




    /**
     * ID与等级名称数组
     * @param array $modules
     */
    public static function idArr() {

        \think\facade\Cache::tag('Ulevel')->remember('UlevelIdArr', function () {
            $data = self::column('id,title');
            return $data;
        },60);
        return \think\facade\Cache::get('UlevelIdArr');
    }

}