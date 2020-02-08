<?php

namespace app\common\model;
use app\common\service\ModelService;

class Withdrawal extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_withdrawal';

    protected $insert = [ 'create_by','ip'];
    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 60,
        'key'=> "String:table:Withdrawal:system_no:{system_no}:id:{id}",
        'keyArr'=> ['id','system_no'],
    ];

    /**
     *  分页获取所有记录数
     * @param integer $page
     * @param integer $limit
     * @param array $search 条件
     */
    public function alist($page = 1,$limit = 10,$search = [],$where = []){

        if(!empty($search['lock_name'])) $search['lock_id'] = getIdbyName($search['lock_name']);

        $channel =  model('app\common\model\Channel')->idRate();

        if(!empty($search['channel_title'])){
            foreach($channel as $key=>$values ){
                //模糊
                if (strstr( $values['title'] , $search['channel_title']) !== false ){
                    $search['channel_id'][] = $key;
                }
                if(empty($search['channel_id']))  $search['channel_id'][] = -1;
            }
        }

        if(empty($search['create_at'])){
            $date = timeToDate(0,0,0,-5); //默认只搜索5天
            $where[] = ['create_at','>',$date];
        }

        $searchField['in'] = ['channel_id'];
        $searchField['eq'] = ['status','mch_id','lock_id'];
        $searchField['left_like'] = ['account_name','system_no','system_no','transaction_no','card_number'];
        $searchField['time'] = ['create_at'];

        //金额区间
        if(!empty($search['amount'])){
            $value_list = explode("-", $search['amount']);
            $where[] = ['amount', 'BETWEEN', ["{$value_list[0]}", "{$value_list[1]}"]];
        }

        $field = "*";
        $where = search($search,$searchField,$where);
        //获取总数
        $count = $this->where($where)->count();
        $data = $this->alias('a')->where($where)->order(['id'=>'desc'])->page($page,$limit)->field($field)->select()->toArray();
        empty($data) ? $msg = '暂无数据！' : $msg = '查询成功！';

        $status =   config('custom.status');

        if(app('request')->module() === 'admin'){
        $card_number = [];
        $account_name = [];
            $bad =  Bank::bList(0);//异常卡
            foreach ($bad as $k => $v){
                $card_number[] = $v['card_number'];
                $account_name[] = $v['account_name'];
            }
        }


        foreach ($data as $k => $v){

            $data[$k]['status_title'] = $status[$v['status']];


            $bank =  json_decode($v['bank'],true);
            $data[$k]['card_number'] = $bank['card_number'];
            $data[$k]['account_name'] = $bank['account_name'];
            $data[$k]['bank_name'] = $bank['bank_name'];
            !empty($bank['branch_name']) &&  $data[$k]['branch_name'] = $bank['branch_name'];
            !empty($bank['province']) && $data[$k]['location'] =  $bank['province'].$bank['city'];


             //只在后台显示
            if(app('request')->module() === 'admin'){
                $data[$k]['lock_name'] = getNamebyId($v['lock_id']);
                $data[$k]['channel_title'] = $channel[$v['channel_id']]['title'];
                //异常卡
                if(in_array($data[$k]['card_number'],$card_number))  $data[$k]['card_number'] = "<span  class='text-danger'  >".$data[$k]['card_number']."-异常</span>";
                if(in_array($data[$k]['account_name'],$account_name))  $data[$k]['account_name'] = "<span  class='text-danger'  >".$data[$k]['account_name']."-异常</span>";
            }

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


}