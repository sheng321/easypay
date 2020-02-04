<?php
namespace app\pay\controller\api;
use app\common\controller\PayController;
use app\common\model\Channel;
use app\common\model\Order;
use think\facade\Url;
use think\helper\Str;
use tool\Curl;
use tool\rsa\Xyfrsa;

//陛下
class Bx extends PayController
{
    //通道信息
    protected $channel;

    //通道配置信息
    protected $config;


    public function __construct()
    {
        parent::__construct();
        $classArr = explode('\\',get_class());
        $this->config = $this->set_api_config($classArr);
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
  ["system_no"] => string(20) "s2001051404033232070"
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

        $native = array(
            "ORDER_ID" => $create['system_no'],
            "ORDER_AMT" => number_format($create['amount'],2,'.','') ,
            "USER_ID" =>$this->config['mch_id'],
            "BUS_CODE" => $create['code'] ,
            "PAGE_URL" => $this->config['notifyUrl'],
            "BG_URL" =>$this->config['returnUrl'],
        );

        $sign_str = $native['ORDER_ID'].$native['ORDER_AMT'].$native['USER_ID'].$native['BUS_CODE'];
        $sign_1 = md5($sign_str);
        $sign_2 = md5($sign_1.$this->config['signkey']);

        $native['SIGN'] = substr($sign_2,8,16);

       return msg_post($this->config['gateway'],$native);

    }

    //查询
    public function query($Order){

        $data = array();
        $data['USER_ID'] =  $this->config['mch_id'];
        $data['ORDER_ID'] = $Order['system_no'];


        $sign_str = $data['ORDER_ID'].$data['USER_ID'];
        $sign_1 = md5($sign_str);
        $sign_2 = md5($sign_1.$this->config['signkey']);

        $data['SIGN'] = substr($sign_2,8,16);

        $res = Curl::post($this->config['queryway'], http_build_query($data));
        $resp = json_decode($res,true);

        if(empty($resp) ) return __err('通道异常！');
        if($resp['code'] !== 200) return __err($resp['desc']);

        if( empty($resp['result']) || $resp['result']['STATE'] !== 4  ) return __err('订单：'.$Order['system_no'].'未支付');

        $sign_str = $resp['result']['ORDER_ID'].$resp['result']['ORDER_AMT'].$resp['result']['BUS_CODE'];
        $sign_1 = md5($sign_str);
        $sign_2 = md5($sign_1.$this->config['signkey']);

        $SIGN = substr($sign_2,8,16);

        if($SIGN !== $resp['result']['SIGN']  ) return __err('查询失败：验签不通过');

        $orderAmt = floatval($resp['result']['ORDER_AMT']);
        if(abs($orderAmt - $Order['amount']) >1) return __err('查询订单金额不匹配：'.$orderAmt);

          //添加到订单查询日志
         logs($res,$type = 'order/query/'.$this->config['code']);
         return  __suc('查询成功！',$res);
    }
    //回调
    public function notify(){
        $param =  $this->getParam('post');

        if(empty($param)) __jerror('no_data');
        if($param['TRANS_STATUS'] !== 'success') __jerror('pay_fail');


        $this->config['returnBack'] = 'success';//返回数据
        $this->config['system_no'] = $param['ORDER_ID'];//系统单号
        $this->config['transaction_no'] =$param['PAY_ORDER_ID']; //第三方订单号
        $this->config['amount'] = $param['AMOUNT'];//下订单金额
        //$this->config['upstream_settle'] = 0;//上游结算金额

        //获取订单信息
        $order = $this->checkOrderNotify();
        //验签
        $sign = $param['SIGN'];
        $sign_str = $param['ORDER_ID'].$param['ORDER_AMT'].$param['BUS_CODE'];
        $sign_1 = md5($sign_str);
        $sign_2 = md5($sign_1.$this->config['signkey']);
        $sign2 = substr($sign_2,8,16);

        if($sign !== $sign2) __jerror('sign_wrong');


        return $this->async($order);
    }



    //话费 通道查询库存
    public function repertory(){
        $data['USER_ID'] =  $this->config['mch_id'];

        $res = Curl::post('http://bx.70104.cn/sk-pay/pay/hfczAmount', http_build_query($data));
        $resp = json_decode($res,true);
        /*
         * array(3) {
  ["msg"] => string(12) "获取成功"
  ["data"] => array(11) {
    ["msg"] => string(12) "获取成功"
    ["totoal_100"] => int(0)
    ["totoal_30"] => int(0)
    ["code"] => string(4) "0000"
    ["totoal_50"] => int(0)
    ["total_numbers"] => int(1)
    ["totoal_500"] => int(0)
    ["totoal_20"] => int(0)
    ["totoal_300"] => int(0)
    ["totoal_10"] => int(0)
    ["totoal_200"] => int(1)
  }
  ["state"] => int(0)
}*/

        $data[10] = 0;
        $data[30] = 0;
        $data[50] = 0;
        $data[100] = 0;
        $data[200] = 0;
        $data[300] = 0;
        $data[500] = 0;
        halt($data);
        if(empty($res) || empty($resp) || $resp['state'] != 0 || $resp['data']['code'] != '0000'  ) return $data;

        $data[10] = $data['data']['totoal_10'];
        $data[30] = $data['data']['totoal_30'];
        $data[50] = $data['data']['totoal_50'];
        $data[100] = $data['data']['totoal_100'];
        $data[200] = $data['data']['totoal_200'];
        $data[300] = $data['data']['totoal_300'];
        $data[500] = $data['data']['totoal_500'];
        dump($data);
        return $data;
    }

    public function callback(){
        echo '处理成功'; exit;
    }

}
