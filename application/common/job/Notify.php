<?php
namespace app\common\job;
use redis\StringModel;
use think\queue\Job;

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
        foreach ($data as $k =>$v ){
            $data[$k] = json_decode($v,true);
        }

        /*
         *[1] => array(4) {
    ["job"] => string(21) "app\common\job\Notify"
    ["data"] => int(666666)
    ["id"] => string(32) "urcVbpOxYJxOKnzJHwKnPzDlQIpNrZqJ"
    ["attempts"] => int(1)
  }*/

        //$data[0]['attempts'] = 66;
       // $model->lSet($key, 0, json_encode($data[0]));

        print("<info>Hello Job Started. job Data is: ".var_export($data,true)."</info> \n");
        print("<info>Hello Job is Fired at " . date('Y-m-d H:i:s') ."</info> \n");
        print("<info>Hello Job is Done!"."</info> \n");

        return false;
    }
}
