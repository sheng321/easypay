<?php

namespace app\common\controller;
use app\common\model\Channel;
use app\common\model\Order;
use app\common\model\Umoney;
use app\common\model\UmoneyLog;
use app\pay\service\Payment;
use redis\StringModel;
use think\facade\Url;
use think\helper\Str;
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
        set_time_limit(100);

    }

    protected  function set_api_config($class){
        $code =  end($class);
        $config = Channel::get_config($code);
        if(empty($config)) __jerror('支付服务不存在6');

        //http://www.test4.com/pay.php/notify/index/Pay/Xyf.html
        $config['notifyUrl'] = Url::build('notify/index',['Pay'=>$code],true,true);
        //http://www.test4.com/pay.php/notify/callback.html
        $config['returnUrl'] = Url::build('notify/callback',[],true,true);
        return $config;
    }

    protected  function set_notify_config($code){
        //ctype_alnum  字母和数字或字母数字的组合
        if(empty($code) || !ctype_alnum($code)) __jsuccess('无权访问');
        $config = Channel::get_config($code);
        if(empty($config)) __jerror('无权访问2');

        //IP 白名单
        $back_ip = array_filter(json_decode($config['back_ip'],true));
        if(!empty($back_ip) && !in_array('*',$back_ip)){
            $ip = get_client_ip();
            if(!in_array($ip,$back_ip))  __jerror('无权访问3');
        }
        return $config;
    }



    /**
     * 获取话费通道库存
     * @param $Channel 通道数据
     */
     protected  function charge_num($Channel){
         //通道
         $Channel_father = Channel::quickGet($Channel['pid']);

         $num[10] = 10;
         $num[30] = 10;
         $num[50] = 10;
         $num[100] = 10;
         $num[200] = 10;
         $num[300] = 10;
         $num[500] = 10;

         if(empty($Channel_father) || empty($Channel_father['code']))   return $num;

         $code = $Channel_father['code'];
         $id = $Channel['id'];
        switch ($code){
            case 'Bx':
                \think\facade\Cache::remember('charge_num_'.$id, function () use($code,$id,$num) {
                   try{
                        $Payment = Payment::factory($code);
                        $res = $Payment->repertory();
                   }catch (\Exception $exception){
                       logs($exception->getMessage().'|'.$exception->getFile().'|查询话费库存失败','api');
                   }
                    !isset($res[10]) &&  $num[10] = $res[10];
                    !isset($res[30]) &&  $num[30] = $res[30];
                    !isset($res[50]) &&  $num[50] = $res[50];
                    !isset($res[100]) &&  $num[100] = $res[100];
                    !isset($res[200]) &&  $num[200] = $res[200];
                    !isset($res[300]) &&  $num[300] = $res[300];
                    !isset($res[500]) &&  $num[500] = $res[500];
                    return $num;
                },60);
                $num = \think\facade\Cache::get('charge_num_'.$id);
                break;
            default:
                //不查询的情况
                break;
        }
       return $num;
    }



    /**
     * 获取通道限额
     * @param $Channel 通道数据
     */
    protected  function check_money($Channel){
        //通道
        $Channel_father = Channel::quickGet($Channel['pid']);
        if(empty($Channel_father)) return false;
        $limit_money = (int) $Channel_father['limit_money'];
        if(empty($limit_money)) return true; //没有设置通道限额的情况

        //时间范围
        $end = date('Y-m-d H:i:s');
        $star =  timeToDate( 0,0,0,-1);
        $date[] = ['update_at', 'BETWEEN', ["{$star}", "{$end}"]];

        //算走量  充值入账
        $Umoney = UmoneyLog::where(['channel_id'=>$Channel_father['id'],'uid'=>0,'df_id'=>0,'type1'=>1,'type'=>7])->where($date)->cache('UmoneyLog_check_money'.$Channel_father['id'],1)->sum('change');
        if(empty($Umoney)) return false;

        // 通道限额将满通知
        if($Umoney < $Channel_father['limit_money'] + 6000){
            addTask('通道限额将满',$Channel_father['title'].'通道限额将满，请及时处理。',5,$time = 1);
        }

        //通道可用余额 小于通道限额
        if($Umoney < $Channel_father['limit_money']) return true;

        return false;
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
     if(empty($order) || $order['pay_status'] == 1) __jerror('no_order');
        if($order['pay_status'] == 3) __jerror('订单已关闭，请联系客服处理');

     //已支付
     if($order['pay_status'] == 2){
         $returnBack = empty($this->config['returnBack'])?'':$this->config['returnBack'];
         throw new \think\exception\HttpResponseException(exit($returnBack));//停止运行，关闭。
     }

        //订单接受回调 时间限制
     if(time() > $order['over_time']){
         //当某个通道出现大量未回调的情况，客服临时调大通道的订单限时
         $time =  time() - strtotime($order['create_at']) - $this->config['limit_time']*60;
         if($time > 0) __jerror('over_time');
      }

     //判断订单金额
      if(!empty($this->config['amount'])  && abs( $order['amount'] - $this->config['amount']) > 1 )  __jerror('money_wrong1');

      //判断结算金额
      if(!empty($this->config['upstream_settle'])  && abs( $order['upstream_settle'] - $this->config['upstream_settle']) > 1 )  __jerror('money_wrong2');

     return $order;
    }

    protected function async1($order){
        //加入异步队列
        $data = [
            'order'=>['system_no'=>$order['system_no']],
            'config'=>[
                'transaction_no'=>empty($this->config['transaction_no'])?'':htmlspecialchars($this->config['transaction_no']),
                'amount'=>empty($this->config['amount'])?0:floatval($this->config['amount']),
                'code'=>$this->config['code']
            ],
        ];//传入的数据

        $res =  Queue::push('app\\common\\job\\Api', $data, 'api');
        if( $res === false ) __jerror('fail');

        //删除实时记录IP
        $redis1 = (new StringModel())->instance();
        $redis1->select(2);
        $ip_record = 'recordIP_'.$order['mch_id'].$order['ip'];
        $redis1->del($ip_record);

        return  $this->config['returnBack'];
    }

    protected function async($order){
        ignore_user_abort();

        echo $this->config['returnBack'];

        //断开连接的代码
        $size=ob_get_length();
        header("Content-Length: $size");  //告诉浏览器数据长度,浏览器接收到此长度数据后就不再接收数据
        header("Connection: Close");      //告诉浏览器关闭当前连接,即为短连接
        ob_flush();
        flush();

        //删除实时记录IP
        $redis1 = (new StringModel())->instance();
        $redis1->select(2);
        $ip_record = 'recordIP_'.$order['mch_id'].$order['ip'];
        $redis1->del($ip_record);


        $data = [
            'order'=>['system_no'=>$order['system_no']],
            'config'=>[
                'transaction_no'=>empty($this->config['transaction_no'])?'':htmlspecialchars($this->config['transaction_no']),
                'amount'=>empty($this->config['amount'])?0:floatval($this->config['amount']),
                'code'=>$this->config['code']
            ],
         ];
        //加锁
        try{
            $lock_val = 'Api:'.$data['order']['system_no'];
            $res =  Lock::queueLock(function ($redis)use($data){
                $result = \app\common\service\MoneyService::api($data['order']['system_no'],$data['config']['transaction_no'],$data['config']['amount']);
                return $result;
            },$lock_val,60,10);
        }catch (\Exception $e){
           exit($e->getMessage());
        }


        if($res === true){
            //获取回调数据
            $notify = Order::notify($data['order']['system_no'],$data['config']['code']);
            \think\Queue::push('app\\common\\job\\Notify', $notify, 'notify');//立即
            \think\Queue::later(30,'app\\common\\job\\Notify', $notify, 'notify');//30秒
            \think\Queue::later(60,'app\\common\\job\\Notify', $notify, 'notify');//一分钟
            \think\Queue::later(240,'app\\common\\job\\Notify', $notify, 'notify');//二分钟
            \think\Queue::later(540,'app\\common\\job\\Notify', $notify, 'notify');//五分钟
            exit();//操作完成！
        }

        $data['金额更新'] = $res;
        //错误添加到订单回调日志
        logs($data,$type = 'order/notify/'.$data['config']['code']);
        exit();
    }


}