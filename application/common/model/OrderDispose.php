<?php

namespace app\common\model;
use app\common\service\ModelService;
use think\helper\Str;

/**
 * 订单处理表
 */
class OrderDispose extends ModelService {

     /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_order_dispose';

    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [

        'ttl'=> 3600,
        'key'=> "String:table:OrderDispose:pid:{pid}:system_no:{system_no}:id:{id}",
        'keyArr'=> ['id','pid','system_no'],
    ];

    /**
     * Undocumented 分页获取
     * @param integer $page
     * @param integer $limit
     * @param array $search
     * @return void
     */
    public function alist($page = 1,$limit = 10,$search = []){
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
         $searchField['eq'] = ['mch_id','payment_id','pay_status','notice'];
         $searchField['like'] = ['out_trade_no','system_no','transaction_no'];
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
        }


        $field = "id,mch_id,out_trade_no,system_no,transaction_no,amount,actual_amount,total_fee,upstream_settle,Platform,channel_id,channel_group_id,payment_id,pay_status,notice,pay_time,create_time,create_at,update_at,cost_rate,run_rate,mch_id1,mch_id2,agent_rate2,agent_rate,agent_amount,agent_amount2,remark,over_time";
        $list = $this->where($where)->page($page,$limit)->field($field)->cache('order_list_admin',2)->order(['create_at'=>'desc'])->select()->toArray();
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
            'count' => count($list),
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil(count($list) / $limit)],
            'data'  => $list,
        ];
        return $list;
    }

}