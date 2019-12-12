<?php
namespace app\user\controller;

use app\common\controller\UserController;

class Index extends UserController
{
    public function index()
    {
        $basic_data = [
            'title'=> '主页',
        ];

        return $this->fetch('', $basic_data);
    }

    /**
     * 首页欢迎界面
     * @return mixed
     */
    public function welcome() {

        return $this->fetch('');
    }

}
