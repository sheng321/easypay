<?php

namespace app\common\service;
use app\common\model\Channel;
use app\common\model\Order;
use app\common\model\OrderDispose;
use app\common\model\Umoney;
use app\common\model\UmoneyLog;
use think\Db;


/**
 * 金额基础数据服务
 * Class MoneyService
 * @package service
 */
class MoneyService {


    /**回调成功，金额变动
     * @param $system_no 系统订单号
     * @param string $transaction_no 上游订单号
     * @param string $amount 实际支付金额
     * @return bool
     * 包括 通道 商户 代理
     * 注：支付通道有两种结算方式，T1或者T0,会员只有T0
     */
    public static function api($system_no,$transaction_no = '',$amount = ''){

        $Order = Order::where(['system_no'=>$system_no])->order(['id'=>'desc'])->find();

       //不存在 或者下单失败 已支付
       if(empty($Order) || $Order['pay_status'] == 1  || $Order['pay_status'] == 3   ) return '订单不存在或者下单失败或者订单已关闭';
        \app\common\model\Order::delRedis($Order['id']);
        //已支付
      //  if($Order['pay_status'] == 2 ) return true;

        $Channel =  Channel::alias('a')->where(['a.id'=>$Order['channel_id']])
                        ->join('channel w','a.pid = w.id')
                        ->field('w.noentry,w.id,w.account')
                        ->cache('channel_pid_'.$Order['channel_id'],3)
                        ->find();
        if(empty($Channel)) return  '通道不存在';

        //是否禁止回调
        $noentry1 = config('set.noentry');//平台是否禁止入款 （平台维护的情况）
        $noentry = max($Channel['noentry'],$noentry1); //上游通道问题的情况
        if(!empty($noentry))  return '禁止入款';

        $Umoney = model('app\common\model\Umoney');
        
        $update = [];
        $log = [];
        //处理金额
        $user  = Db::table('cm_money')->where(['uid'=>$Order['mch_id'],'channel_id'=>0])->field(['update_at'],true)->find(); //商户金额
        if(empty($user)) $user = $Umoney->create(['uid'=>$Order['mch_id'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
        \app\common\model\Umoney::delRedis($user['id']);

        $change['change'] = $Order['settle'];//变动金额
        $change['relate'] = $Order['system_no'];//关联订单号
        $change['type'] = 7;//支付入账
        $res1 =  Umoney::dispose($user,$change);

        $update = array_merge($update,$res1['data']);
        $log = array_merge($log,$res1['change']);


        //上级代理
        $agent_amount = (float)$Order['agent_amount'];
        if(!empty($agent_amount) && !empty($Order['mch_id1']) ){
            $agent1 = Db::table('cm_money')->where(['uid'=>$Order['mch_id1'],'channel_id'=>0])->field(['update_at'],true)->find();
            if(empty($agent1)) $agent1 = $Umoney->create(['uid'=>$Order['mch_id1'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
            \app\common\model\Umoney::delRedis($agent1['id']);


            $change['change'] = $Order['agent_amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 7;//支付入账
            $res2 =  Umoney::dispose($agent1,$change);

            $update = array_merge($update,$res2['data']);
            $log = array_merge($log,$res2['change']);

        }
        //上上级代理
        $agent_amount2 = (float)$Order['agent_amount2'];
        halt($agent_amount2);
        if(!empty($agent_amount2) && !empty($Order['mch_id2'])){
            $agent2  = Db::table('cm_money')->where(['uid'=>$Order['mch_id2'],'channel_id'=>0])->field(['update_at'],true)->find();
            if(empty($agent2)) $agent2 = $Umoney->create(['uid'=>$Order['mch_id2'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
            \app\common\model\Umoney::delRedis($agent2['id']);

            $change['change'] = $Order['agent_amount2'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 7;//支付入账
            $res3 =  Umoney::dispose($agent2,$change);

            $update = array_merge($update,$res3['data']);
            $log = array_merge($log,$res3['change']);

        }


        $channel_money  = Db::table('cm_money')->where(['uid'=>0,'channel_id'=>$Channel['id']])->field(['update_at'],true)->find(); //通道金额
        if(empty($channel_money))  $channel_money = $Umoney->create(['uid'=>0,'channel_id'=>$Channel['id'],'type1'=>1,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
        \app\common\model\Umoney::delRedis($channel_money['id']);

        //T1 结算
        if($Channel['account'] == 1){
            $change['change'] = $Order['upstream_settle'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 11;//T1入账
            $res4 =  Umoney::dispose($channel_money,$change);

            $update = array_merge($update,$res4['data']);
            $log = array_merge($log,$res4['change']);

            $t1['money'] = $Order['upstream_settle'];//变动金额
            $t1['id'] = $channel_money['id'];//金额账户ID
            $t1['system_no'] = $system_no;//关联订单号

        }else{

            $change['change'] = $Order['upstream_settle'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 7;//支付入账
            $res4 =  Umoney::dispose($channel_money,$change);

            $update = array_merge($update,$res4['data']);
            $log = array_merge($log,$res4['change']);
        }

        $Order_update = [
            'id'=>$Order['id'],
            'pay_status'=>2,
            'pay_time'=>date('Y-m-d H:i:s'),
        ];
        if(!empty($transaction_no)) $Order_update['transaction_no'] = $transaction_no;
        if(!empty($amount)) $Order_update['actual_amount'] = $amount;

        //添加到处理订单列表
        if (app('request')->module() == 'admin') {
            $OrderDispose =  new OrderDispose();
            $Dispose =   $OrderDispose->quickGet(['system_no'=>$system_no]);
        }

        dump($log);
        halt($update);
        /***事务处理***/
        Db::startTrans();
        try{
            $save = (new Umoney())->isUpdate(true)->saveAll($update);//批量修改金额
            if (!$save ) throw new \Exception('订单入账事务更新数据失败');

            $save1 = (new UmoneyLog())->isUpdate(false)->saveAll($log);//批量添加变动记录
            if (!$save1 ) throw new \Exception('订单入账事务更新数据失败');

            $save2 = (new Order())->isUpdate(true)->save($Order_update,['id'=>$Order['id']]);
            if (!$save2 ) throw new \Exception('订单入账事务更新数据失败');

            //添加到处理订单列表  在后台处理订单列表显示
            $save3 = true;
            if (app('request')->module() == 'admin') {

                if(empty($Dispose)){
                    $save3 = $OrderDispose->create(['system_no'=>$system_no,'pid'=>$Order['id'],'record'=>session('admin_info.username').'-手动补单']);
                }else{
                    $save3 = $OrderDispose->isUpdate(true)->save([
                        'id'=>$Dispose['id'],
                        'system_no'=>$system_no,
                        'pid'=>$Order['id'],
                        'record'=>$Dispose['record']."|".session('admin_info.username').'-手动补单'
                    ],['id'=>$Dispose['id']]);
                }
            }
            if (!$save3) throw new \Exception('订单入账事务更新数据失败');

            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            return $exception->getMessage();
        }

        //添加到异步T1处理 --支付通道
        //T1 结算
        if($Channel['account'] == 1) \think\Queue::later(3600*24,'app\\common\\job\\T1', $t1, 't1');//24小时
       // if($Channel['account'] == 1) \think\Queue::push('app\\common\\job\\T1', $t1, 't1');
        return true;
    }


    /**
     * 手动退单
     * @param $system_no 系统订单号
     * 只修改商户，代理，通道的金额
     */
    public static function back($system_no){

        $Order = Order::where(['system_no'=>$system_no])->order(['id'=>'desc'])->find();

        if(empty($Order) || $Order['pay_status'] !== 2) __jerror('该订单不存在，或者未支付');
        \app\common\model\Order::delRedis($Order['id']);
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

        $user  = Db::table('cm_money')->where(['uid'=>$Order['mch_id'],'channel_id'=>0])->field(['update_at'],true)->find(); //商户金额
        if(empty($user)) $user = $Umoney->create(['uid'=>$Order['mch_id'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
        \app\common\model\Umoney::delRedis($user['id']);


        $change['change'] = $Order['settle'];//变动金额
        $change['relate'] = $Order['system_no'];//关联订单号
        $change['type'] = 12;//手动退单
        $res4 =  Umoney::dispose($user,$change);

        $update = array_merge($update,$res4['data']);
        $log = array_merge($log,$res4['change']);

        //上级代理
        if(!empty($Order['agent_amount']) && !empty($Order['mch_id1']) ){
            $agent1 = Db::table('cm_money')->where(['uid'=>$Order['mch_id1'],'channel_id'=>0])->field(['update_at'],true)->find();
            if(empty($agent1)) $agent1 = $Umoney->create(['uid'=>$Order['mch_id1'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
            \app\common\model\Umoney::delRedis($agent1['id']);

            $change['change'] = $Order['agent_amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 12;//手动退单
            $res2 =  Umoney::dispose($agent1,$change);

            $update = array_merge($update,$res2['data']);
            $log = array_merge($log,$res2['change']);
        }

        //上上级代理
        if(!empty($Order['agent_amount2']) && !empty($Order['mch_id2'])){
            $agent2  = Db::table('cm_money')->where(['uid'=>$Order['mch_id2'],'channel_id'=>0])->field(['update_at'],true)->find();
            if(empty($agent2)) $agent2 = $Umoney->create(['uid'=>$Order['mch_id2'],'channel_id'=>0,'type1'=>0,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
            \app\common\model\Umoney::delRedis($agent2['id']);

            $change['change'] = $Order['agent_amount2'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 12;//手动退单
            $res3 =  Umoney::dispose($agent2,$change);

            $update = array_merge($update,$res3['data']);
            $log = array_merge($log,$res3['change']);
        }


        $channel_money  = Db::table('cm_money')->where(['uid'=>0,'channel_id'=>$Channel['id']])->field(['update_at'],true)->find(); //通道金额
        if(empty($channel_money))  $channel_money = $Umoney->create(['uid'=>0,'channel_id'=>$Channel['id'],'type1'=>1,'total_money'=>0,'frozen_amount_t1'=>0,'balance'=>0]);
        \app\common\model\Umoney::delRedis($channel_money['id']);

        $change['change'] = $Order['upstream_settle'];//变动金额
        $change['relate'] = $Order['system_no'];//关联订单号
        $change['type'] = 12;//手动退单
        $res4 =  Umoney::dispose($channel_money,$change);

        $update = array_merge($update,$res4['data']);
        $log = array_merge($log,$res4['change']);

        $Order_update = [
            'id'=>$Order['id'],
            'pay_status'=>0,
            'repair'=>2,//退单
        ];


        //添加到处理订单列表
        $OrderDispose =  new OrderDispose();
        $Dispose =   $OrderDispose->where(['system_no'=>$system_no])->value('id');

        Db::startTrans();
        try{

            $save = (new Umoney())->isUpdate(true)->saveAll($update);//批量修改金额
            if (!$save ) throw new \Exception('订单退单,更新数据失败');
            $save1 = (new UmoneyLog())->isUpdate(false)->saveAll($log);//批量添加变动记录
            if (!$save1 ) throw new \Exception('订单退单,更新数据失败');
            $save2 = (new Order())->isUpdate(true)->save($Order_update,['id'=>$Order['id']]);
            if (!$save2 ) throw new \Exception('订单退单,更新数据失败');

            //添加到处理订单列表
            if(empty($Dispose)){
                $save3 = $OrderDispose->create(['system_no'=>$system_no,'pid'=>$Order['id'],'record'=>session('admin_info.username').'-手动退单']);
            }else{
                $save3 = $OrderDispose->isUpdate(true)->save([
                    'id'=>$Dispose['id'],
                    'system_no'=>$system_no,
                    'pid'=>$Order['id'],
                    'record'=>$Dispose['record']."|".session('admin_info.username').'-手动退单'
                ],['id'=>$Dispose['id']]);
            }
            if (!$save3) throw new \Exception('订单退单,更新数据失败');

            Db::commit();
            return true;
        }catch (\Exception $exception){
            Db::rollback();
            return false;
        }
    }
}