<?php
namespace app\pay\controller;
use app\common\controller\PayController;

class Test extends PayController
{
    public function index(){
        date_default_timezone_set("PRC");

        $pay_memberid = "1001350";   //商户ID
        $Md5key = "1y8uawfimek7eyroyms6u2t9gfcd2rv7";   //密钥

        $pay_orderid = getOrder('c');    //订单号
        $pay_amount = 100.00;    //交易金额
        $pay_applydate = date("Y-m-d H:i:s",time());  //订单时间
        $pay_bankcode = $_GET['pay_bankcode'] ? $_GET['pay_bankcode'] : 0;   //银行编码
        $pay_notifyurl = 'http://'.$_SERVER['HTTP_HOST'].'/Run/notify.php';   //服务端返回地址
        $pay_callbackurl = 'http://'.$_SERVER['HTTP_HOST'].'/api/yunjihe/return.php';  //页面跳转返回地址

        $tjurl = "";   //网关提交地址


        $jsapi = array(
            "pay_memberid" => $pay_memberid,
            "pay_orderid" => $pay_orderid,
            "pay_amount" => $pay_amount,
            "pay_applydate" => $pay_applydate,
            "pay_bankcode" => $pay_bankcode,
            "pay_notifyurl" => $pay_notifyurl,
            "pay_callbackurl" => $pay_callbackurl,
        );

        ksort($jsapi);

        $md5str = "";
        foreach ($jsapi as $key => $val) {
            $md5str = $md5str . $key . "=" . $val . "&";
        }

        $sign = strtoupper(md5($md5str . "key=" . $Md5key));

        $jsapi["pay_md5sign"] = $sign;
        $jsapi["pay_productname"] = '会员服务'; //商品名称
        $jsapi["pay_attach"] = '原样返回字段';

        //基础数据
        $basic_data = [
            'title'  => '测试',
            'order'  => $jsapi ,
            'data' => \app\common\model\PayProduct::codeTitle(),
        ];

        return $this->fetch('', $basic_data);
    }
}
