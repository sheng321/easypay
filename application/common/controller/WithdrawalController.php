<?php

namespace app\common\controller;
use app\common\model\ChannelDf;
use app\common\model\Df;
use app\common\model\Order;
use think\helper\Str;
use think\Queue;


/**
 * 代付下单基础控制器
 * Class AdminController
 * @package controller
 */
class WithdrawalController extends BaseController
{


    //通道配置信息
    protected $config;

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("PRC");
        set_time_limit(60);

    }

    protected  function set_config($class){
        $code =  end($class);
        $config = ChannelDf::get_config($code);
        if(empty($config)) __jerror('支付服务不存在6');
        return $config;
    }







    /**
     * 下单失败
     * @param $payurl 支付链接
     * @param $error_msg 错误信息
     * @param $orderId 订单ID
     */
    protected function order_error($payurl,$error_msg,$orderId){
        if(empty($payurl)){
            $msg = '获取支付链接失败!';
            if(!empty($error_msg)) $msg = Str::substr($error_msg,0,100) ;
            //下单失败
            model('app\common\model\Order')->save(['id'=>$orderId,'pay_status'=>1,'remark'=>$msg],['id'=>$orderId]);
            __jerror($msg);
        }
    }


    /**获取回调信息
     * @param string $type 类型
     * @param string $name 指定参数
     * @return array|mixed|string
     */
    protected function getParam($type = '',$name = ''){
        switch (true){
            case ($type == 'param'):
                if(empty($name)){
                    $param = $this->request->except(['Pay'], 'param');
                }else{
                    $param = $this->request->param($name,'',null);
                }
                break;
            case ($type == 'get'):
                $param = $this->request->get();
                break;
            case ($type == 'post'):
                $param = $this->request->post();
                break;
            case ($type == 'json'):
                $param = json_decode(file_get_contents('php://input'),true);
                break;
            case ($type == 'header'):
                $param = $this->request->header();
                break;
            case ($type == 'xml'):
                //1.获取xml数据
                $xmldata=file_get_contents('php://input');
               //2.把xml转换为simplexml对象
                $xmlstring = simplexml_load_string($xmldata, 'SimpleXMLElement', LIBXML_NOCDATA);
               //3.把simplexml对象转换成 json，再将 json 转换成数组。
                $param = json_decode(json_encode($xmlstring),true);
                break;
            default:
                $param = file_get_contents('php://input');
                break;
        }
        //添加到订单回调日志
        logs($param,$type = 'order/notify/'.$this->config['code']);
        return $param;
    }

    protected function checkOrderNotify(){
     $order =  Order::quickGet(['system_no'=>$this->config['system_no']]);
      //下单失败 订单号不存在 订单关闭
     if(empty($order) || $order['pay_status'] == 1 || $order['pay_status'] == 3) __jerror('no_order');

     //已支付
     if($order['pay_status'] == 2){
         $returnBack = empty($this->config['returnBack'])?'':$this->config['returnBack'];
         throw new \think\exception\HttpResponseException(exit($returnBack));
     }

     //订单接受回调 时间限制
        if(time() > $order['over_time']) __jerror('over_time');


     //判断订单金额
      if(!empty($this->config['amount'])  && abs( $order['amount'] - $this->config['amount']) > 1 )  __jerror('money_wrong1');

      //判断结算金额
      if(!empty($this->config['upstream_settle'])  && abs( $order['upstream_settle'] - $this->config['upstream_settle']) > 1 )  __jerror('money_wrong2');

     return $order;
    }

    protected function async($order){
        //加入异步队列
        $job = 'app\\common\\job\\Api';//调用的任务名
        $data = [
            'order'=>['system_no'=>$order['system_no']],
            'config'=>[
                'transaction_no'=>empty($this->config['transaction_no'])?'':htmlspecialchars($this->config['transaction_no']),
                'amount'=>empty($this->config['amount'])?0:floatval($this->config['amount']),
                'code'=>$this->config['code']
            ],
        ];//传入的数据

        $queue = 'api';//队列名，可以理解为组名
        //push()方法是立即执行
        $res =  Queue::push($job, $data, $queue);
        if( $res === false ) __jerror('fail');

        //同步
       // $res = \app\common\service\MoneyService::api($data['order']['system_no'],$data['config']['transaction_no'],$data['config']['amount']);
       // halt($res);

        return  $this->config['returnBack'];
    }


}