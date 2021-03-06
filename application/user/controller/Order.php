<?php

namespace app\user\controller;

use app\common\controller\AdminController;
use app\common\controller\UserController;
use app\common\model\PayProduct;
use app\pay\service\Payment;
use think\facade\Session;

/**
 * Undocumented 订单记录
 */
class Order extends UserController {

    /**
     * config模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * node constructor.
     */
    public function __construct() {
        parent::__construct();
        $this->model = model('app\common\model\Order');
    }
    /**
     *  订单列表
     * @return void
     */
    public function index(){

        if ($this->request->get('type') == 'ajax'){

            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            return json($this->model->clist($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title'  => '订单列表',
            'data'   => '',
            'order' => config('order.'),
            'product' => PayProduct::codeTitle(),//支付产品
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 下载
     * @return void
     */
    public function export(){

        $field = [
            "id",
            "mch_id",
            "out_trade_no",
            "system_no",
            "amount",
            "total_fee",
            "payment_id",
            "actual_amount",
            "create_time",
            "pay_time",
            "productname",
            "pay_status",
            "notice",
            "run_rate",
            "settle"
        ];

        $title = [
            "id"=>'ID',
            "mch_id"=>'商户编号',
            "out_trade_no"=>'商户单号',
            "system_no"=>'系统单号',
            "amount"=>'交易金额',
            "total_fee"=>'手续费',
            "run_rate"=>'运营费率',
            'settle'=>'结算',
            "productname"=>'订单描述',
            'product_name'=>'支付银行',
            'pay_status_name'=>'支付状态',
            'notice_name'=>'通知',
            'create_time'=>'提交时间',
            'pay_time'=>'支付时间',
        ];

        if ($this->request->get('type') == 'ajax'){
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->except(['type','page','limit']);
            $search['field'] = $field;
            return json($this->model->clist($page, $limit, $search));
        }

        $field1 = [
            'pay_status_name',
            'notice_name',
            'product_name',
        ];

        $field =  array_merge($field,$field1);

        //基础数据
        $basic_data = [
            'title'  => '订单列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('common@export/index', $basic_data);
    }


}