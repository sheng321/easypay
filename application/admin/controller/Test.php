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

        $res = \think\Queue::push('app\\common\\job\\Df', 342, 'df');
        halt( $res);


    }

}
