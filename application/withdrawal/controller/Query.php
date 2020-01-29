<?php
namespace app\withdrawal\controller;
use app\common\controller\WithdrawalController;
use app\common\model\Df;
use app\common\model\Uprofile;


/**
 * 支付订单查询接口
 * Class Query
 * @package app\pay\controller
 */
class Query extends WithdrawalController
{
    public function index(){
        $param =   $this->request->only(["pay_memberid" ,"pay_orderid","pay_md5sign"],'post');

        //商户属性
       $Uprofile =  Uprofile::quickGet(['uid'=>$param['pay_memberid']]);
       if(empty($Uprofile) || $Uprofile['who'] != 0 )  __jerror('商户号不存在');
        if(empty($Uprofile['df_api1']) || $Uprofile['df_api1'] != '1' )  __jerror('API代付接口未开通，请联系客服处理。');
        if( $Uprofile['df_api'] != '1' )  __jerror('商户未开启API代付接口功能。。。');

        if(!check_sign($param,$Uprofile['df_secret']))  __jerror('签名错误');

        $Order =  Df::quickGet(['out_trade_no'=>$param['pay_orderid']]);
        if(empty($Order) || $Order['mch_id'] != $param['pay_memberid'] )   __jerror('订单号不存在');

        if($Order['pay_status'] == 2){
            $data['returncode'] = '00';
            $data['trade_state'] = 'SUCCESS';
        }else{
            $data['returncode'] = '01';
            $data['trade_state'] = 'NOTPAY';
        }

        $data['memberid'] = $Order['pay_memberid'];
        $data['orderid'] = $Order['system_no'];
        $data['amount'] = $Order['amount'];
        $data['time_end'] = $Order['pay_time'];
        $data['transaction_id'] = $Order['out_trade_no'];

        ksort($data);
        $md5str = "";
        foreach ($data as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }
        $data['sign'] = strtoupper(md5($md5str . "key=" . $Uprofile['df_secret']));

        return __jsuccess('查询成功',$data);
    }

}
