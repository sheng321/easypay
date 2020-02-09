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

        $sql = "select * from cm_order where  create_at BETWEEN ? AND ?";
        $select =  Db::query($sql, [$ten,$three]);

        halt($select);

        return $data;
    }





}