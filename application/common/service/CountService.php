<?php

namespace app\common\service;
use app\common\model\Channel;
use app\common\model\Order;
use app\common\model\Umoney;
use think\Db;


/**
 * 统计
 * @package service
 */
class CountService {

    /**
     * 通道成功率 3-10分钟的成功率 3分钟统计一次
     * @return bool
     */
    public static function success_rate(){
        $data = [];
        $three = timeToDate(0,3);//三分钟前的时间
        //$ten =  timeToDate(0,10);//十分钟
        $ten = timeToDate(0,0,0,-14);

        dump($three);
        dump($ten);

        //总订单数 total_orders //总订单金额 total_fee_all //已支付金额 total_fee_paid //已支付订单数 total_paid //通道ID //支付类型 // 通道分组
        $sql = "select count(1) as total_orders,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,amount,0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,channel_id,payment_id,channel_group_id from cm_order where  create_at BETWEEN ? AND ? GROUP BY channel_id";//每个通道的成功率
        $data['channel'] =  Db::query($sql, [$ten,$three]);

        foreach ($data['channel'] as $k => $v){

            $data['channel'][$k]['rate'] =  round($data['channel'][$k]['total_fee_paid']/$data['channel'][$k]['total_orders'],3)*100;

            //支付类型
            empty( $data['payment'][$v['payment_id']]['total_orders']) &&   $data['payment'][$v['payment_id']]['total_orders']= 0;
            empty($data['payment'][$v['payment_id']]['total_fee_all']) &&  $data['payment'][$v['payment_id']]['total_fee_all']= 0;
            empty($data['payment'][$v['payment_id']]['total_fee_paid']) &&  $data['payment'][$v['payment_id']]['total_fee_paid']= 0;
            empty($data['payment'][$v['payment_id']]['channel_id']) &&  $data['payment'][$v['payment_id']]['channel_id']= 0;
            empty($data['payment'][$v['payment_id']]['payment_id']) &&  $data['payment'][$v['payment_id']]['payment_id']= 0;
            empty($data['payment'][$v['payment_id']]['channel_group_id']) &&  $data['payment'][$v['payment_id']]['channel_group_id']= 0;


            $data['payment'][$v['payment_id']]['total_orders'] += $v['total_orders'];
            $data['payment'][$v['payment_id']]['total_fee_all'] += $v['total_fee_all'];
            $data['payment'][$v['payment_id']]['total_fee_paid'] += $v['total_fee_paid'];
            $data['payment'][$v['payment_id']]['channel_id'] += $v['channel_id'];
            $data['payment'][$v['payment_id']]['payment_id'] += $v['payment_id'];
            $data['payment'][$v['payment_id']]['channel_group_id'] += $v['channel_group_id'];

            $data['payment'][$v['payment_id']]['rate'] =  round($data['payment'][$v['payment_id']]['total_fee_paid']/$data['payment'][$v['payment_id']]['total_orders'],3)*100;


            //通道分组
            empty( $data['channel_group'][$v['channel_group_id']]['total_orders']) &&   $data['channel_group'][$v['channel_group_id']]['total_orders']= 0;
            empty($data['channel_group'][$v['channel_group_id']]['total_fee_all']) &&  $data['channel_group'][$v['channel_group_id']]['total_fee_all']= 0;
            empty($data['channel_group'][$v['channel_group_id']]['total_fee_paid']) &&  $data['channel_group'][$v['channel_group_id']]['total_fee_paid']= 0;
            empty($data['channel_group'][$v['channel_group_id']]['channel_id']) &&  $data['channel_group'][$v['channel_group_id']]['channel_id']= 0;
            empty($data['channel_group'][$v['channel_group_id']]['payment_id']) &&  $data['channel_group'][$v['channel_group_id']]['payment_id']= 0;
            empty($data['channel_group'][$v['channel_group_id']]['channel_group_id']) &&  $data['channel_group'][$v['channel_group_id']]['channel_group_id']= 0;

            $data['channel_group'][$v['channel_group_id']]['total_orders'] += $v['total_orders'];
            $data['channel_group'][$v['channel_group_id']]['total_fee_all'] += $v['total_fee_all'];
            $data['channel_group'][$v['channel_group_id']]['total_fee_paid'] += $v['total_fee_paid'];
            $data['channel_group'][$v['channel_group_id']]['channel_id'] += $v['channel_id'];
            $data['channel_group'][$v['channel_group_id']]['payment_id'] += $v['payment_id'];
            $data['channel_group'][$v['channel_group_id']]['channel_group_id'] += $v['channel_group_id'];

            $data['channel_group'][$v['channel_group_id']]['rate'] =  round($data['channel_group'][$v['channel_group_id']]['total_fee_paid']/$data['channel_group'][$v['channel_group_id']]['total_orders'],3)*100;


        }
        $data['time'] = timeToDate();

        halt($data);

        return $data;
    }





}