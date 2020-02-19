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
        if(empty($Order) ||$Order['mch_id'] !== $param['pay_memberid'] )   __jerror('订单号不存在');

        $data = array();
        if($Order['pay_status'] == 2){
            //已支付
            $data['returncode'] = "00";
            $data['trade_state'] = 'SUCCESS';
        }else{
            $data['returncode'] = "01";
            $data['trade_state'] = 'NOTPAY';
        }

        $data['memberid'] = $param['pay_memberid'];
        $data['orderid'] = $param['pay_orderid'];
        $data['amount'] = $Order['amount'];
        $data['time_end'] = $Order['pay_time'];
        $data['transaction_id'] = $Order['system_no'];

        ksort($data);
        reset($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $Uprofile['secret']));

        return __jsuccess('查询成功',$data);
    }


    /**
     * 查询订单状态
     * @return \think\response\Json
     */
    public function checkstatus(){

        $out_trade_no =   $this->request->post(["pay_orderid/s"],'');
        if(empty($out_trade_no) )   __jerror('订单号不存在');

        $Order =  Order::quickGet(['out_trade_no'=>$out_trade_no]);
        if(empty($Order) ) __jerror('订单号不存在');

        $time =   time() - strtotime($Order['create_time'])  - 5*60;//时间判断
        if($Order['pay_status'] == 2 && $time <= 0){
            //已支付
            $data['status'] = "ok";
        }else{
            $data['status'] = "no";
        }

        return __jsuccess('查询成功',$data);
    }

}
