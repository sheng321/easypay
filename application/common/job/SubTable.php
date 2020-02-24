<?php
namespace app\common\job;
use think\queue\Job;
//分表
class SubTable {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        if ($job->attempts() > 4) {
            $job->delete();//执行一次
            return;
        }

        $isJobDone = $this->doHelloJob($data);
        if($isJobDone == true){
            $job->delete();//执行一次
            return;
        }
    }


    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data)
    {
        $res = \app\common\service\SubTable::syn_table();
        return $res;
    }
}
