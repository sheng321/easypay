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
     * Undocumented 锁定/解锁
     *
     * @return boolean
     */
    public function is_lock(){
        $data = $this->request->param();
        $info = $this->model->where("id",$data['id'])->find();
        if(!$info) return __error("数据不存在");
        if($info->status == 3 || $info->status==4) return __error("状态不对");
        if($data['type'] == 1){//锁定
            $info->is_lock = 1;
            $info->status = 2;
            $info->lock_name = $this->user->username;
        }else{//解除
            if($info->lock_name != $this->user->username){
                return __error('只能由账号【'.$info->lock_name.'】来解除');
            }
            $info->is_lock = 2;
            $info->status = 1;
            $info->lock_name = '';
            $info->channel = '';
        }
        $info->save();
        return __success('操作成功');
    }
    /**
     * Undocumented 出款/退款/删除
     *
     * @return void
     */
    public function refund(){
        
        $id = $this->request->param('id');
    }











    
}