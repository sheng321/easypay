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

        if(empty($keys)){
            $info = [];
        }else{
            $info = $redis1->mget($keys);
        }

        dump($info);
        $PayCode =  \app\common\model\PayProduct::idCode();

        $option = [];
        foreach($info as $k=>$v){
            $des = json_decode($v,true);

            $option['xAxis'][$k] = $des['time'];

            foreach ($PayCode as $k1 =>$v1){
                $option['legend'][] = $des[$v1.'title'];
                $option['series'][$k1]['name'] = $des[$v1.'title'];
                $option['series'][$k1]['type'] = 'line';
                $option['series'][$k1]['stack'] = '总量';
                $option['series'][$k1]['data'][] = $des[$v1];
            }
        }
        halt($option);


        $basic_data = [
            'title'=> '欢迎页',
            'option' => $option,
        ];
        return $this->fetch('',$basic_data);
    }

}
