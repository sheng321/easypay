<?php
namespace app\pay\controller;
use app\common\controller\PayController;
use app\common\model\Order;
use app\common\model\Uprofile;


/**
 * 支付订单查询接口
 * Class Query
 * @package app\pay\controller
 */
class Query extends PayController
{
    public function index(){
        $param =   $this->request->only(["pay_memberid" ,"pay_orderid","pay_md5sign"],'post');

        //商户属性
       $Uprofile =  Uprofile::quickGet(['uid'=>$param['pay_memberid']]);
       if(empty($Uprofile) || $Uprofile['who'] != 0 )  __jerror('商户号不存在');

        if(!check_sign($param,$Uprofile['secret']))  __jerror('签名错误');

        $Order =  Order::quickGet(['out_trade_no'=>$param['pay_orderid']]);
        if(empty($Order))   __jerror('订单号不存在');

        if($Order['pay_status'] == 2){
            $data['returncode'] = '00';
            $data['trade_state'] = 'SUCCESS';
        }else{
            $data['returncode'] = '01';
            $data['trade_state'] = 'NOTPAY';
        }

        $data['memberid'] = $Order['pay_memberid'];
        $data['orderid'] = $Order['systen_no'];
        $data['amount'] = $Order['amount'];
        $data['time_end'] = $Order['pay_time'];
        $data['transaction_id'] = $Order['out_trade_no'];

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $Uprofile['secret']));

        return __jsuccess('查询成功',$data);
    }

}