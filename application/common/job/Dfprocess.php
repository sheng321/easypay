<?php
namespace app\common\job;
use think\Exception;
use think\queue\Job;
use app\common\model\Df;
use app\withdrawal\service\Payment;

class Dfprocess {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
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
            // 如果任务执行成功，删除任务
            $job->delete();
            return;
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

        if(empty($order) || $order['status'] != 1 || $order['lock_id'] != 0){
           return false;
        }

        return true;
    }

    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data,$order)
    {
        $this->model = model('app\common\model\Df');

        //使用事物保存数据
        $this->model->startTrans();

        try {
            //选择通道并且处理中
            $save1 =  $this->model->save($data['order'],['id'=>$data['order']['id']]);
            $save = model('app\common\model\Umoney')->isUpdate(true)->saveAll($data['Umoney']);
            $add = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($data['UmoneyLog']);

            if ( !$save1 || !$save || !$add )  throw new Exception('数据更新失败，请稍后再试!');

            $Payment = Payment::factory($data['channel']['code']);

            //这里提交代付申请
            $order['channel_amount'] = $data['order']['channel_amount'];
            $order['bank'] = json_decode($order['bank'],true);
            $result = $Payment->pay($order);
            if(empty($result)|| !is_array($result) || !isset($result['code'])) throw new Exception($data['channel']['code'] . '代付通道异常，请稍后再试!');

            //成功
            if($result['code'] == 1){
                //更新数据
                if(!empty($result['data']) && is_array($result['data'])){
                    $arr = [];
                    foreach ($result['data'] as $k1 => $v1){
                        if($k1 == 'actual_amount') $arr[$k1] = $v1;//实际到账
                        if($k1 == 'transaction_no') $arr[$k1] = $v1;//上游单号
                        if($k1 == 'remark') $arr[$k1] = $v1;//备注
                    }
                    if(!empty($arr)){
                        $arr['id'] = $data['order']['id'];
                        $this->model->save($arr,['id'=>$data['order']['id']]);
                    }
                }

                $this->model->commit();
                //添加异步查询订单状态
                \think\Queue::later(60,'app\\common\\job\\Df', $data['order']['id'], 'df');//一分钟

            }else{

                throw new Exception($data['channel']['code'] . '申请代付失败，请检查上游订单状，上游返回：'.$result['msg']."\n");
            }

        } catch (\Exception $e) {
            $this->model->rollback();
            $this->model->save(['id'=>$data['order']['id'],'remark'=>$e->getMessage()],['id'=>$data['order']['id']]);
            return false;
        }



        
        return true;
    }
}
