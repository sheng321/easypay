<?php
namespace app\common\job;
use think\Db;
use think\Exception;
use think\queue\Job;
use app\common\model\Df;
use app\withdrawal\service\Payment;

/**
 * 批量处理代付订单
 * Class Dfprocess
 * @package app\common\job
 */
class Dfprocess {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$task)
    {
        if(empty($task) || !is_array($task)){
            $job->delete();
            return;
        }

        $data =  array_shift($task);

        ini_set('max_execution_time','120');
        $order =  Df::where(['id'=>$data['order']['id']])->find();
        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($order);
        if(!$isJobStillNeedToBeDone){
            $job->delete();
            return;
        }

        $isJobDone = $this->doHelloJob($data,$order);
        unset($order);
        if ($isJobDone) {
             //如果没有元素了
            if(empty($task)){
                $job->delete();
                return;
            }else{
                //重新发起
                \think\Queue::later(3,'app\\common\\job\\Dfprocess', $task, 'dfprocess');
                return;
            }

        }else{
            if ($job->attempts() > 1) {//只执行一次
                $job->delete();
                return;
            }
        }
    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($order){

        if(empty($order) || $order['status'] != 1 || $order['lock_id'] != 0 || $order['remark'] == '批量操作成功'){
           return false;
        }
        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data)
    {

        $Df = model('app\common\model\Df');
        $Umoney = model('app\common\model\Umoney');
        $UmoneyLog = model('app\common\model\UmoneyLog');

        try {
            //选择通道并且处理中
            $Df->startTrans();
            $Umoney->startTrans();
            $UmoneyLog->startTrans();

            $result =  $Df->save($data['order'],['id'=>$data['order']['id']]);
            if($result === false)  throw new Exception('数据更新失败');

            $result =  $Umoney->isUpdate(true)->saveAll($data['Umoney']);
            if($result === false)throw new Exception('数据更新失败');

            $result =  $UmoneyLog->isUpdate(false)->saveAll($data['UmoneyLog']);
            if($result === false) throw new Exception('数据更新失败');

            $Df->commit();
            $Umoney->commit();
            $UmoneyLog->commit();

            $Payment = Payment::factory($data['channel']['code']);
            //这里提交代付申请
            $order =  Df::where(['id'=>$data['order']['id']])->find();
            if(empty($order) || empty($order['channel_amount']) || $order['status'] != 2 ) throw new Exception('数据更新失败');
            if($order['remark'] == '批量操作成功') throw new Exception('批量操作成功');

            $order['bank'] = json_decode($order['bank'],true);
            $result = $Payment->pay($order);
            if(empty($result)|| !is_array($result) || !isset($result['code'])) throw new Exception($data['channel']['code'] . '代付通道异常，请稍后再试!');

            //成功
            if($result['code'] == 1){

                //添加异步查询订单状态
                \think\Queue::later(60,'app\\common\\job\\Df', $data['order']['id'], 'df');//一分钟

                $arr['remark'] = '批量操作成功';
                //更新数据
                if(!empty($result['data']) && is_array($result['data'])){
                    foreach ($result['data'] as $k1 => $v1){
                        if($k1 == 'actual_amount') $arr[$k1] = $v1;//实际到账
                        if($k1 == 'transaction_no') $arr[$k1] = $v1;//上游单号
                    }
                }
                $arr['id'] = $data['order']['id'];
                Db::table('cm_withdrawal_api')->where(['id'=>$data['order']['id']])->update($arr);

            }else{
                throw new Exception($data['channel']['code'] . '申请代付失败，上游返回：'.$result['msg']."\n");
            }

        } catch (\Exception $e) {
            $Df->rollBack();
            $Umoney->rollBack();
            $UmoneyLog->rollBack();

            $msg =  $e->getMessage();
            if($msg == '批量操作成功' || $msg == '数据更新失败') return false;
            if(empty($msg)){
                $msg = '未知错误';
                $trace =  $e->getTrace();
                if(is_array($trace[0]['args'][0])  && !empty($trace[0]['args'][0]['msg'])){
                    $msg = $trace[0]['args']['0']['msg'];
                }elseif(is_string($trace[0]['args'][0])){
                    $msg = $trace[0]['args'][0];
                }
            }

            Db::table('cm_withdrawal_api')->where(['id'=>$data['order']['id']])->update(['remark'=>$msg]);
            return false;
        }

        return true;
    }
}
