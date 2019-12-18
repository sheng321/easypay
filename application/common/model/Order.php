<?php

namespace app\common\model;

use app\common\service\ModelService;

/**
 * 支付通道
 */
class Order extends ModelService {


     /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_order';

    /**
     * Undocumented 分页获取
     *
     * @param integer $page
     * @param integer $limit
     * @param array $search
     * @return void
     */
    public function list($page = 1,$limit = 10,$search = [],$uid=''){
        if(empty($uid)){
            $where = [];
        }else{
            $where = ['mch_id'=>$uid];
        }
        //搜索条件
        $searchField['eq'] = ['status'];
        //$searchField['like'] = ['remark','title'];
        $where = search($search,$searchField,$where);
        $field = "*";
        $list = $this->alias('a')->where($where)->page($page,$limit)->field($field)->select()->toArray();
        empty($list) ? $msg = '暂无数据！' : $msg = '查询成功！';
        $list = [
            'code'  => 0,
            'msg'   => $msg,
            'count' => count($list),
            'info'  => ['limit'=>$limit,'page_current'=>$page,'page_sum'=>ceil(count($list) / $limit)],
            'data'  => $list,
        ];
        return $list;
    }
    /**
     * Undocumented 补发通知
     *
     * @return void
     */
    public function orderSend($data,$id){
        //curl发送
        $result = 'success';
        if($result == 'success'){
            $this->where('id',$id)->update(array('notice'=>2));
        }
        return $result;
    }
    /**
     * Undocumented function
     *
     * @param [type] $info 查询订单结果
     * @param [type] $amount 实际支付金额
     * @param string $transaction_no 三方单号
     * @return void
     */
    public function orderUpdate($info,$amount,$type = '1',$transaction_no =''){
        $pay_time = date("Y-m-d H:i:s",time());//支付时间
        //查看商户状态，对否可以入金/model('app\common\model\Umember');
        //只有未支付订单才能更新
        if($info->pay_status != 1){
            return __error('订单状态不对');
        }
        //下单金额 - 实际支付
        if(abs($info->amount) - abs($amount) > 1){
            return __error('金额不一致');
        }
        //验证ip
        $info->startTrans();
        try {
            $info->pay_status     = 2;//支付状态
            $info->transaction_no = $transaction_no;//三方单号
            $info->actual_amount  = $amount;//实际支付
            $info->pay_time       = $pay_time;//支付时间
            $info->save();
            $info->commit();
        } catch (\Exception $e) {
            // 回滚事务
            $info->rollback();
            return __error($e->getMessage());
        }
        //发送回调
        $data = [

        ];
        $data['sign'] = 'xxxxxxxxxxx';
        $result = $this->orderSend($data,$info->id);
        return __success('强制入单成功,异步回调返回：'.$result);
    }



}