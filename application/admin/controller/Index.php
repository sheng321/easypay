<?php
namespace app\admin\controller;

use app\common\controller\AdminController;

class Index  extends AdminController
{
    public function index()
    {
        //左侧菜单
        $apimenu = new \app\admin\controller\api\Menu();

        $basic_data = [
             'title'=> '主页',
            'menu_view' => $apimenu->getNav(),
        ];
        return $this->fetch('', $basic_data);
    }

    /**
     * 首页欢迎界面
     * @return mixed
     */
    public function welcome() {

        return $this->fetch('');
    }

}
