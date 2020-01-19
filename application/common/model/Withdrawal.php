<?php

namespace app\common\model;
use app\common\service\ModelService;

class Withdrawal extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_withdrawal';

    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 60,
        'key'=> "String:table:Umoney:system_no:{system_no}:id:{id}",
        'keyArr'=> ['id','system_no'],
    ];
    

    /**
     *  分页获取所有记录数
     * @param integer $page
     * @param integer $limit
     * @param array $search 条件
     */
    public function alist($page = 1,$limit = 10,$search = [],$where = []){
        $searchField['eq'] = ['status','system_no','mch_id'];
        $searchField['like'] = ['account_name'];
        $searchField['time'] = ['create_at'];
        $field = "*";
        $where = search($search,$searchField,$where);
        //获取总数
        $count = $this->where($where)->count();
        $data = $this->alias('a')->where($where)->order(['id'=>'desc'])->page($page,$limit)->field($field)->select()->toArray();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        foreach ($data as $k => $v){
            $bank =  json_decode($v['bank'],true);
            $data[$k]['card_number'] = $bank['card_number'];
            $data[$k]['account_name'] = $bank['account_name'];
            $data[$k]['bank_name'] = $bank['bank_name'];
            !empty($bank['branch_name']) &&  $data[$k]['branch_name'] = $bank['branch_name'];
            !empty($bank['province']) && $data[$k]['location'] =  $bank['province'].$bank['city'].$bank['areas'];
        }

        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil($count / $limit)],
            'data'  => $data,
        ];
        return $list;
    }

    /**
     * Undocumented 锁定/解除 出款/退款
     *
     * @param [int] $type 类型1-4
     * @param [obj] $info 数据
     * @param [type] $user_name 登录账号
     * @return void
     */
    public function saveWith($data,$info,$user_name){
        switch ($data['type']) {
            case '1'://锁定
                $info->is_lock = 1;
                $info->status = 2;
                $info->lock_name = $user_name;
                break;
            case '2'://解除
                if($info->lock_name != $user_name){
                    return __error('只能由账号【'.$info->lock_name.'】来解除');
                }
                $info->is_lock = 2;
                $info->status = 1;
                $info->lock_name = '';
                $info->channel = '';
                break;
            case '3'://出款
                if($info->is_lock != 1){
                    return __error('请先锁定');
                }
                if($info->status == 3 || $info->status == 4){
                    return __error('订单状态不对');
                }
                $info->status = 3;
                $info->remark = $data['text'];//备注
                break;
            default://退款
                if($info->is_lock != 1){
                    return __error('请先锁定');
                }
                if($info->status == 3 || $info->status == 4){
                    return __error('订单状态不对');
                }
                $info->status = 4;
                $info->remark = $data['text'];//备注
                break;
        }
        $info->save();
        return __success('操作成功');
    }



}