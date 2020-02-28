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
        $PayCode =  \app\common\model\PayProduct::idCode1();

        $option = [];
        foreach($info as $k=>$v){
            $des = json_decode($v,true);
            dump($des);

            $option['legend'][$k] = $des[$v['code'].'title'];
            $option['xAxis'][$k] = $des['time'];

            $option['series'][$v['code']]['name'] = $des[$k.'title'];
            $option['series'][$v['code']]['type'] = 'line';
            $option['series'][$v['code']]['stack'] = '总量';
            $option['series'][$v['code']]['data'][] = $des[$k];
        }
        halt($option);



        $option = "{
                    tooltip: {
                        trigger: 'axis'
                    },
                    legend: {
                        data:['邮件营销','联盟广告','视频广告','直接访问','搜索引擎']
                    },
                    grid: {
                        left: '3%',
                        right: '4%',
                        bottom: '3%',
                        containLabel: true
                    },
                    toolbox: {
                        feature: {
                            saveAsImage: {}
                        }
                    },
                    xAxis: {
                        type: 'category',
                        boundaryGap: false,
                        data: ['周一','周二','周三','周四','周五','周六','周日']
                    },
                    yAxis: {
            
                        type: 'value'
                    },
                    series: [
                        {
                            name:'邮件营销',
                            type:'line',
                            stack: '总量',
                            data:[120, 132, 101, 134, 90, 230, 210]
                        },
                        {
                            name:'联盟广告',
                            type:'line',
                            stack: '总量',
                            data:[220, 182, 191, 234, 290, 330, 310]
                        },
                        {
                            name:'视频广告',
                            type:'line',
                            stack: '总量',
                            data:[150, 232, 201, 154, 190, 330, 410]
                        },
                        {
                            name:'直接访问',
                            type:'line',
                            stack: '总量',
                            data:[320, 332, 301, 334, 390, 330, 320]
                        },
                        {
                            name:'搜索引擎',
                            type:'line',
                            stack: '总量',
                            data:[820, 932, 901, 934, 1290, 1330, 1320]
                        }
                    ]
                }";





        $basic_data = [
            'title'=> '欢迎页',
            'info' => $info,
        ];
        return $this->fetch('',$basic_data);
    }

}
