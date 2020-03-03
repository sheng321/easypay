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
 * 金额模型流水
 * Class Auth
 * @package app\common\model
 */
class UmoneyLog extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_money_log';

    public function aList($page = 1, $limit = 10, $search = [],$where = []) {

        if(!empty($search['nickname'])){
            $id = getIdbyName($search['nickname']);
            !empty($id) && $where[] = ['create_by','=',$id];
        }

        //权限组
        if(!empty($search['title'])){
            $id = getIdbyTitle($search['title']);
            !empty($id) && $where[] = ['create_by','in',$id];
        }


        if(empty($search['create_at'])){
            $date = timeToDate(0,0,0,-1); //默认只搜索1天
            $where[] = ['create_at','>',$date];
        }

        //搜索条件
        $searchField['eq'] = ['type1','type','uid','channel_id'];
        $searchField['like'] = ['remark','relate'];
        $searchField['time'] = ['create_at'];
        $where = search($search,$searchField,$where);

        $money  =  config('money.');

        $field = ['id','uid','create_at','create_by','balance','before_balance','change','type','remark','relate','type1','channel_id','df_id','type2'];

        $count = $this->where($where)->count();
        $data = $this->where($where)->field($field)->page($page, $limit)->order(['create_at'=>'desc'])->select()->each(function ($item, $key)use ($money) {
            $item['nickname'] = 0;
            $item['auth_title'] = 0;
            if($item['type2'] == 2|| $item['type2'] == 3 &&  $item['create_by'] > 0){
                $item['nickname'] = getUnamebyId($item['create_by']);
                $item['auth_title'] =  getUtitlebyId($item['create_by']);
            }
            if($item['type2'] == 1 &&  $item['create_by'] > 0) {
                $item['nickname'] = getNamebyId($item['create_by']);
                $item['auth_title'] =  getTitlebyId($item['create_by']);
            }
            $item['title'] = $money[$item['type']];
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