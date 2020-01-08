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
    public static function api($sn){
       $Order = Order::quickGet(['systen_no'=>$sn]);
       //不存在 或者下单失败
       if(empty($Order) || $Order['pay_status'] == 1 ) return false;

        //已支付
        if($Order['pay_status'] == 2 ) return true;


        dump($Order['channel_id']);
        $Channel =  Channel::alias('a')->where(['a.id'=>$Order['channel_id']])
                        ->join('channel w','a.pid = w.id')
                        ->field('w.noentry,w.id,w.account')
                        ->cache('channel_pid_'.$Order['channel_id'],3)
                        ->find();

        dump($Channel);

        if(empty($Channel)) return false;


        //是否禁止回调
        $noentry1 = config('set.noentry');//平台是否禁止入款
        $noentry = max($Order['noentry'],$Channel['noentry'],$noentry1);
        if(!empty($noentry))  return false;

        $Umoney = model('app\common\model\Umoney');

        $update = [];
        $log = [];
        //处理金额
        $user  = $Umoney::quickGet(['uid'=>$Order['mch_id'],'channel_id'=>0]); //商户金额

        //T1 结算
        if($Channel['account'] == 1){
            $update[] = [
                'id'=>$user['id'],
                'total_money'=>$user['total_money'] + $Order['settle'],
                'frozen_amount_t1'=>$user['frozen_amount_t1'] + $Order['settle'],
            ];

            $log[] = [
                'uid'=>$user['uid'],
                'channel_id'=>$Channel['id'],
                'before_balance'=>$user['total_money'],
                'balance'=>$user['total_money'] + $Order['settle'],
                'change'=>$Order['settle'],
                'relate'=>$Order['systen_no'],
                'type'=>11,//T1入账
                'type1'=>0,//会员
            ];


        }else{
            $update[] = [
                'id'=>$user['id'],
                'total_money'=>$user['total_money'] + $Order['settle'],
                'balance'=>$user['balance'] + $Order['settle'],
            ];

            $log[] = [
                'uid'=>$user['uid'],
                'channel_id'=>$Channel['id'],
                'before_balance'=>$user['total_money'],
                'balance'=>$user['total_money'] + $Order['settle'],
                'change'=>$Order['settle'],
                'relate'=>$Order['systen_no'],
                'type'=>7,//入账
                'type1'=>0,//会员
            ];
        }


        //上级代理
        if(!empty($Order['agent_amount']) && !empty($Order['mch_id1']) ){
            $agent1 = $Umoney::quickGet(['uid'=>$Order['mch_id1'],'channel_id'=>0]);

            //T1 结算
            if($Channel['account'] == 1){
                $update[] = [
                    'id'=>$agent1['id'],
                    'total_money'=>$agent1['total_money'] + $Order['agent_amount'],
                    'frozen_amount_t1'=>$agent1['frozen_amount_t1'] + $Order['agent_amount'],
                ];

                $log[] = [
                    'uid'=>$agent1['uid'],
                    'channel_id'=>$Channel['id'],
                    'before_balance'=>$agent1['total_money'],
                    'balance'=>$agent1['total_money'] + $Order['agent_amount'],
                    'change'=>$Order['agent_amount'],
                    'relate'=>$Order['systen_no'],
                    'type'=>11,//T1入账
                    'type1'=>0,//会员
                ];


            }else{
                $update[] = [
                    'id'=>$agent1['id'],
                    'total_money'=>$agent1['total_money'] + $Order['agent_amount'],
                    'balance'=>$agent1['balance'] + $Order['agent_amount'],
                ];

                $log[] = [
                    'uid'=>$agent1['uid'],
                    'channel_id'=>$Channel['id'],
                    'before_balance'=>$agent1['total_money'],
                    'balance'=>$agent1['total_money'] + $Order['agent_amount'],
                    'change'=>$Order['agent_amount'],
                    'relate'=>$Order['systen_no'],
                    'type'=>7,//入账
                    'type1'=>0,//会员
                ];


            }

        }
        //上上上级代理
        if(!empty($Order['agent_amount2']) && !empty($Order['mch_id2'])){
            $agent2  = $Umoney::quickGet(['uid'=>$Order['mch_id2'],'channel_id'=>0]);

            //T1 结算
            if($Channel['account'] == 1){
                $update[] = [
                    'id'=>$agent2['id'],
                    'total_money'=>$agent2['total_money'] + $Order['agent_amount2'],
                    'frozen_amount_t1'=>$agent2['frozen_amount_t1'] + $Order['agent_amount2'],
                ];
                $log[] = [
                    'uid'=>$agent2['uid'],
                    'channel_id'=>$Channel['id'],
                    'before_balance'=>$agent2['total_money'],
                    'balance'=>$agent2['total_money'] + $Order['agent_amount2'],
                    'change'=>$Order['agent_amount2'],
                    'relate'=>$Order['systen_no'],
                    'type'=>11,//T1入账
                    'type1'=>0,//会员
                ];

            }else{
                $update[] = [
                    'id'=>$agent2['id'],
                    'total_money'=>$agent2['total_money'] + $Order['agent_amount2'],
                    'balance'=>$agent2['balance'] + $Order['agent_amount2'],
                ];

                $log[] = [
                    'uid'=>$agent2['uid'],
                    'channel_id'=>$Channel['id'],
                    'before_balance'=>$agent2['total_money'],
                    'balance'=>$agent2['total_money'] + $Order['agent_amount2'],
                    'change'=>$Order['agent_amount2'],
                    'relate'=>$Order['systen_no'],
                    'type'=>7,//入账
                    'type1'=>0,//会员
                ];
            }

        }

        $channel  = $Umoney::quickGet(['uid'=>0,'channel_id'=>$Channel['id']]); //通道金额
        //T1 结算
        if($Channel['account'] == 1){
            $update[] = [
                'id'=>$channel['id'],
                'total_money'=>$channel['total_money'] + $Order['upstream_settle'],
                'frozen_amount_t1'=>$channel['frozen_amount_t1'] + $Order['upstream_settle'],
            ];
            $log[] = [
                'uid'=>0,
                'channel_id'=>$channel['id'],
                'before_balance'=>$channel['total_money'],
                'balance'=>$channel['total_money'] + $Order['upstream_settle'],
                'change'=>$Order['upstream_settle'],
                'relate'=>$Order['systen_no'],
                'type'=>11,//T1入账
                'type1'=>1,//通道
            ];

        }else{
            $update[] = [
                'id'=>$channel['id'],
                'total_money'=>$channel['total_money'] + $Order['upstream_settle'],
                'balance'=>$channel['balance'] + $Order['upstream_settle'],
            ];

            $log[] = [
                'uid'=>0,
                'channel_id'=>$channel['id'],
                'before_balance'=>$channel['total_money'],
                'balance'=>$channel['total_money'] + $Order['upstream_settle'],
                'change'=>$Order['upstream_settle'],
                'relate'=>$Order['systen_no'],
                'type'=>7,//入账
                'type1'=>1,//通道
            ];
        }

        $platform  = $Umoney::quickGet(['uid'=>0,'channel_id'=>0,'id'=>0]);
        if(empty($platform)) return false;
        //T1 结算 平台
        if($Channel['account'] == 1){
            $update[] = [
                'id'=>0,
                'total_money'=>$platform['total_money'] + $Order['Platform'],
                'frozen_amount_t1'=>$platform['frozen_amount_t1'] + $Order['Platform'],
            ];
        }else{
            $update[] = [
                'id'=>0,
                'total_money'=>$platform['total_money'] + $Order['Platform'],
                'balance'=>$platform['balance'] + $Order['Platform'],
            ];
        }

        dump($update);

        halt($channel);


        $Umoney->startTrans();

        $save = $Umoney->saveAll($update);//批量修改金额
        if (!$save) {
            $Umoney->rollback();
            $msg = '数据有误，请稍后再试！';
            return __error($msg);
        }
        $Umoney->commit();


        return true;
    }





}