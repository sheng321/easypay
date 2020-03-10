<?php
namespace app\pay\controller;
use app\common\service\QrcodesService;
use think\Controller;
use think\facade\Cache;

class Test extends Controller
{
    public function index(){
      $token = $this->request->get('token/s','原样返回字段');
      $has = Cache::get('pay_token','');
      if(md5($has) != md5($has)) $this->redirect('http://www.baidu.com');

        $pay_memberid = config('set.memberid');   //商户ID
        $Md5key =  config('set.Md5key');   //密钥

        $pay_orderid = getOrder('c');    //测试订单号
        $pay_amount = $this->request->get('amount',100.00);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s",time());  //订单时间
        $pay_bankcode = $this->request->get('code','');
        $pay_notifyurl = 'http://'.$_SERVER['HTTP_HOST'].'/pay.php/test/notify.html';   //服务端返回地址
        $pay_callbackurl = 'http://'.$_SERVER['HTTP_HOST'].'/pay.php/test/callback.html';  //页面跳转返回地址

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
        $jsapi["pay_attach"] = $token;

        //基础数据
        $basic_data = [
            'title'  => '测试',
            'order'  => $jsapi ,
            'data' => \app\common\model\PayProduct::codeTitle(),
        ];

        return $this->fetch('', $basic_data);
    }


    public function test(){

        $ip =  get_client_ip();
        if(!in_array($ip,['127.0.0.1','113.61.61.77'])){
            $this->redirect('http://www.baidu.com');
        }

        //平台商户
        $pay_memberid = '20100005';   //商户ID
        $Md5key =  'f3625fe82f1ca0f2b20c228457a1cdf2fd96faf0';   //密钥


        //二级商户
        $pay_memberid = '20100008';   //商户ID
        $Md5key =  '84f0e083cc91e631cd5712a6cd80323f9a2663bb';   //密钥


        $pay_orderid = getOrder('c');    //测试订单号
        $pay_amount = $this->request->get('amount',100.00);    //交易金额
        $pay_applydate = date("Y-m-d H:i:s",time());  //订单时间
        $pay_bankcode = $this->request->get('code','');
        $pay_notifyurl = 'http://'.$_SERVER['HTTP_HOST'].'/pay.php/test/notify.html';   //服务端返回地址
        $pay_callbackurl = 'http://'.$_SERVER['HTTP_HOST'].'/pay.php/test/callback.html';  //页面跳转返回地址

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

        return $this->fetch('index', $basic_data);
    }


    public function notify(){
        logs($this->request->post(),'test');
        return 'notify';
    }

    public function callback(){
        return '交易成功';
    }




}
