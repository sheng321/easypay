<?php

namespace app\common\model;

use app\common\service\UserService;
use think\Db;
class Withdrawal extends UserService {
    
    
    public static function init()
    {
        self::event('after_write', function ($withdrawal) {
            if(!empty($withdrawal->mch_id)){
                $balance = \app\common\model\Umoney::get_amount($withdrawal->mch_id);
                if($withdrawal->status == 1){//商户申请提现
                    //商户减余额
                    Db::name("member_monney")->where("uid",$withdrawal->mch_id)->setDec('balance',$withdrawal->actual_amount);
                    //插入日志
                    Db::name("mch_record")->insert(array('mch_id'=>$withdrawal->mch_id,'before_balance'=>$balance,'change'=>$withdrawal->actual_amount,'type'=>1,'balance'=>\app\common\model\Umoney::get_amount($withdrawal->mch_id),'remark'=>'申请提现,减：'.$withdrawal->actual_amount));
                    //插入日志
                }elseif($withdrawal->status == 4){//后台退款
                     //商户加余额
                    Db::name("member_monney")->where("uid",$withdrawal->mch_id)->setInc('balance',$withdrawal->actual_amount);
                    //插入日志
                    Db::name("mch_record")->insert(array('mch_id'=>$withdrawal->mch_id,'before_balance'=>$balance,'change'=>$withdrawal->actual_amount,'balance'=>\app\common\model\Umoney::get_amount($withdrawal->mch_id),'type'=>2,'remark'=>'提现失败退款：加'.$withdrawal->actual_amount)); 
                }
            }
        });
    }
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
            $this->commit();//事务提交
        } catch (\Exception $e) {
            // 回滚事务
            $this->rollback();
            return $e->getMessage();
        }
        return true;
    }
    /**
     * Undocumented 锁定/解除 出款/退款
     *
     * @param [int] $type 类型1-4
     * @param [obj] $info 数据
     * @param [type] $user_name 登录账号
     * @return void
     */
    public function saveWith($data,$info,$user_name){
        switch ($data['type']) {
            case '1'://锁定
                $info->is_lock = 1;
                $info->status = 2;
                $info->lock_name = $user_name;
                break;
            case '2'://解除
                if($info->lock_name != $user_name){
                    return __error('只能由账号【'.$info->lock_name.'】来解除');
                }
                $info->is_lock = 2;
                $info->status = 1;
                $info->lock_name = '';
                $info->channel = '';
                break;
            case '3'://出款
                if($info->is_lock != 1){
                    return __error('请先锁定');
                }
                if($info->status == 3 || $info->status == 4){
                    return __error('订单状态不对');
                }
                $info->status = 3;
                $info->remark = $data['text'];//备注
                break;
            default://退款
                if($info->is_lock != 1){
                    return __error('请先锁定');
                }
                if($info->status == 3 || $info->status == 4){
                    return __error('订单状态不对');
                }
                $info->status = 4;
                $info->remark = $data['text'];//备注
                break;
        }
        $info->save();
        return __success('操作成功');
    }



}