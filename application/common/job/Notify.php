<?php
namespace app\common\job;
use app\common\model\Order;
use think\queue\Job;

class Notify {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        $Order =  Order::quickGet($data['order']['id']);

        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($Order);
        if($isJobStillNeedToBeDone === false ){
            $job->delete();
            return;
        }

        $isJobDone = $this->doHelloJob($data,$Order);
        $job->delete();//执行一次
        return;

    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        if(empty($data)) return false;//订单不存在
        if($data['notice'] == 2) return false;//已回调
        if($data['pay_status'] != 2) return false;//不是已支付状态
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data,$Order)
    {
/*
 *  array (
  'data' =>
  array (
    'amount' => '300.000',
    'datetime' => '2020-01-17 10:16:23',
    'memberid' => 20100002,
    'orderid' => 'c2001171015501296455',
    'returncode' => '00',
    'transaction_id' => 's2001171015526909744',
    'sign' => '3E2E23AFB267132B5040BDAAA2819ED1',
    'attach' => '原样返回字段',
  ),
  'url' => 'http://120.24.166.163:66/Run/notify.php',
  'order' =>
  array (
    'id' => 239,
    'notice' => 1,
    'pay_time' => 1579227383,
    'code' => 'Xyf',
  ),
)*/
        $ok = \tool\Curl::post($data['url'],$data['data']);
        if(strtolower($ok) === 'ok'){
            (new Order)->save(['id'=>$data['order']['id'],'notice'=>2,'remark'=>$ok],['id'=>$data['order']['id']]);
            return true;
        }
        if($Order['notice'] != 3){
            (new Order)->save(['id'=>$data['order']['id'],'notice'=>3,'remark'=>htmlspecialchars(\think\helper\Str::substr($ok,0,100))],['id'=>$data['order']['id']]);
        }
        return false;
    }
}
