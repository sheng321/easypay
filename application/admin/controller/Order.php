<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\model\PayProduct;

/**
 * Undocumented 订单记录
 */
class Order extends AdminController {

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
            'title'  => '支付产品列表',
            'data'   => '',
            'order' => config('order.'),
            'product' =>  PayProduct::idArr()//支付产品
        ];

        return $this->fetch('', $basic_data);
    }
    /**
     * Undocumented 详情
     *
     * @return void
     */
    public function details(){
        $id = $this->request->param('id');
        $info = $this->model->where("id",$id)->find();
        if(!$info){
            return __error("数据不存在");
        }
        $this->assign("info",$info);
        return view("details");
    }
    /**
     * Undocumented 补发通知
     *
     * @return void
     */
    public function replacement(){
        $id = $this->request->param('id');
        $info = $this->model->where("id",$id)->find();
        if(!$info){
            return __error("数据不存在");
        }
        $data = [];
        $data['sign'] = 'xxxxxxxxxxx';
        $result = $this->model->orderSend($data,$info->id);
        return __success('发送成功,异步返回：'.$result);
    }
    /**
     * Undocumented 强制入账
     *
     * @return void
     */
    public function compel(){
        $id = $this->request->param('id');
        $info = $this->model->where("id",$id)->find();
        if(!$info){
            return __error("数据不存在");
        }
        return $this->model->orderUpdate($info,$info->amount,2);
    }
    /**
     * Undocumented 删除订单
     *
     * @return void
     */
    public function deleteOrder(){
        $id = $this->request->param('id');
        $info = $this->model->where("id",$id)->find();
        if(!$info){
            return __error("数据不存在");
        }
    }







    
}