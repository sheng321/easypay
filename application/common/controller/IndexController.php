<?php

namespace app\common\controller;


/**
 * 前台基础控制器
 * Class AdminController
 * @package controller
 */
class IndexController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        Policy();

        //不接受任何参数
        $res = $this->request->param();
        if(!empty($res)) exit();

    }





}