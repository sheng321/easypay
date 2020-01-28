<?php

namespace app\common\model;

use app\common\service\ModelService;

/** IP
 * Class Bank
 * @package app\common\model
 */
class Ip extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_ip';

    protected $insert = [ 'create_by','ip'];
    /**
     * 用户分组列表
     * @param int $page
     * @param int $limit
     * @param array $search
     * @param array $where
     * @return array
     */
    public function aList($page = 1, $limit = 10, $search = [], $where = []) {

        if(!empty($search['create_name'])){
            $search['uid'] = getIdbyName($search['create_name']); //后台
            if(empty( $search['uid'])) $search['uid'] = 0;
        }

        //搜索条件
        $searchField['eq'] = ['uid','ip'];
        $searchField['in'] = ['type'];

        $where = search($search,$searchField,$where);
        $field = '*';
        $count = $this->where($where)->count();

        $order = ['create_at'=>'desc'];
        if(empty($search['uid']))  $order = ['uid'=>'desc','create_by'=>'desc','create_at'=>'desc','type'=>'desc'];

        $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select()->each(function ($item, $key) {

            if($item['type'] == 3){
                $item['create_name'] = empty($item['create_by'])?$item['create_by']: getNamebyId($item['create_by']);//后台
                $item['username'] =getNamebyId($item['uid']);
            }else{
                $item['create_name'] = empty($item['create_by'])?$item['create_by']: getUnamebyId($item['create_by']);
            }
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

    public static function bList($uid,$type = null){
        $data = self::where("uid",$uid)->column("id,ip,type",'id');
        $data1 = array();
        foreach ($data as $k=>$v){
            $data1[$v['type']][] = $v['ip'];
        }
        dump($data1);
        halt($data);

        $list =  \think\facade\Cache::remember('IP_'.$uid, function ()use($uid) {
            $data = self::where("uid",$uid)->column("id,ip,type",'id');
            $data1 = array();
            foreach ($data as $k=>$v){
                $data1[$v['type']][] = $v['ip'];
            }
            \think\facade\Cache::tag('Ip')->set('IP_'.$uid,$data1,60);
            return \think\facade\Cache::get('IP_'.$uid);
        });

        if(empty($list)){
            $ip = get_client_ip();
            empty($type) && $type = 1;
            self::create(['uid'=>$uid,'ip'=>$ip,'type'=>$type]);
            return [$ip];
        }

        if($type === null) return $list;

        if(empty($list[$type])){
            \think\facade\Cache::rm('IP_'.$uid);
            $ip = get_client_ip();
            self::create(['uid'=>$uid,'ip'=>$ip,'type'=>$type]);
            return [$ip];
        }

        return $list[$type];
    }

}