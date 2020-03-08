<?php
namespace app\common\job;
use app\common\model\Order;
use Lock\Lock;
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
            return;
        }else{
            if ($job->attempts() > 3) {
                //通过这个方法可以检查这个任务已经重试了几次了
                $job->delete();
                return;
            }
        }
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data)
    {

        //多线程添加锁
        try{
            $lock_val = 'Api:'.$data['order']['system_no'];
            $res =  Lock::queueLock(function ($redis)use($data){
                $result = \app\common\service\MoneyService::api($data['order']['system_no'],$data['config']['transaction_no'],$data['config']['amount']);
                return $result;
            },$lock_val,60,60);
        }catch (\Exception $e){
            return  false;//出现异常
        }

        if($res === true){
            //获取回调数据
            $notify = Order::notify($data['order']['system_no'],$data['config']['code']);
            \think\Queue::push('app\\common\\job\\Notify', $notify, 'notify');//立即
            \think\Queue::later(60,'app\\common\\job\\Notify', $notify, 'notify');//一分钟
            \think\Queue::later(240,'app\\common\\job\\Notify', $notify, 'notify');//二分钟
            \think\Queue::later(540,'app\\common\\job\\Notify', $notify, 'notify');//五分钟
            return true;
        }
        if($res == '禁止入款') return true;

        $data['金额更新'] = $res;
        //错误添加到订单回调日志
        logs($data,$type = 'order/notify/'.$data['config']['code']);

        return false;
    }
}
