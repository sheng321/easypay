<?php

namespace app\admin\controller;

use app\common\controller\AdminController;
use app\common\service\CountService;


class Count extends AdminController {

    /**
     * Count模型对象
     */
    protected $model = null;

    /**
     * 初始化
     * Count constructor.
     */
    public function __construct() {
        parent::__construct();

    }

    /**
     * 通道成功率
     */
    public function index() {

        $data = CountService::success_rate();

        halt($data);




        if (!$this->request->isPost()) {

            //基础数据
            $basic_data = [
                'title' => '通道成功率',
                'data'  => CountService::success_rate(),
            ];

            return $this->fetch('', $basic_data);
        }
    }



}