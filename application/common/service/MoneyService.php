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
     * @param $systen_no 系统订单号
     * @param string $transaction_no 上游订单号
     * @param string $amount 实际支付金额
     * @return bool
     * 包括通道 商户 代理 平台
     */
    public static function api($systen_no,$transaction_no = '',$amount = ''){

       $Order = Order::quickGet(['systen_no'=>$systen_no]);

       //不存在 或者下单失败 已支付
       if(empty($Order) || $Order['pay_status'] == 1   ) return false;

        //已支付
        if($Order['pay_status'] == 2 ) return true;

        $Channel =  Channel::alias('a')->where(['a.id'=>$Order['channel_id']])
                        ->join('channel w','a.pid = w.id')
                        ->field('w.noentry,w.id,w.account')
                        ->cache('channel_pid_'.$Order['channel_id'],3)
                        ->find();
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
        if(empty($user)) $user = $Umoney->create(['uid'=>$Order['mch_id'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

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
            if(empty($agent1)) $agent1 = $Umoney->create(['uid'=>$Order['mch_id1'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

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
            if(empty($agent2)) $agent2 = $Umoney->create(['uid'=>$Order['mch_id2'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

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

        $channel_money  = $Umoney::quickGet(['uid'=>0,'channel_id'=>$Channel['id']]); //通道金额
        if(empty($channel_money))  $channel_money = $Umoney->create(['uid'=>0,'channel_id'=>$Channel['id'],'type1'=>1,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);


        //T1 结算
        if($Channel['account'] == 1){
            $update[] = [
                'id'=>$channel_money['id'],
                'total_money'=>$channel_money['total_money'] + $Order['upstream_settle'],
                'frozen_amount_t1'=>$channel_money['frozen_amount_t1'] + $Order['upstream_settle'],
            ];
            $log[] = [
                'uid'=>0,
                'channel_id'=>$channel_money['channel_id'],
                'before_balance'=>$channel_money['total_money'],
                'balance'=>$channel_money['total_money'] + $Order['upstream_settle'],
                'change'=>$Order['upstream_settle'],
                'relate'=>$Order['systen_no'],
                'type'=>11,//T1入账
                'type1'=>1,//通道
            ];

        }else{
            $update[] = [
                'id'=>$channel_money['id'],
                'total_money'=>$channel_money['total_money'] + $Order['upstream_settle'],
                'balance'=>$channel_money['balance'] + $Order['upstream_settle'],
            ];
            $log[] = [
                'uid'=>0,
                'channel_id'=>$channel_money['channel_id'],
                'before_balance'=>$channel_money['total_money'],
                'balance'=>$channel_money['total_money'] + $Order['upstream_settle'],
                'change'=>$Order['upstream_settle'],
                'relate'=>$Order['systen_no'],
                'type'=>7,//入账
                'type1'=>1,//通道
            ];
        }

        $platform  = $Umoney::quickGet(['uid'=>0,'channel_id'=>0,'id'=>0]);
        if(empty($platform)) $platform = $Umoney->create(['id'=>0,'uid'=>0,'channel_id'=>0,'type1'=>2,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

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


        $Order_update = [
            'id'=>$Order['id'],
            'pay_status'=>2,
            'pay_time'=>date('Y-m-d H:i:s'),
        ];
        if(!empty($transaction_no)) $Order_update['transaction_no'] = $transaction_no;
        if(!empty($amount)) $Order_update['amount'] = $amount;


        //添加到处理订单列表
        if(!empty(session('admin_info.id'))){
            $OrderDispose =  model('app\common\model\OrderDispose');
            $Dispose =   $OrderDispose->quickGet(['systen_no'=>$systen_no]);
        }

        $Umoney->startTrans();
        $save = $Umoney->saveAll($update);//批量修改金额
        $save1 = model('app\common\model\UmoneyLog')->saveAll($log);//批量添加变动记录
        $save2 = model('app\common\model\Order')->save($Order_update,['id'=>$Order['id']]);

        //添加到处理订单列表
        $save3 = true;
        if(!empty(session('admin_info.id'))){
            if(empty($Dispose)){
                $save3 = $OrderDispose->create(['systen_no'=>$systen_no,'pid'=>$Order['id'],'record'=>session('admin_info.username').'-手动回调']);
            }else{
                $save3 = $OrderDispose->save([
                    'systen_no'=>$systen_no,
                    'pid'=>$Order['id'],
                    'record'=>$Dispose['record']."|".session('admin_info.username').'-手动回调'
                ],['id'=>$Dispose['id']]);
            }
        }

        if (!$save || !$save1|| !$save2|| !$save3) {
            $Umoney->rollback();
            return false;
        }
        $Umoney->commit();
        return true;
    }


    /**
     * 手动退单
     * @param $systen_no 系统订单号
     * 只修改商户，代理和平台的金额
     */
    public static function back($systen_no){
        $Order = Order::quickGet(['systen_no'=>$systen_no]);

        if(empty($Order) || $Order['pay_status'] !== 2) __jerror('该订单不存在，或者未支付');

        //通道
        $Channel =  Channel::alias('a')->where(['a.id'=>$Order['channel_id']])
            ->join('channel w','a.pid = w.id')
            ->field('w.noentry,w.id,w.account')
            ->cache('channel_pid_'.$Order['channel_id'],3)
            ->find();

        if(empty($Channel)) return false;


        $Umoney = model('app\common\model\Umoney');
        $update = [];
        $log = [];
        //处理金额
        $user  = $Umoney::quickGet(['uid'=>$Order['mch_id'],'channel_id'=>0]); //商户金额
        if(empty($user)) $user = $Umoney->create(['uid'=>$Order['mch_id'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

        $update[] = [
            'id'=>$user['id'],
            'total_money'=>$user['total_money'] - $Order['settle'],
            'balance'=>$user['balance'] - $Order['settle'],
        ];

        $log[] = [
            'uid'=>$user['uid'],
            'channel_id'=>$Channel['id'],
            'before_balance'=>$user['total_money'],
            'balance'=>$user['total_money'] - $Order['settle'],
            'change'=>$Order['settle'],
            'relate'=>$Order['systen_no'],
            'type'=>12,//手动退单
            'type1'=>0,//会员
        ];


        //上级代理
        if(!empty($Order['agent_amount']) && !empty($Order['mch_id1']) ){
            $agent1 = $Umoney::quickGet(['uid'=>$Order['mch_id1'],'channel_id'=>0]);
            if(empty($agent1)) $agent1 = $Umoney->create(['uid'=>$Order['mch_id1'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

            $update[] = [
                'id'=>$agent1['id'],
                'total_money'=>$agent1['total_money'] - $Order['agent_amount'],
                'balance'=>$agent1['balance'] - $Order['agent_amount'],
            ];

            $log[] = [
                'uid'=>$agent1['uid'],
                'channel_id'=>$Channel['id'],
                'before_balance'=>$agent1['total_money'],
                'balance'=>$agent1['total_money'] - $Order['agent_amount'],
                'change'=>$Order['agent_amount'],
                'relate'=>$Order['systen_no'],
                'type'=>12,
                'type1'=>0,//会员
            ];
        }

        //上上上级代理
        if(!empty($Order['agent_amount2']) && !empty($Order['mch_id2'])){
            $agent2  = $Umoney::quickGet(['uid'=>$Order['mch_id2'],'channel_id'=>0]);
            if(empty($agent2)) $agent2 = $Umoney->create(['uid'=>$Order['mch_id2'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

            $update[] = [
                'id'=>$agent2['id'],
                'total_money'=>$agent2['total_money'] - $Order['agent_amount2'],
                'balance'=>$agent2['balance'] - $Order['agent_amount2'],
            ];

            $log[] = [
                'uid'=>$agent2['uid'],
                'channel_id'=>$Channel['id'],
                'before_balance'=>$agent2['total_money'],
                'balance'=>$agent2['total_money'] - $Order['agent_amount2'],
                'change'=>$Order['agent_amount2'],
                'relate'=>$Order['systen_no'],
                'type'=>12,
                'type1'=>0,//会员
            ];

        }


        $channel_money  = $Umoney::quickGet(['uid'=>0,'channel_id'=>$Channel['id']]); //通道金额
        if(empty($channel_money))  $channel_money = $Umoney->create(['uid'=>0,'channel_id'=>$Channel['id'],'type1'=>1,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

        $update[] = [
            'id'=>$channel_money['id'],
            'total_money'=>$channel_money['total_money'] - $Order['upstream_settle'],
            'balance'=>$channel_money['balance'] - $Order['upstream_settle'],
        ];
        $log[] = [
            'uid'=>0,
            'channel_id'=>$channel_money['channel_id'],
            'before_balance'=>$channel_money['total_money'],
            'balance'=>$channel_money['total_money'] - $Order['upstream_settle'],
            'change'=>$Order['upstream_settle'],
            'relate'=>$Order['systen_no'],
            'type'=>12,
            'type1'=>1,//通道
        ];


        //平台金额
        $platform  = $Umoney::quickGet(['uid'=>0,'channel_id'=>0,'id'=>0]);
        if(empty($platform)) $platform = $Umoney->create(['id'=>0,'uid'=>0,'channel_id'=>0,'type1'=>2,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);

        $update[] = [
            'id'=>0,
            'total_money'=>$platform['total_money'] - $Order['Platform'],
            'balance'=>$platform['balance'] - $Order['Platform'],
        ];
        $log[] = [
            'uid'=>0,
            'channel_id'=>0,
            'before_balance'=>$platform['total_money'],
            'balance'=>$platform['total_money'] - $Order['Platform'],
            'change'=>$Order['Platform'],
            'relate'=>$Order['systen_no'],
            'type'=>12,
            'type1'=>2,//会员
        ];


        $Order_update = [
            'id'=>$Order['id'],
            'pay_status'=>0,
        ];

        //添加到处理订单列表
        $OrderDispose =  model('app\common\model\OrderDispose');
        $Dispose =   $OrderDispose->quickGet(['systen_no'=>$systen_no]);


        $Umoney->startTrans();
        $save = $Umoney->saveAll($update);//批量修改金额
        $save1 = model('app\common\model\UmoneyLog')->saveAll($log);//批量添加变动记录
        $save2 = model('app\common\model\Order')->save($Order_update,['id'=>$Order['id']]);

        //添加到处理订单列表
        if(empty($Dispose)){
            $save3 = $OrderDispose->create(['systen_no'=>$systen_no,'pid'=>$Order['id'],'record'=>session('admin_info.username').'-手动退单']);
        }else{
            $save3 = $OrderDispose->save([
                'systen_no'=>$systen_no,
                'pid'=>$Order['id'],
                'record'=>$Dispose['record']."|".session('admin_info.username').'-手动退单'
            ],['id'=>$Dispose['id']]);
        }

        if (!$save || !$save1|| !$save2|| !$save3) {
            $Umoney->rollback();
            return false;
        }
        $Umoney->commit();
        return true;

    }
}