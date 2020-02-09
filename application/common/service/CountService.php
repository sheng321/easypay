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

        //总订单数 total_orders //总订单金额 total_fee_all //已支付金额 total_fee_paid //已支付订单数 total_paid
        $sql = "select count(1) as total_orders,COALESCE(sum(amount),0) as total_fee_all,COALESCE(sum(if(pay_status=2,amount,0)),0) as total_fee_paid,COALESCE(sum(if(pay_status=2,1,0)),0) as total_paid,channel_id from cm_order where  create_at BETWEEN ? AND ? GROUP BY channel_id";//每个通道的成功率
        $select =  Db::query($sql, [$ten,$three]);

        halt($select);

        return $data;
    }





}