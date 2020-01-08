<?php
namespace app\common\job;
use think\queue\Job;

class Api {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if(!$isJobStillNeedToBeDone){
            $job->delete();
            return;
        }
        $isJobDone = $this->doHelloJob($data);

        if ($isJobDone) {
            // 如果任务执行成功，记得删除任务
            $job->delete();
        }else{
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                $job->delete();
            }
        }
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data)
    {
        $res = \app\common\service\MoneyService::api($data);
        return $res;
    }
}
