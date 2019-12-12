<?php

namespace app\common\controller;


/**
 * 下单基础控制器
 * Class AdminController
 * @package controller
 */
class PayController extends BaseController
{
    public function __construct()
    {
        parent::__construct();

        Policy();

    }





}