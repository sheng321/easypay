<?php
namespace app\admin\controller;

use app\common\controller\AdminController;
use redis\StringModel;

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
    public function welcome(){
        //当前访问量
        $redis1 = (new StringModel())->instance();
        $redis1->select(2);

        //当前访问量
        $keys =  $redis1->keys('flow_*');

        $info = $redis1->mget($keys);
        foreach($info as $k=>$v){
            $info[$k] = json_decode($v,true);
        }

        $basic_data = [
            'title'=> '欢迎页',
            'info' => $info,
        ];
        return $this->fetch('',$basic_data);
    }

}
