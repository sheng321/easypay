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
        //错误添加到订单回调日志
        logs($data.'|'.$job->attempts(),$type = 'order/notify/'.$data['order']['code']);

        $Order =  Order::quickGet($data['order']['id']);
        if(empty($Order)){
            $job->delete();
            return;
        }
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($Order);
        if($job->attempts() > 6|| $isJobStillNeedToBeDone === false ){
            $job->delete();
            return;
        }

        $isJobDone = $this->doHelloJob($data,$Order);
        if ($isJobDone === true) {
            // 如果任务执行成功，记得删除任务
            $job->delete();
            return;
        }else{
            if ($job->attempts() > 6) {
                //通过这个方法可以检查这个任务已经重试了几次了
                $job->delete();
                return;
            }
            // 重发，延迟 60 秒执行
            $job->release(1);
            return;
        }
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        if($data['notice'] == 2) return false;//已回调
        if($data['pay_status'] != 2) return false;//未支付
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data,$Order)
    {
/*        ['data'=>$data,
            'url'=>$Order['notify_url'],
            'order'=>[
                'id'=>$Order['id'],
                'notice'=>$Order['notice'],
                'pay_time'=>strtotime($Order['pay_time']),
                 'code'=>--,
            ]*/

        $ok = \tool\Curl::post($data['url'],$data['data']);
        if(strtolower($ok) === 'ok'){
            (new Order)->save(['id'=>$data['order']['id'],'notice'=>2,'remark'=>$ok],['id'=>$data['order']['id']]);
            return true;
        }

        if($Order['notice'] != 3){
            (new Order)->save(['id'=>$data['order']['id'],'notice'=>3,'remark'=>htmlspecialchars(\think\helper\Str::substr($ok,0,60))],['id'=>$data['order']['id']]);
        }
        return false;
    }
}
