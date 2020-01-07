<?php
namespace app\pay\controller;
use app\common\controller\PayController;
use app\common\model\Channel;
use app\pay\service\Payment;

/**
 * 订单回调
 * Class Notify
 * @package app\pay\controller
 */
class Notify extends PayController
{
    protected $param;
    protected $config;


    public function __construct()
    {
        parent::__construct();
        //http://www.test4.com/pay.php/notify/index/Pay/Xyf.html
        $code =  $this->request->param('Pay/s','');
        if(empty($code) || !ctype_alnum($code)) __jsuccess('无权访问');
        $config = Channel::get_config($code);
        if(empty($config)) __jerror('无权访问2');

        //IP 白名单
        $back_ip = array_filter(json_decode($config['back_ip'],true));
        if(!empty($back_ip) && !in_array('*',$back_ip)){
            $ip = get_client_ip();
            if(!in_array($ip,$back_ip))  __jerror('无权访问3');
        }

        $this->config = $config;

    }

    public function index(){
        $return = [];
        $Payment = Payment::factory($this->config['code']);
        // $Payment = Payment::factory('Index');
        $html  = $Payment->notify($return);
        return $html;
    }


}