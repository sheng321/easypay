<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 行为日志
 * Class ActionRecode
 * @package app\common\model
 */
class ActonRecord extends ModelService {


    protected $insert = [ 'location','ip','create_by'];

    /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_system_action_record';


    /**
     * 获取列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function logList($page = 1, $limit = 10, $search = []) {
        $where = [];

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
            $date = timeToDate(0,0,0,-7); //默认只搜索7天
           $where[] = ['create_at','>',$date];
        }




        //搜索条件
        $searchField['eq'] = ['ip','url','type'];
        $searchField['like'] = ['remark'];
        $searchField['time'] = ['create_at'];

        $where = search($search,$searchField,$where);

        $field = ['id','url','method','param','create_at','remark','ip','location','create_by','type'];

        $count = $this->where($where)->count();

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['create_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';


        if(!empty($data)){
            foreach ($data as $k => $val){
                $data[$k]['nickname'] = getNamebyId($val['create_by']);
                $data[$k]['auth_title'] = getTitlebyId($val['create_by']);
            }
        }

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
     * 获取商户列表信息
     * @param int $page  当前页
     * @param int $limit 每页显示数量
     * @return array
     */
    public function userList($page = 1, $limit = 10, $search = []) {
        $where = [];

        //商户账号
        if(!empty($search['username'])){
            $id = getUnamebyId($search['username']);
            !empty($id) && $where[] = ['create_by','=',$id];
        }
        //商户号
        if(!empty($search['uid'])){
            $id = getIdbyUid($search['uid']);
            !empty($id) && $where[] = ['create_by','in',$id];
        }

        //权限组
        if(!empty($search['title'])){
            $id = getIdbyUtitle($search['title']);
            !empty($id) && $where[] = ['create_by','in',$id];
        }

        if(empty($search['create_at'])){
            $date = timeToDate(0,0,0,-7); //默认只搜索7天
            $where[] = ['create_at','>',$date];
        }


        //搜索条件
        $searchField['eq'] = ['ip','url','type'];
        $searchField['like'] = ['remark'];
        $searchField['time'] = ['create_at'];

        $where = search($search,$searchField,$where);

        $field = ['id','url','method','param','create_at','remark','ip','location','create_by','type'];

        $count = $this->where($where)->count();

        $data = $this->where($where)->field($field)->page($page, $limit)->order(['create_at'=>'desc'])->select();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';


        if(!empty($data)){
            foreach ($data as $k => $val){
                $data[$k]['username'] = getUnamebyId($val['create_by']);//账号
                $data[$k]['auth_title'] = getUtitlebyId($val['create_by']);//权限名称
                $data[$k]['uid'] = getUidbyId($val['create_by']);//商户号
            }
        }

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