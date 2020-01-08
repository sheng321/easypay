<?php
namespace app\common\job;
use app\common\model\Order;
use think\queue\Job;

class Api {
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
            if ($job->attempts() > 3) {
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
        $res = \app\common\service\MoneyService::api($data);

        if($res === true){

            $data = Order::notify($data);

            $ok = \tool\Curl::post($data['url'],$data['data']);
            if(md5(strtolower($ok)) == md5('ok')){
                (new Order)->save(['id'=>$data['order']['id'],'notice'=>2],['id'=>$data['order']['id']]);
            }else{
                \think\Queue::later('60','app\\common\\job\\Notify', $data, 'notify');
            }

        }

        return $res;
    }
}
