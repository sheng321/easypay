<?php
namespace app\agent\controller;

use app\common\model\SysMenu;
use think\Controller;


class Test  extends Controller
{
    public function index()
    {

        dump(Config('google.domain'));

        $qrCodeUrl = (new \tool\Goole())->getQRCodeGoogleUrl(Config('google.domain'), 111);


        halt($qrCodeUrl);

    }

}
