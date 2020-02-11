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
            $ten =  timeToDate(0,-10);//十分钟

            //总订单数 total_orders //总订单金额 total_fee_all //已支付金额 total_fee_paid //已支付订单数 total_paid //通道ID //支付类型 // 通道分组
            $sql = "select count(1) as total_orders,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,amount,0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,channel_id,payment_id,channel_group_id from cm_order where  create_at BETWEEN ? AND ? GROUP BY channel_id  ORDER BY id DESC ";//每个通道的成功率
            $data['channel'] =  Db::query($sql, [$ten,$three]);

            $ChannelGropu =  ChannelGroup::idArr();//通道分组
            $Channel =  Channel::idRate();//通道
            $PayProduct =  PayProduct::idArr();//支付产品

            $data['payment'] = [];
            $data['channel_group'] = [];
            foreach ($data['channel'] as $k => $v){
                $data['channel'][$k]['product_name'] = empty($PayProduct[$v['payment_id']])?'未知':$PayProduct[$v['payment_id']];
                $data['channel'][$k]['channelgroup_name'] = empty($ChannelGroup[$v['channel_group_id']])?'未知':$ChannelGroup[$v['channel_group_id']];
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

                $data['channel_group'][$v['channel_group_id']]['title'] =  $data['channel'][$k]['channelgroup_name'];
                $data['channel_group'][$v['channel_group_id']]['rate'] =  round($data['channel_group'][$v['channel_group_id']]['total_paid']/$data['channel_group'][$v['channel_group_id']]['total_orders'],3)*100;

            }
            $data['time'] = timeToDate();

            return  $data;
        },180);

        return \think\facade\Cache::get('success_rate');

    }

    //商户每日对账 深夜1-2 点统计
    public static function mem_account(){
        $data = [];
        $insert = [];
        //update_at
        $Accounts = model('app\common\model\Accounts');

        $day = $Accounts->where([['uid','>',0]])->order(['day desc'])->cache('account_uid',60)->value('day');
        if(empty($day)){
            $one = 0;
            $day = '2019-01-01 00:00:00';
        }else{
            $one  = strtotime("+1 day",strtotime($day));
            $day = date('Y-m-d ',$one).' 00:00:00';//需要统计的起始时间
        }
         $two  = strtotime("-1 day",time());
        $yestoday = date('Y-m-d ',$two).'23:59:59';//昨天 需要统计的结束时间

        //时间不对  不需要统计
        if($one > $two) return false;

        //商户每天的 通道支付订单统计
        $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,if(actual_amount=0,amount,actual_amount),0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,COALESCE(sum(if(pay_status=2,total_fee,0)),0) as total_fee,mch_id,payment_id from cm_order where create_at BETWEEN ? AND ? GROUP BY day,mch_id,payment_id ORDER BY id DESC ";//每个通道的成功率
        $select =  Db::query($sql,[$day,$yestoday]);

        $PayProduct =  PayProduct::idArr();//支付产品
        foreach ($select as $k => $v) {

            $time =  strtotime($v['day']);
            if($one > $time) continue; //不用记录的数据


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

           $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_orders'] += $v['total_orders'];
           $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_all'] += $v['total_fee_all'];
           $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee_paid'] += $v['total_fee_paid'];
           $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_paid'] += $v['total_paid'];
           $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['rate'] += $v['rate'];
            $data['payment'][$v['mch_id']][$v['day']][$v['payment_id']]['total_fee'] += $v['total_fee'];

           //商户每日对账
             $data['account'][$v['mch_id']][$v['day']]['uid'] = $v['mch_id'];
             $data['account'][$v['mch_id']][$v['day']]['day'] = $v['day'];

            empty( $data['account'][$v['mch_id']][$v['day']]['total_orders']) &&  $data['account'][$v['mch_id']][$v['day']]['total_orders']= 0;
            empty( $data['account'][$v['mch_id']][$v['day']]['total_fee_all']) &&  $data['account'][$v['mch_id']][$v['day']]['total_fee_all']= 0;
            empty( $data['account'][$v['mch_id']][$v['day']]['total_fee_paid']) &&  $data['account'][$v['mch_id']][$v['day']]['total_fee_paid']= 0;
            empty( $data['account'][$v['mch_id']][$v['day']]['total_paid']) &&  $data['account'][$v['mch_id']][$v['day']]['total_paid']= 0;
            empty( $data['account'][$v['mch_id']][$v['day']]['rate']) &&  $data['account'][$v['mch_id']][$v['day']]['rate']= 0;
            empty( $data['account'][$v['mch_id']][$v['day']]['total_fee']) &&  $data['account'][$v['mch_id']][$v['day']]['total_fee']= 0;

             $data['account'][$v['mch_id']][$v['day']]['total_orders'] += $v['total_orders'];
             $data['account'][$v['mch_id']][$v['day']]['total_fee_all'] += $v['total_fee_all'];
             $data['account'][$v['mch_id']][$v['day']]['total_fee_paid'] += $v['total_fee_paid'];
             $data['account'][$v['mch_id']][$v['day']]['total_paid'] += $v['total_paid'];
             $data['account'][$v['mch_id']][$v['day']]['rate'] += $v['rate'];
            $data['account'][$v['mch_id']][$v['day']]['total_fee'] += $v['total_fee'];


            $data['account'][$v['mch_id']][$v['day']]['info'] = json_encode(!isset($data['payment'][$v['mch_id']][$v['day']])?'':$data['payment'][$v['mch_id']][$v['day']]);

            $insert[$v['mch_id'].$v['day']] = $data['account'][$v['mch_id']][$v['day']]; //数据库没有记录的数据
        }

        //插入每日对账表
        if(!empty($insert)) return  $Accounts->saveAll($insert);

        return true;
    }


    //商户单日统计 每十分钟统计一次
    public static function mem_today_account(){

        Cache::remember('mem_today_account', function () {
            $today = date('Y-m-d ',time()).' 00:00:00';//今天
            //$today = '2019-01-01 00:00:00';
            $now = date('Y-m-d H:i:s',time());//现在
            $data['time'] = $now;

            $sql = "select count(1) as total_orders, COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,if(actual_amount=0,amount,actual_amount),0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,COALESCE(sum(if(pay_status=2,total_fee,0)),0) as total_fee,mch_id,create_at from cm_order where create_at BETWEEN ? AND ? GROUP BY mch_id ORDER BY id DESC ";//每个商户的的成功率

            $data['data'] =  Db::query($sql,[$today,$now]);
           if(!empty($data['data'])) $data['data'] = array_column($data['data'], null, 'mch_id');
           return  $data;

       },600);

       return \think\facade\Cache::get('mem_today_account');
    }

    //支付通道每日对账 深夜1-2 点统计
    public static function channel_account(){
        $data = [];
        $insert = [];
        //update_at
        $Accounts = model('app\common\model\Accounts');

        $day = $Accounts->where([['channel_id','>',0]])->order(['day desc'])->cache('account_channel_id',1)->value('day');
        if(empty($day)){
            $one = 0;
            $day = '2019-01-01 00:00:00';
        }else{
            $one  = strtotime("+1 day",strtotime($day));
            $day = date('Y-m-d ',$one).' 00:00:00';//需要统计的起始时间
        }
        $two  = strtotime("-1 day",time());
        $yestoday = date('Y-m-d ',$two).'23:59:59';//昨天 需要统计的结束时间

        //时间不对  不需要统计
        if($one > $two) return false;

        //商户每天的 通道支付订单统计
        $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,if(actual_amount=0,amount,actual_amount),0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,COALESCE(sum(if(pay_status=2,total_fee,0)),0) as total_fee,channel_id,payment_id from cm_order where create_at BETWEEN ? AND ? GROUP BY day,channel_id,payment_id ORDER BY id DESC ";//每个通道的成功率
        $select =  Db::query($sql,[$day,$yestoday]);


        $Channel =  Channel::idRate();//通道
        $PayProduct =  PayProduct::idArr();//支付产品
        foreach ($select as $k => $v) {
            $time =  strtotime($v['day']);
            if($one > $time) continue; //不用记录的数据

            $v['channel_name'] = empty($Channel[$v['channel_id']])?'未知':$Channel[$v['channel_id']]['title'];
            $v['pid'] = empty($Channel[$v['channel_id']])?'0':$Channel[$v['channel_id']]['pid'];

            $v['product_name'] = empty($PayProduct[$v['payment_id']]) ? '未知' : $PayProduct[$v['payment_id']];
            $v['rate'] = round($v['total_paid'] / $v['total_orders'], 3) * 100;

            //单日 通道产品分析
            $data['channel'][$v['day']][$v['channel_id']] = $v;

           //单日 支付通道分析

            $data['channel_father'][$v['day']]['channel_id'] = $v['pid'];
            $data['channel_father'][$v['day']]['day'] = $v['day'];

            empty( $data['channel_father'][$v['day']]['total_orders']) &&  $data['channel_father'][$v['day']]['total_orders']= 0;
            empty( $data['channel_father'][$v['day']]['total_fee_all']) &&  $data['channel_father'][$v['day']]['total_fee_all']= 0;
            empty( $data['channel_father'][$v['day']]['total_fee_paid']) &&  $data['channel_father'][$v['day']]['total_fee_paid']= 0;
            empty( $data['channel_father'][$v['day']]['total_paid']) &&  $data['channel_father'][$v['day']]['total_paid']= 0;
            empty( $data['channel_father'][$v['day']]['rate']) &&  $data['channel_father'][$v['day']]['rate']= 0;
            empty( $data['channel_father'][$v['day']]['total_fee']) &&  $data['channel_father'][$v['day']]['total_fee']= 0;

            $data['channel_father'][$v['day']]['total_orders'] += $v['total_orders'];
            $data['channel_father'][$v['day']]['total_fee_all'] += $v['total_fee_all'];
            $data['channel_father'][$v['day']]['total_fee_paid'] += $v['total_fee_paid'];
            $data['channel_father'][$v['day']]['total_paid'] += $v['total_paid'];
            $data['channel_father'][$v['day']]['rate'] += $v['rate'];
            $data['channel_father'][$v['day']]['total_fee'] += $v['total_fee'];

            $data['channel_father'][$v['day']]['info'] = json_encode(!isset($data['channel'][$v['day']])?'':$data['channel'][$v['day']]);

            $insert[$v['pid'].$v['day']] = $data['channel_father'][$v['day']]; //数据库没有记录的数据

        }

        //插入每日对账表
        if(!empty($insert)) return  $Accounts->saveAll($insert);

        return true;
    }


    // 代理 通道分组每日分析 深夜1-2 点统计
    public static function agent_account(){
        $data = [];
        $insert = [];
        //update_at
        $Accounts = model('app\common\model\Accounts');

        $day = $Accounts->where([['uid','>',0]])->order(['day desc'])->cache('account_channel_id',1)->value('day');
        if(empty($day)){
            $one = 0;
            $day = '2019-01-01 00:00:00';
        }else{
            $one  = strtotime("+1 day",strtotime($day));
            $day = date('Y-m-d ',$one).' 00:00:00';//需要统计的起始时间
        }
        $two  = strtotime("-1 day",time());
        $yestoday = date('Y-m-d ',$two).'23:59:59';//昨天 需要统计的结束时间

        //时间不对  不需要统计
        if($one > $two) return false;

        //商户每天的 通道支付订单统计
        $sql = "select count(1) as total_orders, left(create_at, 10) as day,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,if(actual_amount=0,amount,actual_amount),0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,COALESCE(sum(if(pay_status=2,total_fee,0)),0) as total_fee,mch_id2,mch_id1,channel_group_id,payment_id from cm_order where create_at BETWEEN ? AND ? GROUP BY day,mch_id2,mch_id1,channel_group_id,payment_id ORDER BY id DESC ";//每个通道的成功率
        $select =  Db::query($sql,[$day,$yestoday]);
        //agent_amount2  //agent_amount  //upstream_settle 上游结算  //settle  //Platform 平台收益  0 为普通商户 其他代理商户

        $Channel =  Channel::idRate();//通道
        $PayProduct =  PayProduct::idArr();//支付产品
        foreach ($select as $k => $v) {
            $time =  strtotime($v['day']);
            if($one > $time) continue; //不用记录的数据

            $v['channel_name'] = empty($Channel[$v['channel_id']])?'未知':$Channel[$v['channel_id']]['title'];
            $v['pid'] = empty($Channel[$v['channel_id']])?'0':$Channel[$v['channel_id']]['pid'];

            $v['product_name'] = empty($PayProduct[$v['payment_id']]) ? '未知' : $PayProduct[$v['payment_id']];
            $v['rate'] = round($v['total_paid'] / $v['total_orders'], 3) * 100;

            //单日 通道产品分析
            $data['channel'][$v['day']][$v['channel_id']] = $v;

            //单日 支付通道分析

            $data['channel_father'][$v['day']]['channel_id'] = $v['pid'];
            $data['channel_father'][$v['day']]['day'] = $v['day'];

            empty( $data['channel_father'][$v['day']]['total_orders']) &&  $data['channel_father'][$v['day']]['total_orders']= 0;
            empty( $data['channel_father'][$v['day']]['total_fee_all']) &&  $data['channel_father'][$v['day']]['total_fee_all']= 0;
            empty( $data['channel_father'][$v['day']]['total_fee_paid']) &&  $data['channel_father'][$v['day']]['total_fee_paid']= 0;
            empty( $data['channel_father'][$v['day']]['total_paid']) &&  $data['channel_father'][$v['day']]['total_paid']= 0;
            empty( $data['channel_father'][$v['day']]['rate']) &&  $data['channel_father'][$v['day']]['rate']= 0;
            empty( $data['channel_father'][$v['day']]['total_fee']) &&  $data['channel_father'][$v['day']]['total_fee']= 0;

            $data['channel_father'][$v['day']]['total_orders'] += $v['total_orders'];
            $data['channel_father'][$v['day']]['total_fee_all'] += $v['total_fee_all'];
            $data['channel_father'][$v['day']]['total_fee_paid'] += $v['total_fee_paid'];
            $data['channel_father'][$v['day']]['total_paid'] += $v['total_paid'];
            $data['channel_father'][$v['day']]['rate'] += $v['rate'];
            $data['channel_father'][$v['day']]['total_fee'] += $v['total_fee'];

            $data['channel_father'][$v['day']]['info'] = json_encode(!isset($data['channel'][$v['day']])?'':$data['channel'][$v['day']]);

            $insert[$v['pid'].$v['day']] = $data['channel_father'][$v['day']]; //数据库没有记录的数据

        }

        //插入每日对账表
        if(!empty($insert)) return  $Accounts->saveAll($insert);

        return true;
    }


    //代付通道每日对账 深夜1-2 点统计
    public static function df_account(){

    }










}