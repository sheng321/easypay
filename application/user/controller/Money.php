<?php

namespace app\user\controller;

use app\common\controller\AdminController;
use app\common\controller\UserController;
use app\common\model\PayProduct;
use app\pay\service\Payment;
use think\facade\Session;


class Money extends UserController {

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
     *  资金变动记录
     * @return void
     */
    public function log(){
        $this->model = model('app\common\model\UmoneyLog');
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 15);
            $search1 = (array)$this->request->get('search', []);

            !empty($search['relate']) && $search['relate'] = $search1['relate'];
            !empty($search['create_at']) && $search['create_at'] = $search1['create_at'];

            $search['type1'] = 0;
            $search['uid'] = $this->user['uid'];
            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '资金变动记录',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }




}