<?php

namespace app\common\model;
use app\common\service\ModelService;
use think\Db;
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

        'ttl'=> 15*60,
        'key'=> "String:table:Order:mch_id:{mch_id}:transaction_no:{transaction_no}:out_trade_no:{out_trade_no}:system_no:{system_no}:id:{id}",
        'keyArr'=> ['id','out_trade_no','system_no','transaction_no','mch_id'],
    ];

    /**
     *  分页获取
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
        }else{
            if($type == 1){
                $where[] = ['mch_id1|mch_id2', '>', 0];
            }
        }

        if(empty($search['field'])){
            $field = "id,mch_id,out_trade_no,system_no,transaction_no,amount,actual_amount,total_fee,upstream_settle,Platform,channel_id,channel_group_id,payment_id,pay_status,notice,pay_time,create_time,create_at,update_at,cost_rate,run_rate,mch_id1,mch_id2,agent_rate2,agent_rate,agent_amount,agent_amount2,remark,over_time,ip,repair,is_mobile";
        }else{
            //下载
            $field =  $search['field'];
        }

        $count = $this->where($where)->count(1);

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
        if(!empty($search['field'])) $list['code'] = 1 && $list['msg'] = $msg.'本页数据不显示。'; //下载
        return $list;
    }

    /** 历史订单数据
     * @param int $page
     * @param int $limit
     * @param array $search
     * @param int $type
     * @return array
     */
    public function hlist($page = 1,$limit = 10,$search = [],$type = 0){
        $where = [];

        if(empty($search['table'])) $search['table'] =  date('Y-m');
        $tableName = 'cm_order_'.date('Y_m',strtotime($search['table']));
        $isTable= Db::query("SHOW TABLES LIKE '{$tableName}'");
        if(!$isTable){
            //表不存在
            $list = [
                'code'  => 1,
                'msg'   => "没有 ".$search['table'].' 月的数据',
                'count' => 0,
                'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>0],
                'data'  => [],
            ];
            return $list;
        }
        $this->table = $tableName;

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
        }else{
            if($type == 1){
                $where[] = ['mch_id1|mch_id2', '>', 0];
            }
        }

        if(empty($search['field'])){
            $field = "id,mch_id,out_trade_no,system_no,transaction_no,amount,actual_amount,total_fee,upstream_settle,Platform,channel_id,channel_group_id,payment_id,pay_status,notice,pay_time,create_time,create_at,update_at,cost_rate,run_rate,mch_id1,mch_id2,agent_rate2,agent_rate,agent_amount,agent_amount2,remark,over_time,ip,repair,is_mobile";
        }else{
            //下载
            $field =  $search['field'];
        }

        $count = $this->where($where)->count(1);

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
        if(!empty($search['field'])) $list['code'] = 1 && $list['msg'] = $msg.'本页数据不显示。'; //下载
        return $list;
    }
    


    /**
     *  处理订单分页获取
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
        $searchField['like'] = ['out_trade_no','system_no','transaction_no'];
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


        $field = "a.mch_id,a.out_trade_no,a.system_no,a.transaction_no,a.amount,a.actual_amount,a.total_fee,a.upstream_settle,a.Platform,a.channel_id,a.channel_group_id,a.payment_id,a.pay_status,a.notice,a.pay_time,a.create_time,a.cost_rate,a.run_rate,a.mch_id1,a.mch_id2,a.agent_rate2,a.agent_rate,a.agent_amount,a.agent_amount2,a.over_time,a.ip,w.*,w.remark as remark1,a.remark as remark2,a.repair as repair";

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



    /**
     *  商户分页获取
     * @param integer $page
     * @param integer $limit
     * @param array $search
     * @return void
     */
    public function clist($page = 1,$limit = 10,$search = [],$type = 0){
        if(!empty(session('user_info.uid'))) $uid = $search['mch_id'] = session('user_info.uid');

        $where = [];
        if(empty($search['create_at'])){
            $date = timeToDate(0,0,0,-3); //默认只搜索5天
            $where[] = ['create_at','>',$date];
        }

        //搜索条件
        $searchField['eq'] = ['mch_id','payment_id','pay_status','notice'];
        $searchField['left_like'] = ['out_trade_no','system_no'];
        $searchField['time'] = ['create_at'];
        $where = search($search,$searchField,$where);

        if(empty($search['field'])){
            $field = "id,mch_id,out_trade_no,system_no,amount,total_fee,payment_id,actual_amount,create_time,pay_time,productname,pay_status,notice,run_rate,settle";
        }else{
            //下载
            $field =  $search['field'];
        }


        $count = $this->where($where)->count();

        $list = $this->where($where)->page($page,$limit)->field($field)->cache('order_list_'.$uid,3)->order(['create_at'=>'desc'])->select()->toArray();
        empty($list) ? $msg = '暂无数据！' : $msg = '查询成功！';

        $PayProduct =  PayProduct::idArr();//支付产品

        $order = config('order.');
        foreach ($list as $k=>$v){
            $list[$k]['product_name'] = empty($PayProduct[$v['payment_id']])?'未知':$PayProduct[$v['payment_id']];
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

        if(!empty($search['field'])) $list['code'] = 1 && $list['msg'] = $msg.'本页数据不显示。'; //下载

        return $list;
    }



    /**
     *  代理分页获取
     * @param integer $page
     * @param integer $limit
     * @param array $search
     * @return void
     */
    public function dlist($page = 1,$limit = 10,$search = []){

        $uid = $search['agent'];
        $where[] =  ['mch_id1|mch_id2','=',$uid];

        if(empty($search['create_at'])){
            $date = timeToDate(0,0,0,-3); //默认只搜索5天
            $where[] = ['create_at','>',$date];
        }

        //搜索条件
        $searchField['eq'] = ['mch_id','payment_id','pay_status','notice'];
        $searchField['left_like'] = ['out_trade_no','system_no'];
        $searchField['time'] = ['create_at'];
        $where = search($search,$searchField,$where);

        if(empty($search['field'])){
            $field = "id,mch_id,mch_id1,mch_id2,out_trade_no,system_no,amount,payment_id,actual_amount,create_time,pay_time,productname,pay_status,notice,run_rate,agent_amount2,agent_amount,channel_group_id";
        }else{
            //下载
            $field =  $search['field'];
        }


        $count = $this->where($where)->count();

        $list = $this->where($where)->page($page,$limit)->field($field)->cache('order_list_'.$uid,3)->order(['create_at'=>'desc'])->select()->toArray();
        empty($list) ? $msg = '暂无数据！' : $msg = '查询成功！';


        $ChannelGroup =  ChannelGroup::idArr();//通道分组
        $PayProduct =  PayProduct::idArr();//支付产品

        $order = config('order.');
        foreach ($list as $k=>$v){
            $list[$k]['product_name'] = empty($PayProduct[$v['payment_id']])?'未知':$PayProduct[$v['payment_id']];
            $list[$k]['channelgroup_name'] = empty($ChannelGroup[$v['channel_group_id']])?'未知':$ChannelGroup[$v['channel_group_id']];
            $list[$k]['pay_status_name'] = $order['pay_status'][$v['pay_status']];
            $list[$k]['notice_name'] = $order['notice'][$v['notice']];
            if($v['mch_id2'] == $uid){
                $list[$k]['next'] = '无';//上级代理
                $list[$k]['commission'] = $v['agent_amount2'];//代理费
            }else{
                $list[$k]['next'] = $v['mch_id1'];//上上级代理
                $list[$k]['commission'] = $v['agent_amount'];//代理费
            }


        }

        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil($count / $limit)],
            'data'  => $list,
        ];

        if(!empty($search['field'])) $list['code'] = 1 && $list['msg'] = $msg.'本页数据不显示。'; //下载

        return $list;
    }


    //订单 回调数据
    public static function notify($sn,$code = ''){
        $Order = self::quickGet(['system_no'=>$sn]);
        $Uprofile = Uprofile::quickGet(['uid'=>$Order['mch_id']]);

        $data['memberid'] = $Order['mch_id'];
        $data['orderid'] = $Order['out_trade_no'];
        $data['transaction_id'] = $Order['system_no'];
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
                'code'=>$code,
            ]
        ];
    }

}