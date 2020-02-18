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
 * 商户属性模型
 * Class Auth
 * @package app\common\model
 */
class Uprofile extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_member_profile';

    /**
     * redis
     * key   字段值要唯一
     * relate 关联一起更新
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 60 ,
        'key'=> "String:table:Uprofile:uid:{uid}:id:{id}",
        'keyArr'=> ['uid','id'],
        'relate'=> ['Umember'=>'uid'],
    ];


    public function aList($page = 1, $limit = 10, $search = []) {

        //用户分组数组
        $group =  \app\common\model\Ulevel::idArr();

        $uid =  \app\common\model\Urelations::whereOr([
             [
                 ['pid','=',$search['uid']],
                 ['level','=',1]
             ],//一级会员
            [
                ['pid','=',$search['uid']],
                ['level','=',2],
                ['who','=',0]
            ],//二级商户
        ])->column('id,uid');

        $where = [
            ['uid', 'in', $uid]
        ];
        //下级代理
        $field = 'id,uid,level,pid,group_id,who,create_at,create_by';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['level'=>'asc','who'=>'desc'])->select()
            ->each(function ($item, $key) use ($group) {
                if($item['who'] == 0){
                   // $item['level_title'] = $item['level'].'级商户';
                    $item['level_title'] = '商户';
                }else{
                    //$item['level_title'] =  $item['level'].'级代理';
                    $item['level_title'] =  '代理';
                }
                $item['group_title'] = isset($group[$item['group_id']])?$group[$item['group_id']]:'未分组' ;
                $create_by_username =   getNamebyId($item['create_by']);  //获取后台用户名
                empty($create_by_username) ? $item['create_by_username'] = '无创建者信息' : $item['create_by_username'] = $create_by_username;
            })->toArray();


        //上级代理
        $pid = $this->where([
            ['uid', '=', $search['uid']]
        ])->value('pid');
        if(!empty($pid)){
            $data1 = $this->where([
                ['who', '=', 2],
                ['uid', '=', $pid]])->field($field)->find()->toArray();
            if(!empty($data1)){
                //$data1['level_title'] =  $data1['level'].'级代理(上级代理)';
                $data1['level_title'] =  '上级代理';
                $data1['group_title'] = isset($group[$data1['group_id']])?$group[$data1['group_id']]:'未分组' ;
                $create_by_username = getNamebyId($data1['create_by']);  //获取后台用户名
                empty($create_by_username) ? $data1['create_by_username'] = '无创建者信息' : $data1['create_by_username'] = $create_by_username;
                array_unshift($data, $data1);
            }

        }
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
     * 获取所有的下级
     * @param $uid 商户号
     *  @param $who  0 商户 2 代理
     */
    public static function get_lower($uid,$who){
        $data = self::cache('Uprofile',30)->column('id,uid,pid,who','id');
        $lower = self::recursion($uid,$data);
        if(empty($lower[$who])) return [];
        return $lower[$who];
    }

    //递归
    public static function recursion($uid,$data){
        $lower = [];
        foreach ($data as $k => $v){
            if($v['pid'] == $uid){
                $tmp[$v['who']][] = $v['uid'];
                $tmp1 = self::recursion($k,$data);
                $lower = array_merge($tmp,$tmp1);
            }
        }
        return $lower;
    }




}