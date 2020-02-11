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
        $data =  \app\common\service\CountService::channel_account();
        halt($data);

    }

}
