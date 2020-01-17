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
        $res = \app\common\service\MoneyService::api($data['order']['systen_no'],$data['config']['transaction_no'],$data['config']['amount']);

        if($res === true){

            logs(22222222,$type = 'order/notify/'.$data['config']['code']);

            //获取回调数据
            $notify = Order::notify($data['order']['systen_no']);

            $ok = \tool\Curl::post($notify['url'],$notify['data']);
            if(strtolower($ok) === 'ok'){
                (new Order)->save(['id'=>$notify['order']['id'],'notice'=>2,'remark'=>$ok],['id'=>$notify['order']['id']]);
            }else{
                (new Order)->save(['id'=>$notify['order']['id'],'notice'=>3,'remark'=>htmlspecialchars(\think\helper\Str::substr($ok,0,60))],['id'=>$notify['order']['id']]);
                \think\Queue::later(1,'app\\common\\job\\Notify', $notify, 'notify');
            }
            return true;
        }

        $data['res'] = $res;
        //错误添加到订单回调日志
        logs($data,$type = 'order/notify/'.$data['config']['code']);

        return false;
    }
}
