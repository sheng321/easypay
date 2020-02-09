<?php
namespace app\common\service;
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

            $ChannelGroup =  ChannelGroup::idArr();//通道分组
            $Channel =  Channel::idRate();//通道
            $PayProduct =  PayProduct::idArr();//支付产品

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
                empty($data['payment'][$v['payment_id']]['channel_id']) &&  $data['payment'][$v['payment_id']]['channel_id']= 0;
                empty($data['payment'][$v['payment_id']]['payment_id']) &&  $data['payment'][$v['payment_id']]['payment_id']= 0;
                empty($data['payment'][$v['payment_id']]['channel_group_id']) &&  $data['payment'][$v['payment_id']]['channel_group_id']= 0;


                $data['payment'][$v['payment_id']]['total_orders'] += $v['total_orders'];
                $data['payment'][$v['payment_id']]['total_fee_all'] += $v['total_fee_all'];
                $data['payment'][$v['payment_id']]['total_fee_paid'] += $v['total_fee_paid'];
                $data['payment'][$v['payment_id']]['total_paid'] += $v['total_paid'];
                $data['payment'][$v['payment_id']]['channel_id'] += $v['channel_id'];
                $data['payment'][$v['payment_id']]['payment_id'] += $v['payment_id'];
                $data['payment'][$v['payment_id']]['channel_group_id'] += $v['channel_group_id'];

                $data['payment'][$v['payment_id']]['title'] = $data['channel'][$k]['product_name'];
                $data['payment'][$v['payment_id']]['rate'] =  round($data['payment'][$v['payment_id']]['total_paid']/$data['payment'][$v['payment_id']]['total_orders'],3)*100;


                //通道分组
                empty( $data['channel_group'][$v['channel_group_id']]['total_orders']) &&   $data['channel_group'][$v['channel_group_id']]['total_orders']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['total_fee_all']) &&  $data['channel_group'][$v['channel_group_id']]['total_fee_all']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['total_fee_paid']) &&  $data['channel_group'][$v['channel_group_id']]['total_fee_paid']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['total_paid']) &&  $data['channel_group'][$v['channel_group_id']]['total_paid']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['channel_id']) &&  $data['channel_group'][$v['channel_group_id']]['channel_id']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['payment_id']) &&  $data['channel_group'][$v['channel_group_id']]['payment_id']= 0;
                empty($data['channel_group'][$v['channel_group_id']]['channel_group_id']) &&  $data['channel_group'][$v['channel_group_id']]['channel_group_id']= 0;

                $data['channel_group'][$v['channel_group_id']]['total_orders'] += $v['total_orders'];
                $data['channel_group'][$v['channel_group_id']]['total_fee_all'] += $v['total_fee_all'];
                $data['channel_group'][$v['channel_group_id']]['total_fee_paid'] += $v['total_fee_paid'];
                $data['channel_group'][$v['channel_group_id']]['total_paid'] += $v['total_paid'];
                $data['channel_group'][$v['channel_group_id']]['channel_id'] += $v['channel_id'];
                $data['channel_group'][$v['channel_group_id']]['payment_id'] += $v['payment_id'];
                $data['channel_group'][$v['channel_group_id']]['channel_group_id'] += $v['channel_group_id'];

                $data['channel_group'][$v['channel_group_id']]['title'] =  $data['channel'][$k]['channelgroup_name'];
                $data['channel_group'][$v['channel_group_id']]['rate'] =  round($data['channel_group'][$v['channel_group_id']]['total_paid']/$data['channel_group'][$v['channel_group_id']]['total_orders'],3)*100;

            }
            $data['time'] = timeToDate();

            return  $data;
        },180);

        return \think\facade\Cache::get('success_rate');

    }

    //商户对账
    public static function mem_account(){
        $data = [];
        $one = timeToDate(0,0,0,-1);//昨天
        $two = timeToDate(59,59,59,-1);//昨天


        dump($one);
        dump($two);

        halt($data);
    }






}