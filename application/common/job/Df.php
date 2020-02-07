<?php
namespace app\common\job;
use app\common\model\Order;
use app\common\model\Umoney;
use think\queue\Job;

/**
 * 查询代付订单状态，一分钟一次
 * Class Df
 * @package app\common\job
 */
class Df {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        $Order = \app\common\model\Df::quickGet($data);
        dump($Order);
        // 有些消息在到达消费者时,可能已经不再需要执行了
        if(empty($data) || $data['status'] != 2 || empty($Order['channel_id'])){
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
            $job->delete();//执行一次
            return;
        }

        // 重新发布这个任务
        $job->release(60); //一分钟
        return;
    }


    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($Order,$ChannelDf,$channel_money)
    {

        $this->model = model('app\common\model\Df');

        $Payment = Payment::factory($ChannelDf['code']);
        $res  = $Payment->query($Order);

        if($res['code'] == 0) return false;

        $post['id'] = $Order['id'];

        //处理完成
        if (isset($res['data']['status']) &&  $res['data']['status'] == 3){
            $post['status'] = 3;

            $Umoney = Umoney::quickGet(['uid' =>  $Order['mch_id'], 'channel_id' =>0, 'df_id' =>0]); //会员金额
            $change['change'] = $Order['amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 1;//成功解冻入账

            $res1 = Umoney::dispose($Umoney, $change); //会员处理
            if(true !== $res1['msg'] )  return false;

            $Umoney_data = $res1['data'];
            $UmoneyLog_data = $res1['change'];


            $res2 = Umoney::dispose($channel_money, $change); //通道处理
            if (true !== $res2['msg'])  return false;

            $Umoney_data = array_merge($Umoney_data,$res2['data']);
            $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);

        }

        //失败退款
        if (isset($res['data']['status']) && $res['data']['status'] == 4){
            $post['status'] = 4;

            $Umoney = Umoney::quickGet(['uid' =>  $Order['mch_id'], 'channel_id' =>0]); //会员金额
            $change['change'] = $Order['amount'];//变动金额
            $change['relate'] = $Order['system_no'];//关联订单号
            $change['type'] = 6;//失败解冻退款

            $res1 = Umoney::dispose($Umoney, $change); //会员处理
            if (true !== $res1['msg'] ) return false;

            $Umoney_data = $res1['data'];
            $UmoneyLog_data = $res1['change'];


            $res2 = Umoney::dispose($channel_money, $change); //通道处理
            if (true !== $res2['msg']) return false;

            $Umoney_data = array_merge($Umoney_data,$res2['data']);
            $UmoneyLog_data = array_merge($UmoneyLog_data,$res2['change']);

        }

        if (isset($res['data']['status']) && ($res['data']['status'] == 4||$res['data']['status'] == 3)){
            //使用事物保存数据
            $this->model->startTrans();
            $save1 = $this->model->save($post, ['id' => $post['id']]);

            $save = model('app\common\model\Umoney')->isUpdate(true)->saveAll($Umoney_data);
            $add = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($UmoneyLog_data);

            if (!$save1 || !$save || !$add) {
                $this->model->rollback();
                return false;
            }
            $this->model->commit();
            return true;
        }


        return false;
    }
}
