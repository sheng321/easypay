<?php
namespace app\admin\controller;

use app\common\model\SysAdmin;
use app\common\model\Umember;
use app\common\model\Umoney;
use app\common\model\Uprofile;
use app\common\service\SubTable;
use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;
use Lock\Lock;




class Test  extends Controller
{
    public function index()
    {

       // exceptions_api('当前访问人数过多，请稍后再试~');


        $res = \think\Queue::later(20,'app\\common\\job\\Df', 348, 'df');
        $res = \think\Queue::later(20,'app\\common\\job\\Df', 347, 'df');
        $res = \think\Queue::later(20,'app\\common\\job\\Df', 346, 'df');
        $res = \think\Queue::later(20,'app\\common\\job\\Df', 345, 'df');

        halt( $res);


    }

}
