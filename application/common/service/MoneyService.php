<?php

namespace app\common\service;
use app\common\model\Channel;
use app\common\model\Order;


/**
 * 金额基础数据服务
 * Class MoneyService
 * @package service
 */
class MoneyService {

    /**回调成功，金额变动
     * @param $sn 订单号
     * @return bool
     */
    public function api($sn){
       $Order = Order::quickGet(['systen_no'=>$sn]);
       //不存在 或者下单失败
       if(empty($Order) || $Order['pay_status'] == 1 ) return false;

        //已支付
        if($Order['pay_status'] == 2 ) return true;


        $Channel = Channel::quickGet($Order['pid']);

        //是否禁止回调
        $noentry1 = config('set.noentry');//平台是否禁止入款
        $noentry = max($Order['noentry'],$Channel['noentry'],$noentry1);
        if(!empty($noentry))  return false;


        $Umoney = model('app\common\model\Umoney');

        $update = [];
        //处理金额
        $user  = $Umoney::quickGet(['uid'=>$Order['mch_id'],'channel_id'=>0]); //商户金额
        $update[] = [
             'id'=>$user['id'],
             'total_money'=>$user['total_money'] + $Order['settle'],
             'balance'=>$user['balance'] + $Order['settle'],
            ];


        //上级代理
        if(!empty($Order['agent_amount']) && !empty($Order['mch_id1']) ){
            $agent1 = $Umoney::quickGet(['uid'=>$Order['mch_id1'],'channel_id'=>0]);
            $update[] = [
                'id'=>$agent1['id'],
                'total_money'=>$agent1['total_money'] + $Order['agent_amount'],
                'balance'=>$agent1['balance'] + $Order['agent_amount'],
            ];
        }
        //上上上级代理
        if(!empty($Order['agent_amount2']) && !empty($Order['mch_id2'])){
            $agent2  = $Umoney::quickGet(['uid'=>$Order['mch_id2'],'channel_id'=>0]);
            $update[] = [
                'id'=>$agent2['id'],
                'total_money'=>$agent2['total_money'] + $Order['agent_amount2'],
                'balance'=>$agent2['balance'] + $Order['agent_amount2'],
            ];
        }

        $channel  = $Umoney::quickGet(['uid'=>0,'channel_id'=>$Channel['id']]); //通道金额
        $update[] = [
            'id'=>$channel['id'],
            'total_money'=>$channel['total_money'] + $Order['upstream_settle'],
            'balance'=>$channel['balance'] + $Order['upstream_settle'],
        ];

        $Umoney->startTrans();

        $save = $Umoney->save($update,['id'=>$update['id']]);
        if (!$save) {
            $Umoney->rollback();
            $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $Umoney->commit();


        return true;
    }





}