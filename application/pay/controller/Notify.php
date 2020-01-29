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
        $this->config = $this->set_notify_config($code);
    }

    public function index(){

        $return = [];
        $Payment = Payment::factory($this->config['code']);
        // $Payment = Payment::factory('Index');
        $html  = $Payment->notify($return);
        return $html;
    }


}
