<?php
namespace app\common\service;
use app\common\model\Accounts;
use app\common\model\Channel;
use app\common\model\ChannelGroup;
use app\common\model\PayProduct;
use think\Db;
use think\facade\Cache;


/**
 * 统计
 * @package service
 */
class CountService {

    /**通道成功率 3-10分钟的成功率 3分钟统计一次  支付通道产品，通道分组，支付类型
     * @return mixed
     */
    public static function success_rate(){

        Cache::remember('success_rate', function () {
            $data = [];
            $three = timeToDate(0,-3);//三分钟前的时间
            //$three = timeToDate(0,-1);//三分钟前的时间
            $ten =  timeToDate(0,-15);//十分钟
           // $ten =  timeToDate(0,-1000);//十分钟

            //总订单数 total_orders //总订单金额 total_fee_all //已支付金额 total_fee_paid //已支付订单数 total_paid //通道ID //支付类型 // 通道分组
            $sql = "select count(1) as total_orders,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,amount,0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,channel_id,payment_id,channel_group_id,create_at from cm_order where  create_at BETWEEN ? AND ? GROUP BY channel_id  ORDER BY id DESC ";//每个通道的成功率
            $data['channel'] =  Db::query($sql, [$ten,$three]);


            $ChannelGroup =  ChannelGroup::idArr();//通道分组
            $Channel =  Channel::idRate();//通道
            $PayProduct =  PayProduct::idArr();//支付产品

            $data['payment'] = [];
            $data['channel_group'] = [];
            foreach ($data['channel'] as $k => $v){
                $data['channel'][$k]['product_name'] = empty($PayProduct[$v['payment_id']])?'未知':$PayProduct[$v['payment_id']];
                $channelgroup_name = empty($ChannelGroup[$v['channel_group_id']])?'未知':$ChannelGroup[$v['channel_group_id']];
                $data['channel'][$k]['channel_name'] = empty($Channel[$v['channel_id']])?'未知':$Channel[$v['channel_id']]['title'];
                $data['channel'][$k]['rate'] =  round($data['channel'][$k]['total_paid']/$data['channel'][$k]['total_orders'],3)*100;

                //支付类型
                empty( $data['payment'][$v['payment_id']]['total_orders']) &&   $data['payment'][$v['payment_id']]['total_orders']= 0;
                empty($data['payment'][$v['payment_id']]['total_fee_all']) &&  $data['payment'][$v['payment_id']]['total_fee_all']= 0;
                empty($data['payment'][$v['payment_id']]['total_fee_paid']) &&  $data['payment'][$v['payment_id']]['total_fee_paid']= 0;
                empty($data['payment'][$v['payment_id']]['total_paid']) &&  $data['payment'][$v['payment_id']]['total_paid']= 0;
      


                $data['payment'][$v['payment_id']]['total_orders'] += $v['total_orders'];
                $data['payment'][$v['payment_id']]['total_fee_all'] += $v['total_fee_all'];
                $data['payment'][$v['payment_id']]['total_fee_paid'] += $v['total_fee_paid'];
                $data['payment'][$v['payment_id']]['total_paid'] += $v['total_paid'];
                
                $data['payment'][$v['payment_id']]['channel_id'] = $v['channel_id'];
                $data['payment'][$v['payment_id']]['payment_id'] = $v['payment_id'];
                $data['payment'][$v['payment_id']]['channel_group_id'] = $v['channel_group_id'];

                $data['payment'][$v['payment_id']]['title'] = $data['channel'][$k]['product_name'];
                $data['payment'][$v['payment_id']]['rate'] =  round($data['payment'][$v['payment_id']]['total_paid']/$data['payment'][$v['payment_id']]['total_orders'],3)*100;


                //通道分组
                empty( $data['channel_group'][$v['channel_group_id']]['total_orders']) &&   $data['channel_group'][$v['channel_group_id']]['total_orders']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['total_fee_all']) &&  $data['channel_group'][$v['channel_group_id']]['total_fee_all']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['total_fee_paid']) &&  $data['channel_group'][$v['channel_group_id']]['total_fee_paid']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['total_paid']) &&  $data['channel_group'][$v['channel_group_id']]['total_paid']= 0;


                $data['channel_group'][$v['channel_group_id']]['total_orders'] += $v['total_orders'];
                $data['channel_group'][$v['channel_group_id']]['total_fee_all'] += $v['total_fee_all'];
                $data['channel_group'][$v['channel_group_id']]['total_fee_paid'] += $v['total_fee_paid'];
                $data['channel_group'][$v['channel_group_id']]['total_paid'] += $v['total_paid'];
                
                $data['channel_group'][$v['channel_group_id']]['channel_id'] = $v['channel_id'];
                $data['channel_group'][$v['channel_group_id']]['payment_id'] = $v['payment_id'];
                $data['channel_group'][$v['channel_group_id']]['channel_group_id'] = $v['channel_group_id'];

                $data['channel_group'][$v['channel_group_id']]['title'] =  $channelgroup_name;
                $data['channel_group'][$v['channel_group_id']]['rate'] =  round($data['channel_group'][$v['channel_group_id']]['total_paid']/$data['channel_group'][$v['channel_group_id']]['total_orders'],3)*100;

            }
            $data['time'] = timeToDate();

            return  $data;
        },180);

        return \think\facade\Cache::get('success_rate');

    }

    //商户每日对账  统计  10分钟一次
    public static function mem_account(){

        Cache::remember('mem_account', function () {
            $data = [];
            $insert = [];
            $update = [];

            $Accounts = model('app\common\model\Accounts');

            $day = $Accounts->where([['uid','>',0],['type','=',0]])->order(['day desc'])->cache('account_uid',60)->value('day');
            $now = date('Y-m-d H:i:s',time());//现在 需要统计的结束时间

            if(empty($day)){
                $day = '2019-01-01 00:00:00';
            }else{
                $day = $day.' 00:00:00';//需要统计的起始时间
            }

            //商户每天的 通道支付订单统计
            $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,if(actual_amount=0,amount,actual_amount),0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,COALESCE(sum(if(pay_status=2,total_fee,0)),0) as total_fee,COALESCE(sum(if(pay_status=2,platform,0)),0) as platform,mch_id,payment_id from cm_order where create_at BETWEEN ? AND ? GROUP BY day,mch_id,payment_id ORDER BY id DESC ";//每个通道的成功率
            $select =  Db::query($sql,[$day,$now]);

            $PayProduct =  PayProduct::idArr();//支付产品
            foreach ($select as $k => $v) {

                $v['product_name'] = empty($PayProduct[$v['payment_id']]) ? '未知' : $PayProduct[$v['payment_id']];
                $v['rate'] = round($v['total_paid'] / $v['total_orders'], 3) * 100;

                //商户单日 通道分析
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['mch_id'] = $v['mch_id'];
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['payment_id'] = $v['payment_id'];
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['product_name'] = $v['product_name'];
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['day'] = $v['day'];

                empty($data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_orders']) && $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_orders']= 0;
                empty($data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_all']) && $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_all']= 0;
                empty($data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_paid']) && $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_paid']= 0;
                empty($data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_paid']) && $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_paid']= 0;
                empty($data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['rate']) && $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['rate']= 0;
                empty($data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee']) && $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee']= 0;
                empty($data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['platform']) && $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['platform']= 0;

               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_orders'] += $v['total_orders'];
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_all'] += $v['total_fee_all'];
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_paid'] += $v['total_fee_paid'];
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_paid'] += $v['total_paid'];
               $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['rate'] += $v['rate'];
                $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee'] += $v['total_fee'];
                $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['platform'] += $v['platform'];


               //商户每日对账
                 $data['account'][$v['mch_id']][$v['day']]['uid'] = $v['mch_id'];
                 $data['account'][$v['mch_id']][$v['day']]['day'] = $v['day'];

                $id =  $Accounts->where(['uid'=>$v['mch_id'],'day'=>$v['day']])->cache($v['mch_id'].$v['day'],30)->value('id');


                empty( $data['account'][$v['mch_id']][$v['day']]['total_orders']) &&  $data['account'][$v['mch_id']][$v['day']]['total_orders']= 0;
                empty( $data['account'][$v['mch_id']][$v['day']]['total_fee_all']) &&  $data['account'][$v['mch_id']][$v['day']]['total_fee_all']= 0;
                empty( $data['account'][$v['mch_id']][$v['day']]['total_fee_paid']) &&  $data['account'][$v['mch_id']][$v['day']]['total_fee_paid']= 0;
                empty( $data['account'][$v['mch_id']][$v['day']]['total_paid']) &&  $data['account'][$v['mch_id']][$v['day']]['total_paid']= 0;
                empty( $data['account'][$v['mch_id']][$v['day']]['rate']) &&  $data['account'][$v['mch_id']][$v['day']]['rate']= 0;
                empty( $data['account'][$v['mch_id']][$v['day']]['total_fee']) &&  $data['account'][$v['mch_id']][$v['day']]['total_fee']= 0;
                empty( $data['account'][$v['mch_id']][$v['day']]['platform']) &&  $data['account'][$v['mch_id']][$v['day']]['platform']= 0;

                 $data['account'][$v['mch_id']][$v['day']]['total_orders'] += $v['total_orders'];
                 $data['account'][$v['mch_id']][$v['day']]['total_fee_all'] += $v['total_fee_all'];
                 $data['account'][$v['mch_id']][$v['day']]['total_fee_paid'] += $v['total_fee_paid'];
                 $data['account'][$v['mch_id']][$v['day']]['total_paid'] += $v['total_paid'];
                 $data['account'][$v['mch_id']][$v['day']]['rate'] += $v['rate'];
                $data['account'][$v['mch_id']][$v['day']]['total_fee'] += $v['total_fee'];
                $data['account'][$v['mch_id']][$v['day']]['platform'] += $v['platform'];


                $data['account'][$v['mch_id']][$v['day']]['info'] = json_encode(!isset($data['payment'][$v['mch_id']][$v['day']])?'':$data['payment'][$v['mch_id']][$v['day']]);

                if(!empty($id)){
                    $data['account'][$v['mch_id']][$v['day']]['id'] = $id;
                    $update[$v['mch_id'].$v['day']] = $data['account'][$v['mch_id']][$v['day']]; //数据库更新记录的数据
                }else{
                    $insert[$v['mch_id'].$v['day']] = $data['account'][$v['mch_id']][$v['day']]; //数据库没有记录的数据
                }
            }

            //插入/更新每日对账表
            if(!empty($insert)) $Accounts->isUpdate(false)->saveAll($insert);
            if(!empty($update)) $Accounts->isUpdate(true)->saveAll($update);

            return  $data['account'];
        },600);

        return true;
    }


    // 代理 通道分组每日分析 10分钟一次
    public static function agent_account(){

        Cache::remember('agent_account', function () {
            $data = [];
            $insert = [];
            $update = [];

            $Accounts = model('app\common\model\Accounts');

            $day = $Accounts->where([['uid', '>', 0],['type', '=', 1]])->order(['day desc'])->cache('agent_account_id',1)->value('day');
            $now = date('Y-m-d H:i:s',time());//现在 需要统计的结束时间
            if(empty($day)){
                $day = '2019-01-01 00:00:00';
            }else{
                $day = $day.' 00:00:00';//需要统计的起始时间
            }

            //商户每天的 通道支付订单统计
            $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,if(actual_amount=0,amount,actual_amount),0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,COALESCE(sum(if(pay_status=2,total_fee,0)),0) as total_fee,COALESCE(sum(if(pay_status=2,agent_amount,0)),0) as agent_amount,COALESCE(sum(if(pay_status=2,agent_amount2,0)),0) as agent_amount2,mch_id2,mch_id1,channel_group_id,payment_id from cm_order  where create_at BETWEEN ? AND ? AND mch_id2 > 0 or mch_id1 > 0  GROUP BY day,mch_id2,mch_id1,channel_group_id,payment_id ORDER BY id DESC ";//每个通道的成功率
            $select =  Db::query($sql,[$day,$now]);

            $ChannelGroup =  ChannelGroup::idArr();//通道分组
            $PayProduct =  PayProduct::idArr();//支付产品
            foreach ($select as $k => $v) {

                $v['channelgroup_name'] = empty($ChannelGroup[$v['channel_group_id']])?'未知':$ChannelGroup[$v['channel_group_id']];
                $v['product_name'] = empty($PayProduct[$v['payment_id']]) ? '未知' : $PayProduct[$v['payment_id']];
                $v['rate'] = round($v['total_paid'] / $v['total_orders'], 3) * 100;


                //单日 通道分组分析
                if(!empty($v['mch_id1'])){
                    $v['platform'] = $v['agent_amount'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['uid'] = $v['mch_id1'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['day'] = $v['day'];

                    $id1 =  $Accounts->where(['uid'=>$v['mch_id1'],'day'=>$v['day']])->cache($v['mch_id1'].$v['day'],30)->value('id');

                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['channelgroup_name'] = $v['channelgroup_name'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['product_name'] = $v['product_name'];


                    empty( $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_orders']) &&  $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_orders']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_all']) &&  $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_all']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_paid']) &&  $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_paid']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_paid']) &&  $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_paid']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['rate']) &&  $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['rate']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee']) &&  $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['platform']) &&  $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['platform']= 0;

                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_orders'] += $v['total_orders'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_all'] += $v['total_fee_all'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_paid'] += $v['total_fee_paid'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_paid'] += $v['total_paid'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['rate'] += $v['rate'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee'] += $v['total_fee'];
                    $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['platform'] += $v['platform'];


                    $data['agent'][$v['day']][$v['mch_id1']]['uid'] = $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['uid'];
                    $data['agent'][$v['day']][$v['mch_id1']]['day'] = $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['day'];


                    empty( $data['agent'][$v['day']][$v['mch_id1']]['total_orders']) &&  $data['agent'][$v['day']][$v['mch_id1']]['total_orders']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id1']]['total_fee_all']) &&  $data['agent'][$v['day']][$v['mch_id1']]['total_fee_all']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id1']]['total_fee_paid']) &&  $data['agent'][$v['day']][$v['mch_id1']]['total_fee_paid']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id1']]['total_paid']) &&  $data['agent'][$v['day']][$v['mch_id1']]['total_paid']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id1']]['rate']) &&  $data['agent'][$v['day']][$v['mch_id1']]['rate']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id1']]['total_fee']) &&  $data['agent'][$v['day']][$v['mch_id1']]['total_fee']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id1']]['platform']) &&  $data['agent'][$v['day']][$v['mch_id1']]['platform']= 0;

                    $data['agent'][$v['day']][$v['mch_id1']]['total_orders'] += $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_orders'];
                    $data['agent'][$v['day']][$v['mch_id1']]['total_fee_all'] += $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_all'];
                    $data['agent'][$v['day']][$v['mch_id1']]['total_fee_paid'] += $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee_paid'];
                    $data['agent'][$v['day']][$v['mch_id1']]['total_paid'] += $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_paid'];
                    $data['agent'][$v['day']][$v['mch_id1']]['rate'] += $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['rate'];
                    $data['agent'][$v['day']][$v['mch_id1']]['total_fee'] += $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['total_fee'];
                    $data['agent'][$v['day']][$v['mch_id1']]['platform'] += $data['channelgroup'][$v['day']][$v['mch_id1']][$v['channel_group_id']]['platform'];


                    $data['agent'][$v['day']][$v['mch_id1']]['info'] = json_encode($data['channelgroup'][$v['day']][$v['mch_id1']]);
                    $data['agent'][$v['day']][$v['mch_id1']]['type'] = 1;//代理

                    if(!empty($id1)){
                        $data['agent'][$v['day']][$v['mch_id1']]['id'] = $id1;
                        $update[$v['mch_id1'].$v['day']] = $data['agent'][$v['day']][$v['mch_id1']]; //数据库更新记录的数据
                    }else{
                        $insert[$v['mch_id1'].$v['day']] = $data['agent'][$v['day']][$v['mch_id1']]; //数据库没有记录的数据
                    }
                }

                if(!empty($v['mch_id2'])){
                    $v['platform'] = $v['agent_amount2'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['uid'] = $v['mch_id2'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['day'] = $v['day'];

                    $id2 =  $Accounts->where(['uid'=>$v['mch_id2'],'day'=>$v['day']])->cache($v['mch_id2'].$v['day'],30)->value('id');//是否存在

                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['channelgroup_name'] = $v['channelgroup_name'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['product_name'] = $v['product_name'];


                    empty( $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_orders']) &&  $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_orders']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_all']) &&  $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_all']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_paid']) &&  $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_paid']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_paid']) &&  $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_paid']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['rate']) &&  $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['rate']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee']) &&  $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee']= 0;
                    empty( $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['platform']) &&  $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['platform']= 0;

                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_orders'] += $v['total_orders'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_all'] += $v['total_fee_all'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_paid'] += $v['total_fee_paid'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_paid'] += $v['total_paid'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['rate'] += $v['rate'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee'] += $v['total_fee'];
                    $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['platform'] += $v['platform'];


                    $data['agent'][$v['day']][$v['mch_id2']]['uid'] = $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['uid'];
                    $data['agent'][$v['day']][$v['mch_id2']]['day'] = $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['day'];


                    empty( $data['agent'][$v['day']][$v['mch_id2']]['total_orders']) &&  $data['agent'][$v['day']][$v['mch_id2']]['total_orders']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id2']]['total_fee_all']) &&  $data['agent'][$v['day']][$v['mch_id2']]['total_fee_all']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id2']]['total_fee_paid']) &&  $data['agent'][$v['day']][$v['mch_id2']]['total_fee_paid']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id2']]['total_paid']) &&  $data['agent'][$v['day']][$v['mch_id2']]['total_paid']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id2']]['rate']) &&  $data['agent'][$v['day']][$v['mch_id2']]['rate']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id2']]['total_fee']) &&  $data['agent'][$v['day']][$v['mch_id2']]['total_fee']= 0;
                    empty( $data['agent'][$v['day']][$v['mch_id2']]['platform']) &&  $data['agent'][$v['day']][$v['mch_id2']]['platform']= 0;

                    $data['agent'][$v['day']][$v['mch_id2']]['total_orders'] += $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_orders'];
                    $data['agent'][$v['day']][$v['mch_id2']]['total_fee_all'] += $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_all'];
                    $data['agent'][$v['day']][$v['mch_id2']]['total_fee_paid'] += $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee_paid'];
                    $data['agent'][$v['day']][$v['mch_id2']]['total_paid'] += $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_paid'];
                    $data['agent'][$v['day']][$v['mch_id2']]['rate'] += $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['rate'];
                    $data['agent'][$v['day']][$v['mch_id2']]['total_fee'] += $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['total_fee'];
                    $data['agent'][$v['day']][$v['mch_id2']]['platform'] += $data['channelgroup'][$v['day']][$v['mch_id2']][$v['channel_group_id']]['platform'];


                    $data['agent'][$v['day']][$v['mch_id2']]['info'] = json_encode($data['channelgroup'][$v['day']][$v['mch_id2']]);
                    $data['agent'][$v['day']][$v['mch_id2']]['type'] = 1;//代理

                    if(!empty($id2)){
                        $data['agent'][$v['day']][$v['mch_id2']]['id'] = $id2;
                        $update[$v['mch_id2'].$v['day']] = $data['agent'][$v['day']][$v['mch_id2']]; //数据库更新记录的数据
                    }else{
                        $insert[$v['mch_id2'].$v['day']] = $data['agent'][$v['day']][$v['mch_id2']]; //数据库没有记录的数据
                    }
                }
            }
            //插入每日对账表
            if(!empty($insert)) $Accounts->isUpdate(false)->saveAll($insert);
            if(!empty($update)) $Accounts->isUpdate(true)->saveAll($update);

            return  $data['agent'];
        },600);

        return true;
    }


    //支付通道每日对账
    public static function channel_account(){

        Cache::remember('agent_account', function () {

            $data = [];
            $insert = [];
            $update = [];
            $Accounts = model('app\common\model\Accounts');

            $day = $Accounts->where([['channel_id','>',0]])->order(['day desc'])->cache('account_channel_id',1)->value('day');
            $now = date('Y-m-d H:i:s',time());//现在 需要统计的结束时间
            if(empty($day)){
                $day = '2019-01-01 00:00:00';
            }else{
                $day = $day.' 00:00:00';//需要统计的起始时间
            }

            //商户每天的 通道支付订单统计
            $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,if(actual_amount=0,amount,actual_amount),0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,COALESCE(sum(if(pay_status=2,upstream_settle,0)),0) as total_fee,COALESCE(sum(if(pay_status=2,platform,0)),0) as platform,channel_id,payment_id from cm_order where create_at BETWEEN ? AND ? GROUP BY day,channel_id,payment_id ORDER BY id DESC ";//每个通道的成功率
            $select =  Db::query($sql,[$day,$now]);

            $Channel =  Channel::idRate();//通道
            $PayProduct =  PayProduct::idArr();//支付产品
            foreach ($select as $k => $v) {

                $v['channel_name'] = empty($Channel[$v['channel_id']])?'未知':$Channel[$v['channel_id']]['title'];
                $v['pid'] = empty($Channel[$v['channel_id']])?'0':$Channel[$v['channel_id']]['pid'];

                $v['product_name'] = empty($PayProduct[$v['payment_id']]) ? '未知' : $PayProduct[$v['payment_id']];
                $v['rate'] = round($v['total_paid'] / $v['total_orders'], 3) * 100;


                //单日 通道产品分析
                $data['channel'][$v['day']][$v['pid']][$v['channel_id']] = $v;

               //单日 支付通道分析

                $data['channel_father'][$v['day']]['channel_id'] = $v['pid'];
                $data['channel_father'][$v['day']]['day'] = $v['day'];

                $id =  $Accounts->where(['channel_id'=>$v['pid'],'day'=>$v['day']])->cache($v['channel_id'].$v['day'],30)->value('id');

                empty( $data['channel_father'][$v['day']]['total_orders']) &&  $data['channel_father'][$v['day']]['total_orders']= 0;
                empty( $data['channel_father'][$v['day']]['total_fee_all']) &&  $data['channel_father'][$v['day']]['total_fee_all']= 0;
                empty( $data['channel_father'][$v['day']]['total_fee_paid']) &&  $data['channel_father'][$v['day']]['total_fee_paid']= 0;
                empty( $data['channel_father'][$v['day']]['total_paid']) &&  $data['channel_father'][$v['day']]['total_paid']= 0;
                empty( $data['channel_father'][$v['day']]['rate']) &&  $data['channel_father'][$v['day']]['rate']= 0;
                empty( $data['channel_father'][$v['day']]['total_fee']) &&  $data['channel_father'][$v['day']]['total_fee']= 0;
                empty( $data['channel_father'][$v['day']]['platform']) &&  $data['channel_father'][$v['day']]['platform']= 0;

                $data['channel_father'][$v['day']]['total_orders'] +=  $data['channel'][$v['day']][$v['pid']][$v['channel_id']]['total_orders'];
                $data['channel_father'][$v['day']]['total_fee_all'] +=  $data['channel'][$v['day']][$v['pid']][$v['channel_id']]['total_fee_all'];
                $data['channel_father'][$v['day']]['total_fee_paid'] +=  $data['channel'][$v['day']][$v['pid']][$v['channel_id']]['total_fee_paid'];
                $data['channel_father'][$v['day']]['total_paid'] +=  $data['channel'][$v['day']][$v['pid']][$v['channel_id']]['total_paid'];
                $data['channel_father'][$v['day']]['rate'] +=  $data['channel'][$v['day']][$v['pid']][$v['channel_id']]['rate'];
                $data['channel_father'][$v['day']]['total_fee'] +=  $data['channel'][$v['day']][$v['pid']][$v['channel_id']]['total_fee'];

                $data['channel_father'][$v['day']]['platform'] +=  $data['channel'][$v['day']][$v['pid']][$v['channel_id']]['platform'];
                $data['channel_father'][$v['day']]['title'] =   empty($Channel[$v['pid']])?'未知':$Channel[$v['pid']]['title'];;

                $data['channel_father'][$v['day']]['info'] = json_encode(!isset($data['channel'][$v['day']][$v['pid']])?'':$data['channel'][$v['day']][$v['pid']]);


                if(!empty($id)){
                    $data['channel_father'][$v['day']]['id'] = $id;
                    $update[$v['pid'].$v['day']] = $data['channel_father'][$v['day']]; //数据库更新记录的数据
                }else{
                    $insert[$v['pid'].$v['day']] = $data['channel_father'][$v['day']]; //数据库没有记录的数据
                }

            }

            //插入每日对账表
            if(!empty($insert)) $Accounts->isUpdate(false)->saveAll($insert);
            if(!empty($update)) $Accounts->isUpdate(true)->saveAll($update);

        },600);

        return true;
    }

    //提现结算每日对账
    public static function withdraw_account(){
        $data = [];
        $insert = [];
        $update = [];
        $Accounts = model('app\common\model\Accounts');

        $day = $Accounts->where([['withdraw_id','>',0]])->order(['day desc'])->cache('account_withdraw_id',3)->value('day');
        $now = date('Y-m-d H:i:s',time());//现在 需要统计的结束时间
        if(empty($day)){
            $day = '2019-01-01 00:00:00';
        }else{
            $day = $day.' 00:00:00';//需要统计的起始时间
        }

        //商户每天的 通道支付订单统计
        $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(status=3,channel_amount,0)),0) as total_fee_paid,COALESCE(sum(if(status=3,1,0)),0) as total_paid,COALESCE(sum(if(status=3,fee,0)),0) as total_fee,COALESCE(sum(if(status=3,channel_fee,0)),0) as platform,channel_id,mch_id from cm_withdrawal where create_at BETWEEN ? AND ? GROUP BY day,channel_id,mch_id ORDER BY id DESC ";//每个通道的成功率
        $select =  Db::query($sql,[$day,$now]);


        $Channel =  Channel::idRate();//通道
        foreach ($select as $k => $v) {
            $channel_name = empty($Channel[$v['channel_id']])?'未选择下发通道':$Channel[$v['channel_id']]['title'];


            //商户的下发统计
            $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']][$v['channel_id']]['mch_id'] = $v['mch_id'];
            $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['day'] = $v['day'];


            empty( $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_orders']) &&  $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_orders']= 0;
            empty( $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_all']) &&  $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_all']= 0;
            empty( $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_paid']) &&  $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_paid']= 0;
            empty( $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_paid']) &&  $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_paid']= 0;
            empty( $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee']) &&  $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee']= 0;
            empty( $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['platform']) &&  $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['platform']= 0;

            $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_orders'] += $v['total_orders'];
            $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_all'] += $v['total_fee_all'];
            $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_paid'] += $v['total_fee_paid'];
            $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_paid'] += $v['total_paid'];
            $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee'] += $v['total_fee'];


            //下发通道的统计

            $data['channel'][$v['day']]['withdraw_id'] = $v['channel_id'];
            $data['channel'][$v['day']]['day'] = $v['day'];

            $id =  $Accounts->where(['withdraw_id'=>$v['channel_id'],'day'=>$v['day']])->cache($v['channel_id'].$v['day'].'_',30)->value('id');

            $data['channel'][$v['day']]['title'] = $channel_name;


            empty( $data['channel'][$v['day']]['total_orders']) &&  $data['channel'][$v['day']]['total_orders']= 0;
            empty( $data['channel'][$v['day']]['total_fee_all']) &&  $data['channel'][$v['day']]['total_fee_all']= 0;
            empty( $data['channel'][$v['day']]['total_fee_paid']) &&  $data['channel'][$v['day']]['total_fee_paid']= 0;
            empty( $data['channel'][$v['day']]['total_paid']) &&  $data['channel'][$v['day']]['total_paid']= 0;
            empty( $data['channel'][$v['day']]['total_fee']) &&  $data['channel'][$v['day']]['total_fee']= 0;
            empty( $data['channel'][$v['day']]['platform']) &&  $data['channel'][$v['day']]['platform']= 0;

            $data['channel'][$v['day']]['total_orders'] += $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_orders'];
            $data['channel'][$v['day']]['total_fee_all'] += $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_all'];
            $data['channel'][$v['day']]['total_fee_paid'] += $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee_paid'];
            $data['channel'][$v['day']]['total_paid'] += $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_paid'];
            $data['channel'][$v['day']]['total_fee'] += $data['merch'][$v['day']][$v['channel_id']][$v['mch_id']]['total_fee'];

            $data['channel'][$v['day']]['info'] = json_encode(!isset($data['merch'][$v['day']][$v['channel_id']])?'':$data['merch'][$v['day']][$v['channel_id']]);

            $data['channel'][$v['day']]['type'] = 4;

            if(!empty($id)){
                $data['channel'][$v['day']][$v['channel_id']]['id'] = $id;
                $update[$v['channel_id'].$v['day']] = $data['channel'][$v['day']]; //数据库更新记录的数据
            }else{
                $insert[$v['channel_id'].$v['day']] = $data['channel'][$v['day']]; //数据库没有记录的数据
            }
        }

        halt($insert);


        //插入每日对账表
        if(!empty($insert)) $Accounts->isUpdate(false)->saveAll($insert);
        if(!empty($update)) $Accounts->isUpdate(true)->saveAll($update);

        return true;
    }




    //代付每日对账
    public static function df_account(){
        $data = [];
        $insert = [];
        $update = [];
        $Accounts = model('app\common\model\Accounts');

        $day = $Accounts->where([['df_id','>',0]])->order(['day desc'])->cache('account_df_id',3)->value('day');
        $now = date('Y-m-d H:i:s',time());//现在 需要统计的结束时间
        if(empty($day)){
            $day = '2019-01-01 00:00:00';
        }else{
            $day = $day.' 00:00:00';//需要统计的起始时间
        }

        //商户每天的 通道支付订单统计
        $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(status=3,channel_amount,0)),0) as total_fee_paid,COALESCE(sum(if(status=3,1,0)),0) as total_paid,COALESCE(sum(if(status=3,fee,0)),0) as total_fee,COALESCE(sum(if(status=3,channel_fee,0)),0) as platform,channel_id,mch_id from cm_withdrawal_api where create_at BETWEEN ? AND ? GROUP BY day,channel_id,mch_id ORDER BY id DESC ";//每个通道的成功率
        $select =  Db::query($sql,[$day,$now]);



    }










}