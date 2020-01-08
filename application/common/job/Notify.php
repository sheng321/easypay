<?php
namespace app\common\job;
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
        print("<info>Hello Job Started. job Data is: ".var_export($data,true)."</info> \n");
        print("<info>Hello Job is Fired at " . date('Y-m-d H:i:s') ."</info> \n");
        print("<info>Hello Job is Done!"."</info> \n");

        return false;
    }
}
