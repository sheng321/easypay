<?php
namespace app\common\job;
use app\common\model\Umoney;
use app\withdrawal\service\Payment;
use think\queue\Job;

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
        $Order = \app\common\model\Df::quickGet($data);
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

        $isJobDone = $this->doHelloJob($Order,$ChannelDf,$channel_money);
        if($isJobDone === true){
            $job->delete();
            return;
        }else{
            if ($job->attempts() > 200) {
                $job->delete();
                return;
            }
             // 重新发布这个任务
            $job->release(60); //一分钟
            return;
        }


    }


    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($Order,$ChannelDf,$channel_money)
    {

        $this->model = model('app\common\model\Df');

        $Payment = Payment::factory($ChannelDf['code']);
        $res  = $Payment->query($Order);

        if(empty($res)  || !is_array($res) || !isset($res['code']) || !isset($res['data']['status']) || $res['code'] == 0) return false;

        $Order = \app\common\model\Df::quickGet($Order['id']);
        $update['id'] = $Order['id'];
        $update['verson'] = $Order['verson'] + 1;//版本号

        //处理完成
        if (isset($res['data']['status']) &&  $res['data']['status'] == 3){
            $update['status'] = 3;

            $Umoney = Umoney::quickGet(['uid' => $Order['mch_id'], 'channel_id' =>0, 'df_id' =>0]); //会员金额
            $change['change'] = $Order['amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 1;//成功解冻入账

            $res1 = Umoney::dispose($Umoney, $change); //会员处理
            if(true !== $res1['msg'] )  return false;

            $Umoney_data = $res1['data'];
            $UmoneyLog_data = $res1['change'];

            $change['change'] = $Order['channel_amount'];//通道变动金额
            $res2 = Umoney::dispose($channel_money, $change); //通道处理
            if (true !== $res2['msg'])  return false;

            $Umoney_data = array_merge($Umoney_data,$res2['data']);
            $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);

            $update['actual_amount'] = $Order['amount'] - $Order['fee'];//实际到账

        }

        //失败退款
        if (isset($res['data']['status']) && $res['data']['status'] == 4){
            $update['status'] = 4;

            $Umoney = Umoney::quickGet(['uid' =>  $Order['mch_id'], 'channel_id' =>0]); //会员金额
            $change['change'] = $Order['amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 16;//会员代付失败解冻退款

            $res1 = Umoney::dispose($Umoney, $change); //会员处理
            if (true !== $res1['msg'] ) return false;

            $Umoney_data = $res1['data'];
            $UmoneyLog_data = $res1['change'];

            $change['change'] = $Order['channel_amount'];//通道变动金额
            $change['type'] = 6;//通道失败解冻退款
            $res2 = Umoney::dispose($channel_money, $change); //通道处理
            if (true !== $res2['msg']) return false;

            $Umoney_data = array_merge($Umoney_data,$res2['data']);
            $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);

        }

        //3  已完成   4失败退款
        if (isset($res['data']['status']) && ($res['data']['status'] == 4||$res['data']['status'] == 3)){
            //使用事物保存数据
            $this->model->startTrans();
            try{
                $save1 = $this->model->save($update, ['id' => $update['id']]);
                $save = model('app\common\model\Umoney')->isUpdate(true)->saveAll($Umoney_data);
                $add = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($UmoneyLog_data);

                if (!$save1 || !$save || !$add) {
                    throw new Exception('数据更新错误');
                }
                $this->model->commit();
            }catch (\Exception $exception){
                $this->model->rollback();
                return false;
            }
            return true;
        }

        return false;
    }
}
