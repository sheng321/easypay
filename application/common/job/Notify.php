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
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if($job->attempts() > 6|| !$isJobStillNeedToBeDone ){
            $job->delete();
            return;
        }

        $isJobDone = $this->doHelloJob($data);
        if ($isJobDone) {
            // 如果任务执行成功，记得删除任务
            $job->delete();
        }else{
            if ($job->attempts() > 6) {
                //通过这个方法可以检查这个任务已经重试了几次了
                $job->delete();
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
        $key = "queues:notify";
        $model = (new StringModel())->instance();
        $model->select(3);
        $data =  $model->lrange($key, 0 ,100);

        foreach ($data as $k =>$v ){
            $data[$k] = json_decode($v,true);
            if($data[$k]['attempts'] > 6){
                unset($data[$k]);
                continue;
            }
             //最少间隔30秒
            if((time() - $data[$k]['order']['pay_time']) < 30){
                unset($data[$k]);
                continue;
            }
        }

        if(empty($data)) return true;

        /*
         *[1] => array(4) {
    ["job"] => string(21) "app\common\job\Notify"
    ["data"] => int(666666)
    ["id"] => string(32) "urcVbpOxYJxOKnzJHwKnPzDlQIpNrZqJ"
    ["attempts"] => int(1)
  }*/

    $res =  Curl::curl_multi($data); //批量处理
    foreach ($res as $k1 => $v1){
        if(md5(strtolower($v1)) == md5('ok')){
            (new Order)->save(['id'=>$data['order']['id'],'notice'=>2],['id'=>$data['order']['id']]);
            $data[$k1]['attempts'] = 66;
        }else{
            $data[$k1]['attempts'] = $data[$k1]['attempts'] + 1;
            $data[$k]['order']['pay_time'] = time();
        }
        $model->lSet($key, 0, json_encode($data[$k1])); //更新队列
    }

    return true;
    }
}
