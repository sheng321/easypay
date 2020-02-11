<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

/**
 * 消息中心
 * Class Message
 * @package app\admin\controller
 */
class Message extends AdminController
{
    public function index()
    {


        halt(111);

        $basic_data = [
             'title'=> '消息中心',

        ];
        return $this->fetch('', $basic_data);
    }


}
