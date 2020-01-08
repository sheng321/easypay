<?php
namespace app\common\job;
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
        $isJobDone = $this->doHelloJob($data);
        if ($isJobDone) {
            // 如果任务执行成功，记得删除任务
            $job->delete();
        }else{
            if ($job->attempts() > 5) {
                //通过这个方法可以检查这个任务已经重试了几次了
                $job->delete();
            }
        }
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data)
    {
        $key = "queues:notify";
        $model = (new StringModel())->instance();
        $model->select(3);
        $data =  $model->lrange($key, 0 ,60);

        $notify = array();
        foreach ($data as $k =>$v ){
            $data[$k] = json_decode($v,true);
            $notify[$k]['url'] = $data[$k]['data']['url'];
            $notify[$k]['data'] = $data[$k]['data']['url'];
        }

        /*
         *[1] => array(4) {
    ["job"] => string(21) "app\common\job\Notify"
    ["data"] => int(666666)
    ["id"] => string(32) "urcVbpOxYJxOKnzJHwKnPzDlQIpNrZqJ"
    ["attempts"] => int(1)
  }*/


    $res =  Curl::curl_multi($notify); //批量处理

        foreach ($res as $k1 => $v1){
            if(md5(strtolower($v1)) == md5('ok')){
                $data[$k1]['attempts'] = 66;
            }else{
                $data[$k1]['attempts'] = $data[$k1]['attempts'] + 1;
            }
            $model->lSet($key, 0, json_encode($data[$k1])); //更新
        }

        return true;
    }
}
