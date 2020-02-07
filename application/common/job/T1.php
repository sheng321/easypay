<?php
namespace app\common\job;
use app\common\model\Order;
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

        // 有些消息在到达消费者时,可能已经不再需要执行了
        $isJobStillNeedToBeDone = $this->checkDatabaseToSeeIfJobNeedToBeDone($data);
        if($isJobStillNeedToBeDone === false ){
            $job->delete();
            return;
        }

        $isJobDone = $this->doHelloJob($data);
        if($isJobDone !== true ) __log(json_encode($isJobDone,320),3);

        $job->delete();//执行一次
        return;

    }

    /**
     * 有些消息在到达消费者时,可能已经不再需要执行了
     * @param array|mixed    $data     发布任务时自定义的数据
     * @return boolean                 任务执行的结果
     */
    private function checkDatabaseToSeeIfJobNeedToBeDone($data){
        return true;
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
        $Umoney = model('app\common\model\Umoney');
        $channel_money  = $Umoney::quickGet($data['id']); //通道金额
        $res['channel_money'] = $channel_money;
        if(empty($channel_money) || empty($channel_money['frozen_amount_t1'])){
            $res['msg'] = 'T1解冻，通道金额不存在';
            return $res;
        }
        if($channel_money['frozen_amount_t1'] < $data['money']){
            $res['msg'] = 'T1解冻，T1冻结金额小于解冻金额';
            return $res;
        }

        $update[] = [
            'id'=>$channel_money['id'],
            'total_money'=>Db::raw('balance+'.$data['money']),
            'balance'=>Db::raw('frozen_amount_t1-'.$data['money']),
        ];
        $log[] = [
            'uid'=>0,
            'channel_id'=>$channel_money['channel_id'],
            'before_balance'=>$channel_money['balance'],
            'balance'=>$channel_money['balance'] + $data['money'],
            'change'=>$data['money'],
            'relate'=>$data['system_no'],
            'type'=>17,//T1解冻
            'type1'=>1,//通道
        ];
        $Umoney->startTrans();
        $save = $Umoney->isUpdate(true)->saveAll($update);//批量修改金额
        $save1 = model('app\common\model\UmoneyLog')->isUpdate(false)->saveAll($log);//批量添加变动记录
        if (!$save || !$save1){
            $Umoney->rollback();
            $res['msg'] = 'T1解冻，更新数据失败';
            return $res;
        }
        $Umoney->commit();
        return true;
    }
}