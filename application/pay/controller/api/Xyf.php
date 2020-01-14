<?php
namespace app\pay\controller\api;
use app\common\controller\PayController;
use app\common\model\Channel;
use app\common\model\Order;
use think\facade\Url;
use think\helper\Str;
use tool\Curl;
use tool\rsa\Xyfrsa;

//信誉付
class Xyf extends PayController
{
    //通道信息
    protected $channel;

    //通道配置信息
    protected $config;

    //公钥
    private $public = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlHz2CYGnJp0OTmpqF7ukcUW5vMmrZuIDf0a6t/Pg8Mkps/g6yi32Ak2z6AH1u7zyprXmsdU9K08i9DIavih/3h2tW6NiMw3LOceyUtoC0jGv3zOGIhov1pjOhlA9J5Td0NOn5vUypqDRXZLxnDrqBSVzv9d0bNR2ud9rjYo9pfQPv1Zeym3lYVzCk9/MApS98+JjlkqSErpQna61lTQs6VC+/QQOrbvpqwx9+k3JUs7qItxuIc7frY5CSJ3PiEeXecpWKFg7OGLZNbi27/0xtCBzPQNUG4XDxCEjqDoXTNOaw2AIonffqJlpZECG/MGBOGYQv4KPGDcILeI85idy2QIDAQAB";

    //私钥
    private $private = 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCUfPYJgacmnQ5OamoXu6RxRbm8yatm4gN/Rrq38+DwySmz+DrKLfYCTbPoAfW7vPKmteax1T0rTyL0Mhq+KH/eHa1bo2IzDcs5x7JS2gLSMa/fM4YiGi/WmM6GUD0nlN3Q06fm9TKmoNFdkvGcOuoFJXO/13Rs1Ha532uNij2l9A+/Vl7KbeVhXMKT38wClL3z4mOWSpISulCdrrWVNCzpUL79BA6tu+mrDH36TclSzuoi3G4hzt+tjkJInc+IR5d5ylYoWDs4Ytk1uLbv/TG0IHM9A1QbhcPEISOoOhdM05rDYAiid9+omWlkQIb8wYE4ZhC/go8YNwgt4jzmJ3LZAgMBAAECggEANjWgxTu2QFyaOnqTHPL+R/RCqO+fScI9sJur0ziP6JgoR3HaVLGO3Kxtf8gnZFDI7Z//BvFokYUkp64bIU070WVYQtpVIXpptUB4k9LPsNk+8eenko+o41mKHHLywJ6SlIiRBbqCsV6I0Payimzfvt07ctA/yvOOHLG6XEQZ2Zm/l9tJ74COC6ZjyBj80atVcZ8cZPUMLGPI7+5y7XWc1e+1DgmOmeNujgMIxkTeKJ+rbCIrgheLE4lgy+I7wnz2r3H4Ly8oFpZ97sYJGcqav+FJObtn35uGlGVoFqatt7p4u1EG8CWXQSHvGi93mjxzGRQfbhqsPPBY9AwfZXQKAQKBgQDWvhpVc1jiLIFzzsCXtzhaBJxp5eT1flANEKg2HUTfB0whY/6nSRv2AJRHB85HunzyQNVbMo76dXhXR+Rlcscry07oTJ+bCnIgi8TLTWOPsWnqm5wqT1PwVGfHNjrmfVhinEaH3ynpcG0qT/zF3gREfBSNqMSQI6WQ8Oa1t/9guQKBgQCxBDMYAvo/L8JMSFAXhtP1NFKvtfz09wYJY/zx09FSFPxCYAyxuASAoQ7r7cBR9y9obIy86BHPZtEdmUTbK/EYzDLjsSTDpA7ufbMKAKy3GupO59e/0UBVKO9ul1+MPtHnLhQQmLbYo0j7sQIwa8wuXD5rHoJPP+4rM8TxwOBTIQKBgQDP14lXYWAK8LaOpvLjJNOm1MWq/XagYRQLwS59ydBp6P83ZjgII4urix34rcZqyEW8lyGptgKKyX2jRJL72Z6Kdam2zsq/3dleRMlBWHLflgCEsP3yOXttpdQYDRXvFiygrM7bHRTMuyL9jBOEU4Ff45RlE47ET6wk3/T8tsy3CQKBgAx4eEUDyK905OJM4d+Wbw5CPmUElCqJ0JIOyj4bJw76TX87lwlaJm8NaaizWi4sFNntc8jHLKII40iLiK9MHDMcB6XE0As8XpTspBVbUM+hhMpESQ3JZxfYx21qGqAduNnphB5bM951OmoI4VeZ8It/kiInxxRgM541inVhmiwhAoGABSnBwQjxVxTU0a0XsVMIvm1bYrs35/uHRQDQv+dTuZU//HoP1rCIoiHkYFNufszQRA/fHnrA3+NN+eHNZ/rIaZ3zs5d9ScBvFEjPfJIHBxQLHUK6yoIN2F/+dqJ8pPPP0y/mMZow/WrC3XZKGBl9WGS5o6M26ehutElHmXACSxk=';
    //平台公钥
    private $webkey = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAitLVEnGiepZTpOLiJuR7hdrT3KRaIp9bXoWe1bww+1OGV+eZnG3PsjqZfCVq6FRBsHXZbtyVTRb9QJq6sMX/AYr4Vphm5NVNofpHvz9syo6OpiF00YAKkGaB/HJd0DJJL9TQBaMxv8cTJ3pMgHJxKIUGSH5CaFZhwS7aClmCjIpUdzcufNJLQ7TiEUs8mLHLoOd7KPN0a69vYKcokysq5RcCBKaSp3SYM3qcl17BCQ5OoIbYxCIhwhdyKi/ZJnpD+866UA177ftb7+j2ntC1fdztpEAReewHJ3pTWtl92Dt6PsbHS3ripb2wdLSKgaadl5jVcsYf/l3r/JRE1bLTUwIDAQAB";

    public function __construct()
    {
        parent::__construct();
        $classArr = explode('\\',get_class());
        $code =  end($classArr);
        $config = Channel::get_config($code);
        if(empty($config)) __jerror('支付服务不存在6');
        $this->config = $config;

        //http://www.test4.com/pay.php/notify/index/Pay/Xyf.html
        $this->config['notifyUrl'] = Url::build('notify/index',['Pay'=>$code],true,true);
        //http://www.test4.com/pay.php/notify/callback.html
        $this->config['returnUrl'] = Url::build('notify/callback',[],true,true);
    }
    /*
     * array(35) {
  ["id"] => int(56)
  ["title"] => string(9) "信誉付"
  ["status"] => int(1)
  ["sort"] => int(2)
  ["remark"] => string(0) ""
  ["create_by"] => int(1)
  ["create_at"] => string(19) "2020-01-05 12:46:56"
  ["update_at"] => string(19) "2020-01-05 12:59:50"
  ["update_by"] => int(1)
  ["code"] => string(3) "Xyf"
  ["min_amount"] => string(0) ""
  ["max_amount"] => string(0) ""
  ["f_amount"] => string(0) ""
  ["ex_amount"] => string(0) ""
  ["f_multiple"] => int(0)
  ["f_num"] => string(0) ""
  ["p_id"] => string(10) "{"1":"19"}"
  ["verson"] => int(2)
  ["type"] => int(0)
  ["pid"] => int(0)
  ["c_id"] => int(0)
  ["g_id"] => string(0) ""
  ["c_rate"] => string(6) "0.0000"
  ["s_rate"] => string(6) "0.0000"
  ["mtype"] => int(0)
  ["visit"] => int(0)
  ["back_ip"] => string(5) "["*"]"
  ["mch_id"] => string(7) "2019099"
  ["signkey"] => string(32) "QIgVJWCaXpZGsMUbEqPNTuSFBRyztmlD"
  ["secretkey"] => string(0) ""
  ["gateway"] => string(26) "http://api.xinyufu.com/pay"
  ["limit_money"] => int(0)
  ["account"] => int(0)
  ["forbid"] => int(0)
  ["charge"] => int(0)
}*/

/*
 * array(29) {
  ["mch_id"] => string(8) "20100002"
  ["mch_id1"] => int(0)
  ["mch_id2"] => int(0)
  ["out_trade_no"] => string(20) "c2001051404014341565"
  ["systen_no"] => string(20) "s2001051404033232070"
  ["amount"] => string(6) "300.00"
  ["cost_rate"] => string(6) "0.0380"
  ["run_rate"] => string(6) "0.5556"
  ["total_fee"] => float(166.68)
  ["settle"] => float(133.32)
  ["agent_rate"] => int(0)
  ["agent_rate2"] => int(0)
  ["upstream_settle"] => float(11.4)
  ["agent_amount"] => int(0)
  ["agent_amount2"] => int(0)
  ["channel_id"] => int(57)
  ["channel_group_id"] => int(24)
  ["pay_code"] => string(6) "alipay"
  ["notify_url"] => string(35) "http://www.test4.com/Run/notify.php"
  ["callback_url"] => string(43) "http://www.test4.com/api/yunjihe/return.php"
  ["ip"] => string(9) "127.0.0.1"
  ["Platform"] => float(155.28)
  ["create_time"] => string(19) "2020-01-05 14:04:01"
  ["productname"] => string(12) "会员服务"
  ["attach"] => string(18) "原样返回字段"
  ["create_by"] => int(0)
  ["create_at"] => string(19) "2020-01-05 14:04:03"
  ["update_at"] => string(19) "2020-01-05 14:04:03"
  ["id"] => string(3) "188"
}*/

    //下单
    public function pay($create){
        $data = array();
        $data['merId'] = $this->config['mch_id'];
        $data['orderId'] = $create['systen_no'];
        $data['orderAmt'] = number_format($create['amount'],2,'.','');
        $data['channel'] = $create['code'];
        $data['desc'] = 'xyf';
        $data['attch'] = 'w';
        $data['smstyle'] = '1';
        $data['userId'] = 'aaa';
        $data['ip'] = get_client_ip();
        $data['notifyUrl'] = $this->config['notifyUrl'];
        $data['returnUrl'] =  $this->config['returnUrl'];
        $data['nonceStr'] = Str::random(32);

        $data['sign'] = $this->getSign($data);

        $res = json_decode(Curl::post($this->config['gateway'], http_build_query($data)),true);

        if(empty($res['data']['payurl'])){
            $msg = '获取支付链接失败!';
            if(!empty($res['msg'])) $msg = Str::substr($res['msg'],0,100) ;
            //下单失败
            Order::save(['id'=>$create['id'],'pay_status'=>1,'remark'=>$msg]);
            __jerror($msg);
        }

     return msg_get($res['data']['payurl']);
    }

    private function getSign($data){
        ksort($data);
        reset($data);
        $str = "";
        foreach($data as $k=>$v){
            if($v === '' || $v === null || in_array($k,['sign'])){
                continue;
            }
            $str .= "{$k}={$v}&";
        }

        $str .= "key=" . $this->config['signkey'];
        $md5 = strtoupper(md5($str));
        $rsa = new Xyfrsa($this->webkey, $this->private);
        return $rsa->sign($md5);
    }

    private function verifys($data){
        ksort($data);
        reset($data);

        $str = "";
        foreach($data as $k=>$v){
            if($v === '' || $v === null || in_array($k,['sign'])){
                continue;
            }
            $str .= "{$k}={$v}&";
        }
        $str .= "key=" . $this->config['signkey'];
        $md5 = strtoupper(md5($str));
        $rsa = new Xyfrsa($this->webkey, $this->private);
        return $rsa->verify($md5,$data['sign']);
    }

    //查询
    public function query($sn){
        $Order =  Order::quickGet(['systen_no'=>$sn]);
        if(empty($Order))  return ['code' => 0, 'msg' => '订单不存在：'.$sn, 'data' => []];

        $gateway = 'http://api.xinyufu.com/pay/query';
        $data = array();
        $data['merId'] =  $this->config['mch_id'];
        $data['orderId'] = $sn;
        $data['nonceStr'] = md5(time() . mt_rand(10000,99999));
        $data['sign'] = $this->getSign($data);
        $res = Curl::post($gateway, http_build_query($data));
        $resp = json_decode($res,true);

        /*
         array(4) {
  ["code"] => int(1)
  ["msg"] => string(12) "查询成功"
  ["time"] => string(10) "1578983647"
  ["data"] => array(7) {
    ["merId"] => string(7) "2019099"
    ["status"] => string(1) "1"
    ["orderId"] => string(27) "2019099s2001141225278937541"
    ["sysOrderId"] => string(18) "XISUvFGrL6p32EVwmJ"
    ["orderAmt"] => string(6) "300.00"
    ["nonceStr"] => string(32) "zk209VXMNR4Z8OwTf5c6jEFQJuoIS1YK"
    ["sign"] => string(344) "iOrtZhmHzSrXSBEBXvzNQ5igDZDP7KbRggaKZ7x9l88Hw42sHG8u1gVfGaxEeNRWWrhjyHxdkfZ7xqmenFN6YNomaO5e/oTtiYL1bM8udBd3KSdz845c7Jg/XVVaBw38zx1FIw4fv1X9IoYBRX3d2NM7iTUkegYFpJ0mJCsAtS3Y8BlVCa32aVZkcyIMivDtdRBEQCJBxySkOQlMvQII7cGP6gOvwnaKYFJZnx8bwmgoR/EhQYsQJCvKYADyWKYdsYdKLXeV00i1pvVjG8030hpWbjmlqFoH9NMxVOQ7RXoJ7U0bVKmPQ39snbHPNtdz4RVku9pi1dq8FmtFEEinIQ=="
  }
} */
        if(empty($resp) ||$resp['code'] !== 1){
            if($resp['msg']) return ['code' => 0, 'msg' => $resp['msg'], 'data' => []];
           return  ['code' => 0, 'msg' => '查询失败', 'data' => []];
        }
        if( empty($resp['data']) || $resp['data']['status'] != '1'   ){
            return ['code' => 0, 'msg' => '订单：'.$sn.'未支付', 'data' => []];
        }

        $flag = $this->verifys($resp['data']);
        if(!$flag)  return ['code' => 0, 'msg' => '查询失败：验签不通过', 'data' => []];

        $orderAmt = floatval($resp['data']['orderAmt']);
        if(abs($orderAmt - $Order['amount']) >1) return ['code' => 0, 'msg' => '查询订单金额不匹配：'.$orderAmt, 'data' => []];

          //添加到订单查询日志
         logs($res,$type = 'order/query/'.date('Ymd').'/'.$Order['channel_id']);
         return ['code' => 1, 'msg' => '查询成功！', 'data' => $res];
    }
    //回调
    public function notify(){

        $param =  $this->getParam('param');
        if(empty($param)) __jerror('no_data');
        if($param['status'] !== '1') __jerror('pay_fail');

        $this->config['returnBack'] = 'success';//返回数据
        $this->config['transaction_no'] =$param['sysOrderId']; //第三方订单号
        $this->config['amount'] = $param['orderAmt'];//下订单金额
        //$this->config['upstream_settle'] = 0;//上游结算金额

        //获取订单信息
        $orderid = $param['orderId'];
        $order = $this->checkOrderNotify($orderid);
        $flag = $this->verifys($param);
        if(!$flag) __jerror('sign_wrong');

        return $this->async($order);
    }

    public function callback(){
        echo '处理成功'; exit;
    }

}
