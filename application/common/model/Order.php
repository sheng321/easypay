<?php

namespace app\common\model;
use app\common\service\ModelService;

/**
 * 订单支付表
 */
class Order extends ModelService {

     /**
     * 绑定数据表
     * @var string
     */
    protected $table = 'cm_order';

    /**
     * redis (复制的时候不要少数组参数)
     * key   字段值要唯一
     * @var array
     */
    protected $redis = [
        'is_open'=> true,
        'ttl'=> 300,
        'key'=> "String:table:Order:out_trade_no:{out_trade_no}:systen_no:{systen_no}:id:{id}",
        'keyArr'=> ['id','out_trade_no','systen_no'],
    ];


    /**
     * Undocumented 分页获取
     *
     * @param integer $page
     * @param integer $limit
     * @param array $search
     * @return void
     */
    public function alist($page = 1,$limit = 10,$search = [],$uid=''){
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


    //订单 回调数据
    public static function notify($sn){
        $Order = self::quickGet(['systen_no'=>$sn]);
        $Uprofile = Uprofile::quickGet(['uid'=>$Order['uid']]);

        $data['memberid'] = $Order['uid'];
        $data['orderid'] = $Order['out_trade_no'];
        $data['transaction_id'] = $Order['systen_no'];
        $data['amount'] = $Order['amount'];
        $data['datetime'] = $Order['pay_time'];
        $data['returncode'] = '00';

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $Uprofile['secret']));
        $data['attach'] = $Order['attach'];

        return [
            'data'=>$data,
            'url'=>$Order['notify_url'],
            'order'=>[
                'id'=>$Order['id'],
                'notice'=>$Order['notice'],
            ]
        ];
    }

}