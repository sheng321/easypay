<?php
namespace app\common\job;
use app\common\model\Umoney;
use think\Db;
use think\queue\Job;

class T1 {
    /**
     * fire方法是消息队列默认调用的方法
     * @param Job            $job      当前的任务对象
     * @param array|mixed    $data     发布任务时自定义的数据
     */
    public function fire(Job $job,$data)
    {
        if ($job->attempts() > 3) {
            $job->delete();
            return;
        }

        //多线程添加锁
        try{
            $lock_val = 'T1:'.$data['id'];
            $isJobDone =  Lock::queueLock(function ($res)use($data){
                $isJobDone = $this->doHelloJob($data);
                return $isJobDone;
            },$lock_val,60,60);
        }catch (\Exception $e){
            $job->release(10);//出现异常
            return $e->getMessage();
        }
        if($isJobDone !== true ){
            $job->failed();
            __log(json_encode($isJobDone,320),3);//记录到异常日志列表
            return;
        }

        $job->delete();
        return;
    }



    /**
     * 根据消息中的数据进行实际的业务处理...
     */
    private function doHelloJob($data)
    {

        $res['data'] = $data;

        /**
         * $t1['money'] = $Order['upstream_settle'];//变动金额
        $t1['id'] = $channel_money['id'];//金额账户ID
        $t1['system_no'] = $system_no;//关联订单号
         */
        
        $channel_money  = Db::table('cm_money')->where(['id'=>$data['id']])->find(); //通道金额
        $res['channel_money'] = $channel_money;
        if(empty($channel_money) || empty($channel_money['frozen_amount_t1'])){
            $res['msg'] = 'T1解冻，通道金额不存在';
            return $res;
        }
        if($channel_money['frozen_amount_t1'] < $data['money']){
            $res['msg'] = 'T1解冻，T1冻结金额小于解冻金额';
            return $res;
        }
        Umoney::delRedis($data['id']);

        $change['change'] = $data['money'];//变动金额
        $change['relate'] = $data['system_no'];//关联订单号
        $change['type'] = 17;//T1解冻
        $res4 =  Umoney::dispose($channel_money,$change);

        $update = $res4['data'];
        $log = $res4['change'];


        Db::startTrans();
        try{
            $save = (new Umoney())->isUpdate(true)->saveAll($update);//批量修改金额
            if (!$save ) throw new \Exception('T1解冻，更新数据失败');
            $save1 = (new UmoneyLog())->isUpdate(false)->saveAll($log);//批量添加变动记录
            if (!$save1 ) throw new \Exception('T1解冻，更新数据失败');
            Db::commit();
        }catch (\Exception $exception){
            Db::rollback();
            $res['msg'] = $exception->getMessage();
            return $res;
        }

        return true;
    }
}
