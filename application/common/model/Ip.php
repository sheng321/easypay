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
        
        //搜索条件
        $searchField['eq'] = ['uid'];
        $searchField['like'] = ['account_name'];
        $where = search($search,$searchField,$where);
        $field = '*';
        $count = $this->where($where)->count();

        $order = ['create_at'=>'desc'];
        if(empty($search['uid']))  $order = ['uid'=>'desc','type'=>'desc','create_at'=>'desc'];

        $data = $this->where($where)->field($field)->page($page, $limit)->order($order)->select()->each(function ($item, $key) {
            $item['create_name'] =  getUnamebyId(empty($item['create_by'])?$item['create_by']:$item['create_by']);
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
        $list = self::where("uid",$uid)->cache('IP_list_'.$uid,3)->column("id,ip,type,uid",'type');
        if(empty($list)){
            $ip = get_client_ip();
            empty($type) && $type = 1;
            self::create(['uid'=>$uid,'ip'=>$ip,'type'=>$type]);
            return [$ip];
        }
        if($type == null) return $list;
        if(empty($list[$type])){
            $ip = get_client_ip();
            self::create(['uid'=>$uid,'ip'=>$ip,'type'=>$type]);
            return [$ip];
        }

        return $list[$type];
    }

}