<?php


namespace app\user\controller;


use app\common\controller\UserController;
use think\Db;
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

    public function index(){
        if ($this->request->get('type') == 'ajax') {
            $page = $this->request->get('page', 1);
            $limit = $this->request->get('limit', 10);
            $search = (array)$this->request->get('search', []);
            return json($this->model->list($page, $limit, $search,$this->user['uid']));
        }
        return view("index");
    }





















}