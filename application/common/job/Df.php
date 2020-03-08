<?php
namespace app\common\job;
use app\common\model\ChannelDf;
use app\common\model\Umoney;
use app\withdrawal\service\Payment;
use think\Db;
use think\queue\Job;
use Lock\Lock;

/**
 * 查询代付订单状态，一分钟一次
 * Class Df
 * @package app\common\job
 */
class Df {


    protected $model;

    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        if ($job->attempts() > 2000) {
            $job->delete();
            return;
        }


        ini_set('max_execution_time', '120');
        $Order = \app\common\model\Df::where(['id'=>$data])->field(['extends','ip','bank','update_by','create_by','create_by','create_at','update_at','remark','record','bank','remark1'],true)->find();

        // 有些消息在到达消费者时,可能已经不再需要执行了
        if(empty($Order) || $Order['status'] != 2 || empty($Order['channel_id'])){
            $job->delete();
            return;
        }

        $ChannelDf = ChannelDf::where(['id'=>$Order['channel_id']])->field('code')->find();
        if(empty($ChannelDf) || empty($ChannelDf['code'])){
            $job->delete();
            return;
        }

        $channel_money = Umoney::where(['uid' => 0, 'df_id' => $Order['channel_id']])->value('id'); //通道金额
        if(empty($channel_money)){
            $job->delete();
            return;
        }


        try{
            $lock_val = 'Df:'.$data;
            $isJobDone =  Lock::queueLock(function ($res)use($Order,$ChannelDf){
                    $isJobDone = $this->doHelloJob($Order,$ChannelDf);
                    return $isJobDone;
                 },$lock_val,60, 60);
        }catch (\Exception $e){
            $job->failed();//出现异常
            return;
        }

        if($isJobDone === true){
            $job->delete();
            return;
        }else{
            // 原任务的基础上1分钟执行一次并增加尝试次数
            $job->failed();
            return;
        }

    }


    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($Order,$ChannelDf)
    {

        $Payment = Payment::factory($ChannelDf['code']);
        $res  = $Payment->query($Order);

        if(empty($res)  || !is_array($res) || !isset($res['code']) || !isset($res['data']['status']) || $res['code'] == 0) return false;

        $Order = \app\common\model\Df::where(['id'=>$Order['id']])->field(['extends','ip','bank','update_by','create_by','create_by','create_at','update_at','remark','record','bank','remark1'],true)->find();
        if($Order['status'] > 2 ) return true;

        $update['id'] = $Order['id'];
        $update['verson'] = $Order['verson'] + 1;//版本号

        //处理完成
        if (  $res['data']['status'] == 3){
            $update['status'] = 3;
            $change_user = 1;
            $change_channel = 1;
            $update['actual_amount'] = $Order['amount'] - $Order['fee'];//实际到账
        }
        //失败退款
        if (  $res['data']['status'] == 4){
            $update['status'] = 4;
            $change_user = 16;
            $change_channel = 6;
        }


        //3  已完成   4失败退款
        if ($res['data']['status'] == 4||$res['data']['status'] == 3){

            $Umoney = Db::table('cm_money')->field(['update_at'],true)->where(['uid' => $Order['mch_id'], 'channel_id' =>0, 'df_id' =>0])->find(); //会员金额
            $change['change'] = $Order['amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = $change_user;//成功解冻入账

            $res1 = Umoney::dispose($Umoney, $change); //会员处理
            if(true !== $res1['msg'] )  return false;

            $Umoney_data = $res1['data'];
            $UmoneyLog_data = $res1['change'];

            $channel_money = Db::table('cm_money')->field(['update_at'],true)->where(['uid' => 0, 'df_id' => $Order['channel_id']])->find(); //通道金额
            $change['change'] = $Order['channel_amount'];//通道变动金额
            $change['type'] = $change_channel;//成功解冻入账
            $res2 = Umoney::dispose($channel_money, $change); //通道处理
            if (true !== $res2['msg'])  return false;

            $Umoney_data1 = array_merge($Umoney_data,$res2['data']);
            $UmoneyLog_data1 = array_merge($UmoneyLog_data,$res2['change']);

            //使用事物保存数据
            Db::startTrans();
                try{
                    $save1 = (new \app\common\model\Df())->save($update, ['id' => $update['id']]);
                    if (!$save1)  throw new \Exception('数据更新错误');
                    $save = (new \app\common\model\Umoney())->isUpdate(true)->saveAll($Umoney_data1);
                    if (!$save)  throw new \Exception('数据更新错误');
                    $add = (new \app\common\model\UmoneyLog())->isUpdate(false)->saveAll($UmoneyLog_data1);
                    if (!$add)  throw new \Exception('数据更新错误');
                    Db::commit();
                }catch (\Exception  $exception){
                    Db::rollback();
                    return false;
                }
        }

        \app\common\model\Df::delRedis($Order['id']);
        \app\common\model\Umoney::delRedis($Umoney['id']);
        \app\common\model\Umoney::delRedis($channel_money['id']);

        //确认数据是否更新完成
        $status = \app\common\model\Df::where(['id'=>$Order['id']])->value('status');
        if($status > 2) return true;
        return false;
    }
}
