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

        $field = 'id, title, remark, channel_id,create_at,type1,type,uid';
        $count = $this->where($where)->count(1);
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

        \think\facade\Cache::remember('getMaxRate', function (){
            $channel_id = self::column('channel_id,id','id');
            $data = [];
            foreach ($channel_id as $k => $v){
                $data[$k] = json_decode($v,true);
                foreach ($data[$k] as $k1 => $v1){
                 if(!empty($v1) && is_array($v1)){
                     $data[$k][$k1] = \app\common\model\ChannelGroup::where([['id','in',$v1]])->max('c_rate');
                 }
                }
            }
            \think\facade\Cache::tag('Ulevel')->set('getMaxRate',$data,3600);
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

        \think\facade\Cache::remember('UlevelIdArr', function () {
            $data = self::column('id,title');
            \think\facade\Cache::tag('Ulevel')->set('UlevelIdArr',$data,60);
            return $data;
        },60);
        return \think\facade\Cache::get('UlevelIdArr');
    }


    /**
     *  删除代理下商户分组选中的通道
     *  $uid 商户号
     *  $p_id 支付产品ID
     *  $channel_group_id 要删除的通道分组
     */
    public static function delChennelGroupID($uid,$p_id,$channel_group_id) {
        //代理分组
        $lower = Uprofile::get_lower($uid,2);//代理下级所有的代理
        $lower[]=$uid;
        $channel = self::where([
                ['uid','in',$lower],
                ['type1','=',0]//商户分组
            ]
        )->column('id,channel_id','id');//代理商户分组的分配通道分组的数据

        $needel = [];
        foreach ($channel as $k => $v){
            $temp = json_decode($v,true);
            if(!empty($temp[$p_id]) && is_array($temp[$p_id])){
                $key = array_search($channel_group_id,$temp[$p_id]);
                if(is_numeric($key)){
                    unset($temp[$p_id][$key]); //删除通道数据
                    if(empty($temp[$p_id])) unset($temp[$p_id]);
                    $needel[$k]['id'] = $k;
                    $needel[$k]['channel_id'] = json_encode($temp);
                }
            }
        }
        $res = true;
        if(!empty($needel)) $res =  (new Ulevel())->saveAll($needel);
        return $res;
    }





}