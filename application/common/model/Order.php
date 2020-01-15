<?php

namespace app\common\model;
use app\common\service\ModelService;
use think\helper\Str;

/**
 * 订单支付表
 */
class Order extends ModelService {

     /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_order';

    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 300,
        'key'=> "String:table:Order:transaction_no:{transaction_no}:out_trade_no:{out_trade_no}:systen_no:{systen_no}:id:{id}",
        'keyArr'=> ['id','out_trade_no','systen_no','transaction_no'],
    ];

    /**
     * Undocumented 分页获取
     * @param integer $page
     * @param integer $limit
     * @param array $search
     * @return void
     */
    public function alist($page = 1,$limit = 10,$search = [],$type = 0){
        $where = [];

        $ChannelGroup =  ChannelGroup::idArr();//通道分组
        $Channel =  Channel::idRate();//通道

        //通道分组模糊搜索
        if(!empty($search['channelgroup_name'])){
            foreach($ChannelGroup as $key=>$values ){
                if (strstr( $values , $search['channelgroup_name']) !== false ){
                    $search['channel_group_id'][] = $key;
                }
            }
            if(empty($search['channel_group_id']))  $search['channel_group_id'][] = 0;
                $searchField['in'][] = 'channel_group_id';
        }

        //通道模糊搜索
        if(!empty($search['channel_name'])){
            foreach($Channel as $key=>$values ){
                if (strstr( $values['title'] , $search['channel_name']) !== false ){
                    $search['channel_id'][] = $key;
                }
            }
            if(empty($search['channel_id'])) $search['channel_id'][] = 0;
                $searchField['in'][] = 'channel_id';
        }

        if(empty($search['create_at'])){
            $date = timeToDate(0,0,0,-5); //默认只搜索5天
            $where[] = ['create_at','>',$date];
        }

        //搜索条件
         $searchField['eq'] = ['mch_id','payment_id','pay_status','notice','ip'];
         $searchField['like'] = ['out_trade_no','systen_no','transaction_no'];
         $searchField['time'] = ['create_at'];
        $where = search($search,$searchField,$where);

        //价格区间
        if(!empty($search['amount'])){
            $value_list = explode("-", $search['amount']);
            $where[] = ['amount', 'BETWEEN', ["{$value_list[0]}", "{$value_list[1]}"]];
        }
        //代理
        if(!empty($search['mch_id1'])){
            $where[] = ['mch_id1|mch_id2', '=', $search['mch_id1']];
        }else{
            if($type == 1){
                $where[] = ['mch_id1|mch_id2', '>', 0];
            }
        }

        if(empty($search['field'])){
            $field = "id,mch_id,out_trade_no,systen_no,transaction_no,amount,actual_amount,total_fee,upstream_settle,Platform,channel_id,channel_group_id,payment_id,pay_status,notice,pay_time,create_time,create_at,update_at,cost_rate,run_rate,mch_id1,mch_id2,agent_rate2,agent_rate,agent_amount,agent_amount2,remark,over_time,ip";
        }else{
            //下载
            $field =  $search['field'];
        }

        $count = $this->where($where)->count();

        $list = $this->where($where)->page($page,$limit)->field($field)->cache('order_list_admin',2)->order(['create_at'=>'desc'])->select()->toArray();
        empty($list) ? $msg = '暂无数据！' : $msg = '查询成功！';

        $PayProduct =  PayProduct::idArr();//支付产品


        $order = config('order.');
        foreach ($list as $k=>$v){
            $list[$k]['product_name'] = empty($PayProduct[$v['payment_id']])?'未知':$PayProduct[$v['payment_id']];
            $list[$k]['channelgroup_name'] = empty($ChannelGroup[$v['channel_group_id']])?'未知':$ChannelGroup[$v['channel_group_id']];
            $list[$k]['channel_name'] = empty($Channel[$v['channel_id']])?'未知':$Channel[$v['channel_id']]['title'];

            empty($list[$k]['pay_time'])?'':$list[$k]['pay_time'] = Str::substr($list[$k]['pay_time'],8,11);
            $list[$k]['create_time'] =Str::substr($list[$k]['create_time'],8,11);
            $list[$k]['update_at'] = Str::substr($list[$k]['update_at'],8,11);

            if(($v['pay_status'] == 0) && (time() > $v['over_time'])) $list[$k]['pay_status'] = 3;//显示订单关闭

            $list[$k]['pay_status_name'] = $order['pay_status'][$v['pay_status']];
            $list[$k]['notice_name'] = $order['notice'][$v['notice']];

        }

        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil($count / $limit)],
            'data'  => $list,
        ];
        return $list;
    }


    /**
     * Undocumented 处理订单分页获取
     * @param integer $page
     * @param integer $limit
     * @param array $search
     * @return void
     */
    public function blist($page = 1,$limit = 10,$search = []){
        $where = [];
        $ChannelGroup =  ChannelGroup::idArr();//通道分组
        $Channel =  Channel::idRate();//通道

        //通道分组模糊搜索
        if(!empty($search['channelgroup_name'])){
            foreach($ChannelGroup as $key=>$values ){
                if (strstr( $values , $search['channelgroup_name']) !== false ){
                    $search['channel_group_id'][] = $key;
                }
            }
            if(empty($search['channel_group_id']))  $search['channel_group_id'][] = 0;
            $searchField['in'][] = 'channel_group_id';
        }

        //通道模糊搜索
        if(!empty($search['channel_name'])){
            foreach($Channel as $key=>$values ){
                if (strstr( $values['title'] , $search['channel_name']) !== false ){
                    $search['channel_id'][] = $key;
                }
            }
            if(empty($search['channel_id'])) $search['channel_id'][] = 0;
            $searchField['in'][] = 'channel_id';
        }

        //搜索条件
        $searchField['eq'] = ['mch_id','payment_id','pay_status','notice','ip'];
        $searchField['like'] = ['out_trade_no','systen_no','transaction_no'];
        $searchField['time'] = ['create_at'];
        $where = search($search,$searchField,$where);

        foreach ($where as $k => $v){
            if($where[$k][0] == 'create_at'){
                $where[$k][0] = 'w.'.$where[$k][0];
                continue;
            }
            $where[$k][0] = 'a.'.$where[$k][0];
        }

        if(empty($search['create_at'])){
            $date = timeToDate(0,0,0,-5); //默认只搜索5天
            $where[] = ['w.create_at','>',$date];
        }

        //价格区间
        if(!empty($search['amount'])){
            $value_list = explode("-", $search['amount']);
            $where[] = ['a.amount', 'BETWEEN', ["{$value_list[0]}", "{$value_list[1]}"]];
        }
        //代理
        if(!empty($search['mch_id1'])){
            $where[] = ['a.mch_id1|a.mch_id2', '=', $search['mch_id1']];
        }


        $field = "a.mch_id,a.out_trade_no,a.systen_no,a.transaction_no,a.amount,a.actual_amount,a.total_fee,a.upstream_settle,a.Platform,a.channel_id,a.channel_group_id,a.payment_id,a.pay_status,a.notice,a.pay_time,a.create_time,a.cost_rate,a.run_rate,a.mch_id1,a.mch_id2,a.agent_rate2,a.agent_rate,a.agent_amount,a.agent_amount2,a.over_time,a.ip,w.*,w.remark as remark1,a.remark as remark2";

        $count = self::alias('a')
            ->where($where)
            ->join('order_dispose w','a.id = w.pid','right')
            ->count();

        $list = self::alias('a')
             ->where($where)
            ->join('order_dispose w','a.id = w.pid','right')
            ->page($page,$limit)->field($field)->cache('order_dispose_list_admin',2)->order(['w.create_at'=>'desc'])->select()->toArray();

        empty($list) ? $msg = '暂无数据！' : $msg = '查询成功！';

        $PayProduct =  PayProduct::idArr();//支付产品

        foreach ($list as $k=>$v){
            $list[$k]['product_name'] = empty($PayProduct[$v['payment_id']])?'未知':$PayProduct[$v['payment_id']];
            $list[$k]['channelgroup_name'] = empty($ChannelGroup[$v['channel_group_id']])?'未知':$ChannelGroup[$v['channel_group_id']];
            $list[$k]['channel_name'] = empty($Channel[$v['channel_id']])?'未知':$Channel[$v['channel_id']]['title'];

            empty($list[$k]['pay_time'])?'':$list[$k]['pay_time'] = Str::substr($list[$k]['pay_time'],8,11);
            $list[$k]['create_time'] =Str::substr($list[$k]['create_time'],8,11);
            $list[$k]['update_at'] = Str::substr($list[$k]['update_at'],8,11);

            if(($v['pay_status'] == 0) && (time() > $v['over_time'])) $list[$k]['pay_status'] = 3;//显示订单关闭
        }

        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil($count / $limit)],
            'data'  => $list,
        ];
        return $list;
    }


    //订单 回调数据
    public static function notify($sn){
        $Order = self::quickGet(['systen_no'=>$sn]);
        $Uprofile = Uprofile::quickGet(['uid'=>$Order['mch_id']]);

        $data['memberid'] = $Order['mch_id'];
        $data['orderid'] = $Order['out_trade_no'];
        $data['transaction_id'] = $Order['systen_no'];
        $data['amount'] = $Order['amount'];
        $data['datetime'] = $Order['pay_time'];
        $data['returncode'] = '00';

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $Uprofile['secret']));
        $data['attach'] = $Order['attach'];

        return [
            'data'=>$data,
            'url'=>$Order['notify_url'],
            'order'=>[
                'id'=>$Order['id'],
                'notice'=>$Order['notice'],
                'pay_time'=>strtotime($Order['pay_time']),
            ]
        ];
    }

}