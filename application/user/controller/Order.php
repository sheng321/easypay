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
     * Undocumented 订单列表
     * @return void
     */
    public function index(){

        if ($this->request->get('type') == 'ajax'){

            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            return json($this->model->alist($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title'  => '订单列表',
            'data'   => '',
            'order' => config('order.'),
            'product' => PayProduct::idArr(),//支付产品
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 下载
     * @return void
     */
    public function export(){

        $field = [
            'id',
            'mch_id',
            'mch_id1',
            'mch_id2',
            'out_trade_no',
            'systen_no',
            'transaction_no',
            'amount',
            'actual_amount',
            'cost_rate',
            'run_rate',
            'agent_rate',
            'agent_rate2',
            'total_fee',
            'Platform',
            'settle',
            'upstream_settle',
            'agent_amount',
            'agent_amount2',

            'payment_id',
            'channel_group_id',
            'pay_status',
            'channel_id',
            'over_time',
            'notice',
            'create_time',
            'pay_time',
            'ip',
            'update_at',
            'over_time',
        ];

        $title = [
            'id'=>'ID',
            'mch_id'=>'商户号',
            'mch_id1'=>'代理',
            'mch_id2'=>'上上代理',
            'out_trade_no'=>'商户单号',
            'systen_no'=>'系统单号',
            'transaction_no'=>'上游单号',
            'amount'=>'下单金额',
            'actual_amount'=>'实际支付',
            'total_fee'=>'手续费',
            'cost_rate'=>'成本费率',
            'run_rate'=>'运营费率',

            'settle'=>'商户结算',
            'agent_rate'=>'上代理费率',
            'agent_rate2'=>'上上级代理费率',

            'upstream_settle'=>'上游结算',
            'agent_amount'=>'上级代理商结算',
            'agent_amount2'=>'上上级代理商结算',
            'Platform'=>'平台收益',


            'channelgroup_name'=>'通道分组',
            'product_name'=>'支付类型',
            'channel_name'=>'通道',
            'pay_status_name'=>'支付状态',
            'notice_name'=>'通知',
            'create_time'=>'提交时间',
            'pay_time'=>'支付时间',
            'ip'=>'IP',
            'update_at'=>'最近更新时间',
        ];

        if ($this->request->get('type') == 'ajax'){
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->except(['type','page','limit']);
            $search['field'] = $field;
            return json($this->model->alist($page, $limit, $search));
        }

        $field1 = [
            'pay_status_name',
            'notice_name',
            'product_name',
            'channel_name',
            'channelgroup_name',
        ];

        $field =  array_merge($field,$field1);

        //基础数据
        $basic_data = [
            'title'  => '订单列表',
            'url'  =>request() -> url(),
            'data'   => ['field'=>json_encode($field),'title'=>json_encode($title)],
        ];

        return $this->fetch('export/index', $basic_data);
    }


}