<?php
namespace app\common\job;
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
        ini_set('max_execution_time', '120');
        $Order = \app\common\model\Df::where(['id'=>$data])->find();

        // 有些消息在到达消费者时,可能已经不再需要执行了
        if(empty($Order) || $Order['status'] != 2 || empty($Order['channel_id'])){
            $job->delete();
            return;
        }
        $ChannelDf = \app\common\model\ChannelDf::quickGet($Order['channel_id']);
        if(empty($ChannelDf) || empty($ChannelDf['code'])){
            $job->delete();
            return;
        }

        $channel_money = Umoney::quickGet(['uid' => 0, 'df_id' => $Order['channel_id']]); //通道金额
        if(empty($channel_money)){
            $job->delete();
            return;
        }

        try{
            $lock_val = 'Df:'.$data;
            $isJobDone =  Lock::lock(function ($res)use($Order,$ChannelDf){
                    $isJobDone = $this->doHelloJob($Order,$ChannelDf);
                    return $isJobDone;
                 },$lock_val);
        }catch (\Exception $e){
            $job->delete();//出现异常
            return;
        }
        halt($isJobDone);


        if($isJobDone === true){
            $job->delete();
            return;
        }else{
            if ($job->attempts() > 1) {
                // 第2种处理方式：原任务的基础上1分钟执行一次并增加尝试次数
                $job->failed();
                return;
            }
        }

    }

    /*
     * array(25) {
  ["id"] => int(334)
  ["system_no"] => string(20) "d2003061536446162645"
  ["mch_id"] => string(8) "20100008"
  ["lock_id"] => int(1)
  ["record"] => string(67) "admin选择下发代付通道:备用金|admin更新状态:处理中"
  ["remark"] => string(0) ""
  ["status"] => int(2)
  ["create_at"] => string(19) "2020-03-06 15:36:44"
  ["update_at"] => string(19) "2020-03-06 15:56:10"
  ["amount"] => string(6) "10.000"
  ["fee"] => string(5) "5.000"
  ["actual_amount"] => string(5) "0.000"
  ["create_by"] => int(55)
  ["update_by"] => int(1)
  ["bank"] => string(140) "{"account_name":"\u6d4b\u8bd599","bank_name":"\u4e2d\u56fd\u5de5\u5546\u94f6\u884c","card_number":"123456","branch_name":"","bank_id":"102"}"
  ["channel_id"] => int(63)
  ["channel_fee"] => string(5) "1.000"
  ["verson"] => int(2)
  ["transaction_no"] => string(0) ""
  ["remark1"] => string(0) ""
  ["out_trade_no"] => string(12) "后台申请"
  ["ip"] => string(12) "113.61.61.77"
  ["extends"] => NULL
  ["card_number"] => string(0) ""
  ["channel_amount"] => string(5) "6.000"
}*/



    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($Order,$ChannelDf)
    {
        $this->model = model('app\common\model\Df');

        $Payment = Payment::factory($ChannelDf['code'].'1');
        $res  = $Payment->query($Order);

        if(empty($res)  || !is_array($res) || !isset($res['code']) || !isset($res['data']['status']) || $res['code'] == 0) return false;


        $Order = \app\common\model\Df::where(['id'=>$Order['id']])->find();
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

            $Umoney = Db::table('cm_money')->where(['uid' => $Order['mch_id'], 'channel_id' =>0, 'df_id' =>0])->find(); //会员金额
            $change['change'] = $Order['amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = $change_user;//成功解冻入账

            $res1 = Umoney::dispose($Umoney, $change); //会员处理
            if(true !== $res1['msg'] )  return false;

            $Umoney_data = $res1['data'];
            $UmoneyLog_data = $res1['change'];

            $channel_money = Db::table('cm_money')->where(['uid' => 0, 'df_id' => $Order['channel_id']])->find(); //通道金额
            $change['change'] = $Order['channel_amount'];//通道变动金额
            $change['type'] = $change_channel;//成功解冻入账
            $res2 = Umoney::dispose($channel_money, $change); //通道处理
            if (true !== $res2['msg'])  return false;

            $Umoney_data1 = array_merge($Umoney_data,$res2['data']);
            $UmoneyLog_data1 = array_merge($UmoneyLog_data,$res2['change']);

            //使用事物保存数据
            Db::startTrans();
                try{
                    $save1 = (new \app\common\model\Df)->save($update, ['id' => $update['id']]);
                    if (!$save1)  throw new \Exception('数据更新错误');
                    $save = (new \app\common\model\Umoney)->isUpdate(true)->saveAll($Umoney_data1);
                    if (!$save)  throw new \Exception('数据更新错误');
                    $add = (new \app\common\model\UmoneyLog)->isUpdate(false)->saveAll($UmoneyLog_data1);
                    if (!$add)  throw new \Exception('数据更新错误');
                    Db::commit();
                }catch (\Exception  $exception){
                    Db::rollback();
                    return false;
                }
        }

        //确认数据是否更新完成
        $status = \app\common\model\Df::where(['id'=>$Order['id']])->value('status');
        if($status > 2) return true;
        return false;
    }
}
