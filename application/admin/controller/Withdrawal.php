<?php

namespace app\admin\controller;

use app\common\controller\AdminController;

/**
 * Undocumented 提现记录
 */
class Withdrawal extends AdminController {

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
        $this->model = model('app\common\model\Withdrawal');
    }
    /**
     * Undocumented 提现列表
     *
     * @return void
     */
    public function index(){
        if($this->request->isPost()){

            return $this->model->editField($this->request->param());
        }else{
            if ($this->request->get('type') == 'ajax') {
                $page = $this->request->get('page', 1);
                $limit = $this->request->get('limit', 10);
                $search = (array)$this->request->get('search', []);
                return json($this->model->wlist($page, $limit, $search));
            }
            return view("index");
        }
    }
    /**
     * Undocumented 锁定/解锁 出款/退款
     *
     * @return boolean
     */
    public function with_save(){
        $data = $this->request->param();
        $info = $this->model->where("id",$data['id'])->find();
        if(!$info) return __error("数据不存在");
        return $this->model->saveWith($data,$info,$this->user->username);
    }











    
}