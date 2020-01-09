<?php

namespace app\common\controller;
use app\common\model\Order;
use think\Queue;


/**
 * 下单基础控制器
 * Class AdminController
 * @package controller
 */
class PayController extends BaseController
{

    //通道配置信息
    protected $config;

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("PRC");
        set_time_limit(60);
    }

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

        return $param;
    }

    protected function checkOrderNotify($sn){

     $order =  Order::quickGet(['systen_no'=>$sn]);
      //下单失败 订单号不存在
     if(empty($order) || $order['pay_status'] == 1) __jerror('no_order');

     //已支付
     if($order['pay_status'] == 2){
         $returnBack = empty($this->config['returnBack'])?'':$this->config['returnBack'];
         throw new \think\exception\HttpResponseException(exit($returnBack));
     }

     //订单接受回调 时间限制
     $time = empty($this->config['time_limit'])?10*60:$this->config['time_limit']*60;
     $limit = strtotime($this->config['create_at']) + $time;
    // if($limit < time()) __jerror('over_time');

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
            'order'=>['systen_no'=>$order['systen_no']],
            'config'=>[
                'transaction_no'=>empty($this->config['transaction_no'])?'':$this->config['transaction_no'],
                'amount'=>empty($this->config['amount'])?'':$this->config['amount']
            ],
        ];//传入的数据

        $queue = 'api';//队列名，可以理解为组名
        //push()方法是立即执行
        $res =  Queue::push($job, $data, $queue);

        //同步
        //$res = \app\common\service\MoneyService::api($data);
       // halt($res);

        if( $res === false ) __jerror('fail');

        return  $this->config['returnBack'];
    }


}