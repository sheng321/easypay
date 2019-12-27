<?php
namespace app\agent\controller;

use app\common\controller\AgentController;

class Index extends AgentController
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
