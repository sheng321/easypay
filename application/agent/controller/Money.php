<?php

namespace app\agent\controller;

use app\common\controller\AgentController;
use app\common\model\PayProduct;


class Money extends AgentController {

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
            $page =(int) $this->request->get('page', 1);
            $limit =(int) $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);

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


    /**
     *  代理对账
     * @return void
     */
    public function reconciliation(){
        $this->model = model('app\common\model\Accounts');
        //ajax访问
        if ($this->request->get('type') == 'ajax') {
            $page =(int) $this->request->get('page', 1);
            $limit =(int) $this->request->get('limit', 15);
            $search = (array)$this->request->get('search', []);
            $search['uid'] = $this->user['uid'];

            return json($this->model->aList($page, $limit, $search));
        }

        //基础数据
        $basic_data = [
            'title' => '代理对账列表',
            'data'  => '',
        ];

        return $this->fetch('', $basic_data);
    }


    /**
     *  通道分组分析
     * @return void
     */
    public function analyse(){
        $data = [];
        $info = json_decode(model('app\common\model\Accounts')->where(['uid'=>$this->user['uid']])->order(['day desc'])->value('info'),true);
        if(!empty($info) || is_array($info)) $data = $info;


        //基础数据
        $basic_data = [
            'title' => '通道分组分析',
            'info'  => $data,
        ];

        return $this->fetch('', $basic_data);
    }





}