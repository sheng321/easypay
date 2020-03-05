<?php
namespace app\admin\controller;

use app\common\model\Umember;
use app\common\model\Umoney;
use app\common\model\Uprofile;
use app\common\service\SubTable;
use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;


class Test  extends Controller
{
    public function index()
    {


        halt( \think\Queue::push('app\\common\\job\\Df', 237, 'df'));


    }

}
