<?php
namespace app\agent\controller;

use app\common\controller\AgentController;

class Api extends AgentController
{

    /**通道费率
     * @return mixed
     */
    public function index()
    {
        $basic_data = [
            'title'=> '主页',
        ];

        return $this->fetch('', $basic_data);
    }

}
