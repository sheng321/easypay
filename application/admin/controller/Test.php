<?php
namespace app\admin\controller;

use app\common\model\Umember;
use app\common\model\Umoney;
use think\Controller;
use redis\StringModel;
use think\Queue;
use tool\Curl;


class Test  extends Controller
{
    public function index()
    {

        $login =    Umember::where(['username'=>'test'])->find();


        halt($login);

    }

}
