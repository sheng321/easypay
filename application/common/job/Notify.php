<?php
namespace app\common\job;
use app\common\model\Order;
use redis\StringModel;
use think\queue\Job;
use tool\Curl;

class Notify {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        //错误添加到订单回调日志
        logs($data.'|'.$job->attempts(),$type = 'order/notify/'.$data['config']['code']);

        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if($job->attempts() > 6|| $isJobStillNeedToBeDone === true ){
           // $job->delete();
            return;
        }





        $isJobDone = $this->doHelloJob($data);
        if ($isJobDone === true) {
            // 如果任务执行成功，记得删除任务
          //  $job->delete();
        }else{
            if ($job->attempts() > 6) {
                //通过这个方法可以检查这个任务已经重试了几次了
              //  $job->delete();
            }

            // 重发，延迟 60 秒执行
            $job->release(60);
        }
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
       $Order =  Order::quickGet($data['order']['id']);
        if($Order['notice'] == 2) return false;//已回调

        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data)
    {
/*        ['data'=>$data,
            'url'=>$Order['notify_url'],
            'order'=>[
                'id'=>$Order['id'],
                'notice'=>$Order['notice'],
                'pay_time'=>strtotime($Order['pay_time']),
            ]*/

        //最少间隔30秒
        if((time() - $data['order']['pay_time']) < 30) return false;

        $ok = \tool\Curl::post($data['url'],$data['data']);
        if(strtolower($ok) === 'ok'){
            (new Order)->save(['id'=>$data['order']['id'],'notice'=>2,'remark'=>$ok],['id'=>$data['order']['id']]);
            return true;
        }
        return false;
    }
}
