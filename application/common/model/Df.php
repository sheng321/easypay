<?php

namespace app\common\model;
use app\common\service\ModelService;

class Df extends ModelService {

    /**
     * 绑定的数据表
     * @var string
     */
    protected $table = 'cm_withdrawal_api';


    protected $insert = [ 'create_by','ip'];

    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 3,
        'key'=> "String:table:Df:out_trade_no:{out_trade_no}:transaction_no:{transaction_no}:system_no:{system_no}:id:{id}",
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

        $channel = \app\common\model\ChannelDf::cache('ChannelDf_list',3)->column('id,title,status','id');


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

            !empty($v['lock_id']) && $data[$k]['lock_name'] = getNamebyId($v['lock_id']);
             $data[$k]['channel_title'] =!empty($v['channel_id']) && !empty($channel[$v['channel_id']]) ?$channel[$v['channel_id']]['title']: '';

            $bank =  json_decode($v['bank'],true);
            $data[$k]['card_number'] = $bank['card_number'];
            $data[$k]['account_name'] = $bank['account_name'];
            $data[$k]['bank_name'] = $bank['bank_name'];
            !empty($bank['branch_name']) &&  $data[$k]['branch_name'] = $bank['branch_name'];
            !empty($bank['province']) && $data[$k]['location'] =  $bank['province'].$bank['city'];

            if(app('request')->module() === 'admin'){
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

    //单卡 单日次数 单日金额
    public static function card_times_money($cardnumber,$amount){
        $result =  self::where([['status','<',4],['card_number','=',$cardnumber]])->whereBetween('create_at',[timeToDate(0,0,-24),date('Y-m-d H:i:s')])->cache('card_times__money'.$cardnumber,1)->field("COALESCE(sum(amount),0) as amounts,count(1) as num ")->select();
        if(empty($result[0])) return '数据异常！';

        $withdrawal = config("custom.df");
        if(($result[0]['amounts'] + $amount) > $withdrawal['limit_money']){
            return "此银行卡： {$cardnumber} 超过单卡单日限额 {$withdrawal['limit_money']} 元";
        }
        if(($result[0]['num'] + 1) > $withdrawal['limit_times']){
            return "此银行卡： {$cardnumber} 超过单卡单日次数 {$withdrawal['limit_times']} 次";
        }
       return true;
    }

    //会员 单日限额
    public static function mch_id_money($mch_id,$amount){
        $withdrawal = config("custom.df");
        if(!empty($withdrawal['excpet_uid'])){
            $excpet_uid = explode("|",$withdrawal['excpet_uid']);
            if(!in_array($mch_id,$excpet_uid)){
                $amounts = self::where([['mch_id','=',$mch_id],['status','<',4]])->whereBetween('create_at',[timeToDate(0,0,-24),date('Y-m-d H:i:s')])->sum('amount');
                if(($amounts + $amount) > $withdrawal['total_money']){
                    return "超过会员单日限额 {$withdrawal['total_money']} 元，请联系客服处理。";
                }
            }
        }

        return true;
    }


}