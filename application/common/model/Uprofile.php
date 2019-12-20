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

    public function aList($page = 1, $limit = 10, $search = []) {

        //用户分组数组
        $group =  \app\common\model\Ulevel::idArr();

        $where = [
            ['who', 'in', [0,2]],
            ['pid', '=', $search['uid']]
        ];
        //下级代理
        $field = 'id,uid,level,pid,group_id,who,create_at,create_by';
        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['level'=>'asc','who'=>'desc'])->select()
            ->each(function ($item, $key) use ($group) {

                if($item['who'] == 0){
                    $item['level_title'] = $item['level'].'级商户';
                }else{
                    $item['level_title'] =  $item['level'].'级代理';
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
                $data1['level_title'] =  $data1['level'].'级代理(上级代理)';
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

}