<?php
namespace app\withdrawal\controller\api;
use app\common\controller\WithdrawalController;
use app\common\model\Channel;
use think\facade\Url;
use think\helper\Str;
use tool\Curl;
use tool\rsa\Xyfrsa;

//盈联科技 代付通道
class Yl extends WithdrawalController
{
    //通道信息
    protected $channel;

    //通道配置信息
    protected $config;

    public function __construct()
    {
        parent::__construct();
        $classArr = explode('\\',get_class());
        $code =  end($classArr);
        $config = Channel::get_config($code);
        if(empty($config)) __jerror('支付服务不存在');
        $this->config = $config;

        //http://www.test4.com/pay.php/notify/index/Pay/Xyf.html
        $this->config['notifyUrl'] = Url::build('notify/index',['Pay'=>$code],true,true);
        //http://www.test4.com/pay.php/notify/callback.html
        $this->config['returnUrl'] = Url::build('notify/callback',[],true,true);
    }

    //发起代付订单
    public function pay($create){
        $data = array();
        $data['merId'] = $this->config['mch_id'];
        $data['orderId'] = $create['system_no'];
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


        $payurl = empty($res['data']['payurl'])?"":$res['data']['payurl'];
        $msg =  empty($res['msg'])?"":$res['msg'];

        //下单失败
        $this->order_error($payurl,$msg,$create['id']);

     return msg_get($res['data']['payurl']);
    }

    //查询订单状态
    public function query($Order,$result =['code' => 0, 'msg' => '查询失败', 'data' => []]){

        $gateway = 'http://api.xinyufu.com/pay/query';
        $data = array();
        $data['merId'] =  $this->config['mch_id'];
        $data['orderId'] = $Order['system_no'];
        $data['nonceStr'] = md5(time() . mt_rand(10000,99999));
        $data['sign'] = $this->getSign($data);
        $res = Curl::post($gateway, http_build_query($data));
        $resp = json_decode($res,true);


        if(empty($resp) ||$resp['code'] !== 1){
            if($resp['msg']) return $result['msg'] = $resp['msg'];
           return $result;
        }
        if( empty($resp['data']) || $resp['data']['status'] != '1'   ){
            $result['msg'] =  '订单：'.$Order['system_no'].'未支付';
            return $result;

        }

        $flag = $this->verifys($resp['data']);
        if(!$flag){
            $result['msg'] = '查询失败：验签不通过';
            return $result;
         }

        $orderAmt = floatval($resp['data']['orderAmt']);
        if(abs($orderAmt - $Order['amount']) >1){
            $result['msg'] = '查询订单金额不匹配：'.$orderAmt;
            return $result;
         }

          //添加到订单查询日志
         logs($res,$type = 'order/query/'.$this->config['code']);
         return ['code' => 1, 'msg' => '查询成功！', 'data' => $res];
    }
    //查询余额
    public function notify(){
        $param =  $this->getParam('param');

        if(empty($param)) __jerror('no_data');
        if($param['status'] !== '1') __jerror('pay_fail');

        $this->config['returnBack'] = 'success';//返回数据
        $this->config['system_no'] = $param['orderId'];//返回数据
        $this->config['transaction_no'] =$param['sysOrderId']; //第三方订单号
        $this->config['amount'] = $param['orderAmt'];//下订单金额
        //$this->config['upstream_settle'] = 0;//上游结算金额

        //获取订单信息
        $order = $this->checkOrderNotify();
        //验签
        $flag = $this->verifys($param);
        if(!$flag) __jerror('sign_wrong');

        return $this->async($order);
    }

}
