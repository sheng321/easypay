<?php

namespace app\common\model;

use app\common\service\UserService;
use think\Db;
class Withdrawal extends UserService {
    /**
     * Undocumented 分页获取所有记录数
     *
     * @param integer $page
     * @param integer $limit
     * @param array $data 条件
     * @return void
     */
    public function list($page = 1,$limit = 10,$data = [],$mch){
        $where = ['a.mch_id' => $mch];
        $searchField['eq'] = ['status','system_sn'];
        $searchField['like'] = ['account_name','create_time'];
        $field = "a.id,a.system_sn,a.total_amount,a.total_fee,a.actual_amount,a.status,a.create_time,a.remark,b.account_name,b.card_number,b.bank_name,b.location";
        $where = search($data,$searchField,$where);
        //获取总数
        $count = $this->count();
        $list = $this->alias('a')->where($where)->join('bank_card b','a.bank_card_id = b.id')->order('a.id','desc')->page($page,$limit)->field($field)->select()->toArray();
        empty($list) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil($count / $limit)],
            'data'  => $list,
        ];
        return $list;
    }
    public function wlist($page = 1,$limit = 10,$data = []){
        $where = array();
        $searchField['eq'] = ['status','system_sn'];
        $searchField['like'] = ['account_name','create_time'];
        $field = "a.id,a.system_sn,a.is_lock,a.channel,a.lock_name,a.mch_id,a.update_time,a.total_amount,a.total_fee,a.actual_amount,a.status,a.create_time,a.remark,b.account_name,b.card_number,b.bank_name,b.location";
        $where = search($data,$searchField,$where);
        //获取总数
        $count = $this->count();
        $list = $this->alias('a')->where($where)->join('bank_card b','a.bank_card_id = b.id')->order('a.id','desc')->page($page,$limit)->field($field)->select()->toArray();
        empty($list) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => $count,
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil($count / $limit)],
            'data'  => $list,
        ];
        return $list;
    }
    /**
     * Undocumented 创建提现申请
     *
     * @param [type] $mch 商户号
     * @param integer $total_fee 手续费
     * @param [type] $data 提交数据
     * @return void
     */
    public function add($mch,$total_fee = 0,$data){
        $this->startTrans();//开启事务
        try {
            $this->create([ 
                "mch_id" => $mch, // 商户号
                "system_sn" => getOrderId(), // 系统单号
                "bank_card_id" => $data['bank_card_id'], //银行卡ID
                "total_amount" => $data['total_amount'], //申请金额
                "total_fee" => $total_fee, //手续费
                "actual_amount" => $data['total_amount']-$total_fee,//实际到账
                "create_time" =>date("Y-m-d H:i:s",time())
            ]);
            //余额减少
           // Db::name("member_monney")->where("uid",$mch)->setDec("balance",$data['total_amount']);
            //冻结增加
            //Db::name("member_monney")->where("uid",$mch)->setInc("freeze",$data['total_amount']);
            $this->commit();//事务提交
        } catch (\Exception $e) {
            // 回滚事务
            $this->rollback();
            return $e->getMessage();
        }
        return true;
    }




}